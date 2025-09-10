// assets/js/user/ui.js
const BCEK_User_UI = {
    init(state) {
        this.state = state;
    },

    /**
     * NOVO E CORRIGIDO: Desenha todos os campos personalizados no canvas.
     */
    drawCanvas() {
        const { baseImg, canvas, ctx } = this.state.dom;
        if (!baseImg.complete || baseImg.naturalWidth === 0) return;

        canvas.width = baseImg.clientWidth;
        canvas.height = baseImg.clientHeight;
        this.state.scale = baseImg.clientWidth / baseImg.naturalWidth;
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Desenha primeiro as imagens que ficam por baixo da imagem base
        this.state.bcekData.fields
            .filter(field => field.field_type === 'image' && parseInt(field.z_index_order, 10) === 0)
            .forEach(field => {
                const inputData = this.state.userInputs[field.field_id] || {};
                if (inputData.imageDataUrl) {
                    this.drawImage(field, inputData.imageDataUrl);
                }
            });

        // Desenha a imagem base
        ctx.drawImage(baseImg, 0, 0, canvas.width, canvas.height);

        // Desenha os campos de texto e as imagens que ficam por cima
        this.state.bcekData.fields.forEach(field => {
            const inputData = this.state.userInputs[field.field_id] || {};
            
            if (field.field_type === 'image' && parseInt(field.z_index_order, 10) !== 0) {
                 if (inputData.imageDataUrl) {
                    this.drawImage(field, inputData.imageDataUrl);
                }
            } else if (field.field_type === 'text') {
                const text = inputData.text ?? field.default_text;
                const fontSize = inputData.fontSize ?? field.font_size;
                this.drawText(field, text, fontSize);
            }
        });
    },

    /**
     * Desenha uma imagem de utilizador no canvas (sem alterações).
     */
    drawImage(field, imageUrl) {
        const { ctx } = this.state.dom;
        const scale = this.state.scale;

        const img = new Image();
        img.onload = () => {
            ctx.save();
            if (field.container_shape === 'circle') {
                ctx.beginPath();
                ctx.arc(
                    (parseInt(field.pos_x) + parseInt(field.width) / 2) * scale,
                    (parseInt(field.pos_y) + parseInt(field.height) / 2) * scale,
                    (parseInt(field.width) / 2) * scale,
                    0, Math.PI * 2, true
                );
                ctx.clip();
            }
            ctx.drawImage(img, parseInt(field.pos_x) * scale, parseInt(field.pos_y) * scale, parseInt(field.width) * scale, parseInt(field.height) * scale);
            ctx.restore();
        };
        img.src = imageUrl;
    },

    /**
     * Lógica de desenho de texto refatorada e corrigida.
     */
    drawText(field, text, fontSize) {
        const { ctx } = this.state.dom;
        const scale = this.state.scale;

        const safeFontSize = parseInt(fontSize, 10) || parseInt(field.font_size, 10) || 16;
        const fontFamily = field.font_family || 'Montserrat';
        const fontWeight = (field.font_weight || '400').replace('i', '');
        const fontStyle = (field.font_weight || '400').includes('i') ? 'italic' : 'normal';
        
        ctx.fillStyle = field.font_color || '#000000';
        ctx.font = `${fontStyle} ${fontWeight} ${safeFontSize * scale}px "${fontFamily}"`;
        ctx.textAlign = field.alignment || 'left';
        
        const lines = this.getWrappedText(text, field.width * scale, ctx);
        const lineHeight = (safeFontSize * (parseFloat(field.line_height_multiplier) || 1.3)) * scale;

        let startX = parseInt(field.pos_x, 10) * scale;
        if (ctx.textAlign === 'center') {
            startX += (parseInt(field.width, 10) / 2) * scale;
        } else if (ctx.textAlign === 'right') {
            startX += parseInt(field.width, 10) * scale;
        }

        // Correção crucial: A posição Y inicial deve ser a do topo do campo mais a altura da primeira linha.
        // O `* 0.8` é um fator de ajuste empírico para alinhar melhor a baseline da fonte.
        let startY = (parseInt(field.pos_y, 10) * scale) + (safeFontSize * scale * 0.8);

        for (let i = 0; i < lines.length; i++) {
            // Verifica se a próxima linha caberá na altura do campo
            if ((startY + (i * lineHeight) - (parseInt(field.pos_y, 10) * scale)) > (parseInt(field.height, 10) * scale)) {
                break;
            }
            ctx.fillText(lines[i], startX, startY + (i * lineHeight));
        }
    },

    /**
     * Função para quebrar o texto, similar à do painel de administração.
     */
    getWrappedText(text, maxWidth, context) {
        if (!text) return [];
        const words = text.split(' ');
        let lines = [];
        let currentLine = words[0] || '';

        for (let i = 1; i < words.length; i++) {
            const word = words[i];
            const width = context.measureText(currentLine + " " + word).width;
            if (width < maxWidth) {
                currentLine += " " + word;
            } else {
                lines.push(currentLine);
                currentLine = word;
            }
        }
        lines.push(currentLine);
        return lines;
    },

    // As funções showCropper, confirmCrop e cancelCrop permanecem iguais.
    showCropper(file, field) {
        this.state.currentCroppingField = field;
        const reader = new FileReader();
        reader.onload = (event) => {
            this.state.dom.imageToCrop.src = event.target.result;
            this.state.dom.cropperModal.style.display = 'flex';

            this.state.cropper = new Cropper(this.state.dom.imageToCrop, {
                aspectRatio: parseInt(field.width) / parseInt(field.height),
                viewMode: 2,
                autoCropArea: 1,
            });
        };
        reader.readAsDataURL(file);
    },

    confirmCrop() {
        const field = this.state.currentCroppingField;
        if (!this.state.cropper || !field) return;

        const croppedCanvas = this.state.cropper.getCroppedCanvas({
            width: parseInt(field.width),
            height: parseInt(field.height),
            imageSmoothingQuality: 'high',
        });
        
        this.state.userInputs[field.field_id] = this.state.userInputs[field.field_id] || { type: 'image' };
        this.state.userInputs[field.field_id].imageDataUrl = croppedCanvas.toDataURL('image/png');
        
        this.drawCanvas();
        this.cancelCrop();
    },
    
    cancelCrop() {
        if (this.state.cropper) {
            this.state.cropper.destroy();
            this.state.cropper = null;
        }
        this.state.dom.imageToCrop.src = '';
        this.state.dom.cropperModal.style.display = 'none';
        this.state.currentCroppingField = null;
        document.querySelectorAll('.bcek-dynamic-image-input').forEach(input => input.value = '');
    }
};
