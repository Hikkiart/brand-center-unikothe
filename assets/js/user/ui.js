// assets/js/user/ui.js
const BCEK_User_UI = {
    init(state) {
        this.state = state;
    },

    /**
     * Desenha todos os campos personalizados no canvas.
     */
    drawCanvas() {
        const { baseImg, canvas, ctx } = this.state.dom;
        if (!baseImg.complete || baseImg.naturalWidth === 0) return;

        // Ajusta o tamanho do canvas para corresponder à imagem base renderizada
        canvas.width = baseImg.clientWidth;
        canvas.height = baseImg.clientHeight;
        this.state.scale = baseImg.clientWidth / baseImg.naturalWidth;
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        this.state.bcekData.fields.forEach(field => {
            const inputData = this.state.userInputs[field.field_id] || {};
            
            if (field.field_type === 'image' && inputData.imageDataUrl) {
                this.drawImage(field, inputData.imageDataUrl);
            } else if (field.field_type === 'text') {
                const text = inputData.text ?? field.default_text;
                const fontSize = inputData.fontSize ?? field.font_size;
                this.drawText(field, text, fontSize);
            }
        });
    },

    /**
     * Desenha uma imagem de utilizador no canvas.
     */
    drawImage(field, imageUrl) {
        const { ctx, baseImg } = this.state.dom;
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
     * Desenha um texto com quebra de linha no canvas.
     */
    drawText(field, text, fontSize) {
        const { ctx } = this.state.dom;
        const scale = this.state.scale;

        ctx.fillStyle = field.font_color || '#000000';
        ctx.font = `${parseInt(fontSize, 10) * scale}px "${field.font_family}"`;
        ctx.textAlign = field.alignment || 'left';

        const lines = text.split('\n');
        const lineHeight = (parseInt(fontSize, 10) * (parseFloat(field.line_height_multiplier) || 1.3)) * scale;
        
        let startX = parseInt(field.pos_x, 10) * scale;
        if (ctx.textAlign === 'center') {
            startX += (parseInt(field.width, 10) / 2) * scale;
        } else if (ctx.textAlign === 'right') {
            startX += parseInt(field.width, 10) * scale;
        }

        let startY = (parseInt(field.pos_y, 10) + parseInt(fontSize, 10)) * scale; // Ajuste para a linha de base
        
        lines.forEach(line => {
            ctx.fillText(line, startX, startY);
            startY += lineHeight;
        });
    },

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
