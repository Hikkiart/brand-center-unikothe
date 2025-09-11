// assets/js/user/ui.js
const BCEK_User_UI = {
    init(state) {
        this.state = state;
    },

    drawCanvas() {
        const { baseImg, canvas, ctx } = this.state.dom;
        if (!baseImg.complete || baseImg.naturalWidth === 0) return;

        canvas.width = baseImg.clientWidth;
        canvas.height = baseImg.clientHeight;
        this.state.scale = baseImg.clientWidth / baseImg.naturalWidth;
        
        if (isNaN(this.state.scale) || this.state.scale <= 0) {
            return;
        }

        // Limpa o canvas para um novo desenho
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // 1. DESENHA A IMAGEM DE BASE PRIMEIRO, DENTRO DO CANVAS
        ctx.drawImage(baseImg, 0, 0, canvas.width, canvas.height);

        // 2. Percorre os campos e desenha os elementos dinâmicos POR CIMA
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

    drawImage(field, imageUrl) {
        const { ctx } = this.state.dom;
        const scale = this.state.scale;
        const img = new Image();
        img.onload = () => {
            ctx.save();
            if (field.container_shape === 'circle') {
                ctx.beginPath();
                ctx.arc((parseInt(field.pos_x, 10) + parseInt(field.width, 10) / 2) * scale, (parseInt(field.pos_y, 10) + parseInt(field.height, 10) / 2) * scale, (parseInt(field.width, 10) / 2) * scale, 0, Math.PI * 2, true);
                ctx.clip();
            }
            ctx.drawImage(img, parseInt(field.pos_x, 10) * scale, parseInt(field.pos_y, 10) * scale, parseInt(field.width, 10) * scale, parseInt(field.height, 10) * scale);
            ctx.restore();
        };
        img.src = imageUrl;
    },

    /**
     * VERSÃO FINAL: Desenha texto rico, respeitando formatação aninhada, alinhamento e limites.
     */
    drawText(field, htmlText, baseFontSize) {
        const { ctx } = this.state.dom;
        const scale = this.state.scale;

        // --- Configurações ---
        const safeFontSize = parseInt(baseFontSize, 10) || 16;
        const fontFamily = field.font_family || 'Montserrat';
        const blockX = parseInt(field.pos_x, 10) * scale;
        const blockY = parseInt(field.pos_y, 10) * scale;
        const blockWidth = parseInt(field.width, 10) * scale;
        const blockHeight = parseInt(field.height, 10) * scale;
        const lineHeight = (safeFontSize * (parseFloat(field.line_height_multiplier) || 1.3)) * scale;
        
        ctx.fillStyle = field.font_color || '#000000';
        ctx.textBaseline = 'alphabetic';

        // --- Funções Auxiliares ---
        const getFontString = (isBold, isItalic) => {
            let weight = (field.font_weight || '400').replace('i', '');
            if (isBold) {
                weight = weight === '400' ? '700' : '900'; // Normal -> Bold -> Black
            }
            const style = isItalic ? 'italic' : (field.font_weight || '').includes('i') ? 'italic' : 'normal';
            return `${style} ${weight} ${safeFontSize * scale}px "${fontFamily}"`;
        };
        
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = htmlText.replace(/<div>/g, '<br>').replace(/<\/div>/g, '');

        let lines = [];
        let currentLine = [];
        
        function processNodeForWrapping(node, isBold = false, isItalic = false) {
            isBold = isBold || node.nodeName === 'STRONG' || node.nodeName === 'B';
            isItalic = isItalic || node.nodeName === 'EM' || node.nodeName === 'I';

            if (node.nodeType === Node.TEXT_NODE) {
                const words = node.textContent.split(' ');
                words.forEach(word => {
                    if (word) currentLine.push({ text: word, bold: isBold, italic: isItalic });
                });
            } else if (node.nodeName === 'BR') {
                lines.push(currentLine);
                currentLine = [];
            } else if (node.childNodes) {
                Array.from(node.childNodes).forEach(child => processNodeForWrapping(child, isBold, isItalic));
            }
        }
        
        processNodeForWrapping(tempDiv);
        if (currentLine.length > 0) lines.push(currentLine);

        let wrappedLines = [];
        lines.forEach(line => {
            if (line.length === 0) {
                wrappedLines.push([]);
                return;
            }
            let currentSubLine = [];
            let currentSubLineWidth = 0;
            line.forEach(wordObj => {
                ctx.font = getFontString(wordObj.bold, wordObj.italic);
                const wordWidth = ctx.measureText(wordObj.text + ' ').width;

                if (currentSubLineWidth > 0 && currentSubLineWidth + wordWidth > blockWidth) {
                    wrappedLines.push(currentSubLine);
                    currentSubLine = [wordObj];
                    currentSubLineWidth = wordWidth;
                } else {
                    currentSubLine.push(wordObj);
                    currentSubLineWidth += wordWidth;
                }
            });
            wrappedLines.push(currentSubLine);
        });

        let currentY = blockY + (safeFontSize * scale * 0.9);

        wrappedLines.forEach(line => {
            if (currentY > blockY + blockHeight) return;

            let lineX = blockX;
            let totalLineWidth = 0;
            if (field.alignment === 'center' || field.alignment === 'right') {
                line.forEach(wordObj => {
                    ctx.font = getFontString(wordObj.bold, wordObj.italic);
                    totalLineWidth += ctx.measureText(wordObj.text + ' ').width;
                });
                if (field.alignment === 'center') lineX = blockX + (blockWidth - totalLineWidth) / 2;
                if (field.alignment === 'right') lineX = blockX + blockWidth - totalLineWidth;
            }

            let currentX = lineX;
            line.forEach(wordObj => {
                ctx.font = getFontString(wordObj.bold, wordObj.italic);
                ctx.fillText(wordObj.text, currentX, currentY);
                currentX += ctx.measureText(wordObj.text + ' ').width;
            });
            currentY += lineHeight;
        });
    },

    showCropper(file, field) {
        this.state.currentCroppingField = field;
        const reader = new FileReader();
        reader.onload = (event) => {
            this.state.dom.imageToCrop.src = event.target.result;
            this.state.dom.cropperModal.style.display = 'flex';
            this.state.cropper = new Cropper(this.state.dom.imageToCrop, {
                aspectRatio: parseInt(field.width, 10) / parseInt(field.height, 10),
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
            width: parseInt(field.width, 10),
            height: parseInt(field.height, 10),
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