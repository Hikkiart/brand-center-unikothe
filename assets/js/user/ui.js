// assets/js/user/ui.js
const BCEK_User_UI = {
    init(state) {
        this.state = state;
    },

    async drawCanvas(canvas, ctx, scale, layer = 'all', isPreview = false) {
        const { baseImg } = this.state.dom;
        if (!baseImg.complete || baseImg.naturalWidth === 0) return;

        const imagePromises = [];
        const fieldsWithImages = this.state.bcekData.fields.filter(field => {
            const inputData = this.state.userInputs[field.field_id] || {};
            return field.field_type === 'image' && inputData.imageDataUrl;
        });
        fieldsWithImages.forEach(field => {
            const inputData = this.state.userInputs[field.field_id];
            const img = new Image();
            img.src = inputData.imageDataUrl;
            imagePromises.push(new Promise(resolve => {
                img.onload = () => resolve({ img, field });
            }));
        });
        const loadedImagesData = await Promise.all(imagePromises);
        const loadedImagesMap = new Map(loadedImagesData.map(data => [data.field.field_id, data.img]));

        // Lida com a densidade de pixels apenas na tela. Na exportação (isPreview = false), mantém original.
        const dpr = isPreview ? (window.devicePixelRatio || 1) : 1;
        
        // Aumenta a resolução física interna do Canvas
        canvas.width = baseImg.naturalWidth * scale * dpr;
        canvas.height = baseImg.naturalHeight * scale * dpr;

        if (isPreview) {
            // Mantém o tamanho visual do Canvas via CSS
            canvas.style.width = `${baseImg.naturalWidth * scale}px`;
            canvas.style.height = `${baseImg.naturalHeight * scale}px`;
            // Escala o contexto para desenhar maior, combinando com a resolução física
            ctx.scale(dpr, dpr);
        }

        ctx.clearRect(0, 0, baseImg.naturalWidth * scale, baseImg.naturalHeight * scale);

        const drawField = (field) => {
            const inputData = this.state.userInputs[field.field_id] || {};
            if (field.field_type === 'image') {
                const img = loadedImagesMap.get(field.field_id);
                if (img) this.drawImage(ctx, scale, field, img);
            } else if (field.field_type === 'text') {
                const text = inputData.text ?? field.default_text;
                const fontSize = inputData.fontSize ?? field.font_size;
                this.drawText(ctx, scale, field, text, fontSize);
            }
        };
        
        const behindLayers = this.state.bcekData.fields.filter(f => parseInt(f.z_index_order, 10) === 0);
        const aboveLayers = this.state.bcekData.fields.filter(f => parseInt(f.z_index_order, 10) !== 0);

        if (layer === 'all' || layer === 'behind') {
            behindLayers.forEach(drawField);
        }

        if (layer === 'all') {
            // CORREÇÃO: Usamos a escala CSS normal (sem dpr). O ctx.scale() 
            // definido no topo da função já fará o aumento para telas Retina.
            ctx.drawImage(baseImg, 0, 0, baseImg.naturalWidth * scale, baseImg.naturalHeight * scale);
        }

        if (layer === 'all' || layer === 'above') {
            aboveLayers.forEach(drawField);
        }
    },
    
    drawImage(ctx, scale, field, imgElement) {
        ctx.save();
        if (field.container_shape === 'circle') {
            ctx.beginPath();
            ctx.arc(
                (parseInt(field.pos_x, 10) + parseInt(field.width, 10) / 2) * scale,
                (parseInt(field.pos_y, 10) + parseInt(field.height, 10) / 2) * scale,
                (parseInt(field.width, 10) / 2) * scale,
                0, Math.PI * 2, true
            );
            ctx.clip();
        }
        ctx.drawImage(imgElement, parseInt(field.pos_x, 10) * scale, parseInt(field.pos_y, 10) * scale, parseInt(field.width, 10) * scale, parseInt(field.height, 10) * scale);
        ctx.restore();
    },
    
    drawText(ctx, scale, field, htmlText, baseFontSize) {
        const PADDING = 4 * scale;
        const safeFontSize = parseInt(baseFontSize, 10) || 16;
        const fontFamily = field.font_family || 'Montserrat';
        const blockX = (parseInt(field.pos_x, 10) * scale) + PADDING;
        const blockY = (parseInt(field.pos_y, 10) * scale) + PADDING;
        const blockWidth = (parseInt(field.width, 10) * scale) - (PADDING * 2);
        const blockHeight = (parseInt(field.height, 10) * scale) - (PADDING * 2);
        const lineHeight = (safeFontSize * (parseFloat(field.line_height_multiplier) || 1.3)) * scale;
        
        ctx.fillStyle = field.font_color || '#000000';
        ctx.textBaseline = 'alphabetic';

        const getFontString = (isBold, isItalic) => {
            let weight = (field.font_weight || '400').replace('i', '');
            let style = (field.font_weight || '').includes('i') ? 'italic' : 'normal';
            if (weight === '400') {
                weight = '700'; // Mantém o peso mais forte para o regular
            }
            if (isBold) weight = '900';
            if (isItalic) style = 'italic';
            return `${style} ${weight} ${safeFontSize * scale}px "${fontFamily}"`;
        };
        
        const tempDiv = document.createElement('div');
        // Transforma blocos nativos do navegador em duplo BR (Parágrafo)
        let cleanText = htmlText
            .replace(/<(div|p)>\s*<br>\s*<\/\1>/gi, '<br><br>') // Trata parágrafos vazios criados por múltiplos Enters
            .replace(/<(div|p)>(.*?)<\/\1>/gi, '<br><br>$2')    // Trata blocos com texto
            .replace(/^(<br>)+/i, '');                          // Remove BRs extras no início, caso o Firefox envolva a 1ª linha em <p>
            
        tempDiv.innerHTML = cleanText;

        let lines = [];
        let currentLine = [];
        function processNode(node, isBold = false, isItalic = false) {
            isBold = isBold || ['STRONG', 'B'].includes(node.nodeName);
            isItalic = isItalic || ['EM', 'I'].includes(node.nodeName);
            if (node.nodeType === Node.TEXT_NODE) {
                node.textContent.split(/(\s+)/).forEach(word => {
                    if(word) currentLine.push({ text: word, bold: isBold, italic: isItalic });
                });
            } else if (node.nodeName === 'BR') {
                lines.push(currentLine); currentLine = [];
            } else if (node.childNodes) {
                Array.from(node.childNodes).forEach(child => processNode(child, isBold, isItalic));
            }
        }
        processNode(tempDiv);
        if(currentLine.length > 0) lines.push(currentLine);

        let wrappedLines = [];
        lines.forEach(line => {
            if(line.length === 0) { wrappedLines.push([]); return; }
            let currentSubLine = [], currentSubLineWidth = 0;
            line.forEach(wordObj => {
                ctx.font = getFontString(wordObj.bold, wordObj.italic);
                const wordWidth = ctx.measureText(wordObj.text).width;

                // --- INÍCIO DA LÓGICA DE QUEBRA DE PALAVRAS LONGAS ---
                if (wordWidth > blockWidth && wordObj.text.length > 1) {
                    // Se a linha atual não estiver vazia, primeiro adiciona-a
                    if (currentSubLine.length > 0) {
                        wrappedLines.push(currentSubLine);
                        currentSubLine = [];
                        currentSubLineWidth = 0;
                    }
                    // Agora, "fatia" a palavra longa
                    let tempWord = '';
                    for (let i = 0; i < wordObj.text.length; i++) {
                        const char = wordObj.text[i];
                        const charWidth = ctx.measureText(tempWord + char).width;
                        if (charWidth > blockWidth) {
                            wrappedLines.push([{ ...wordObj, text: tempWord }]);
                            tempWord = char;
                        } else {
                            tempWord += char;
                        }
                    }
                    if (tempWord) {
                        currentSubLine.push({ ...wordObj, text: tempWord });
                        currentSubLineWidth = ctx.measureText(tempWord).width;
                    }
                    return; // Passa para o próximo objeto de palavra
                }
                // --- FIM DA LÓGICA DE QUEBRA DE PALAVRAS LONGAS ---

                if (currentSubLineWidth > 0 && currentSubLineWidth + wordWidth > blockWidth) {
                    wrappedLines.push(currentSubLine);
                    currentSubLine = [wordObj];
                    currentSubLineWidth = wordWidth;
                } else {
                    currentSubLine.push(wordObj);
                    currentSubLineWidth += wordWidth;
                }
            });
            if(currentSubLine.length > 0) wrappedLines.push(currentSubLine);
        });

        let currentY = blockY + (safeFontSize * scale * 0.9);
        const align = (field.alignment || 'left').trim().toLowerCase();

        wrappedLines.forEach((line, lineIndex) => {
            if (currentY > blockY + blockHeight) return;

            // Remove palavras/espaços vazios no final da linha para cálculo
            let trimmedLine = [...line];
            while (trimmedLine.length > 0 && !trimmedLine[trimmedLine.length - 1].text.trim()) {
                trimmedLine.pop();
            }

            if (trimmedLine.length === 0) {
                currentY += lineHeight;
                return;
            }

            let lineX = blockX;
            let spaceSpacing = 0; // Espaçamento extra para o 'justify'

            // --- LÓGICA DE ALINHAMENTO ---
            if (['center', 'right', 'h-center'].includes(align)) {
                let totalLineWidth = 0;
                trimmedLine.forEach(wordObj => {
                    ctx.font = getFontString(wordObj.bold, wordObj.italic);
                    totalLineWidth += ctx.measureText(wordObj.text).width;
                });

                if (align === 'center' || align === 'h-center') {
                    lineX = blockX + (blockWidth - totalLineWidth) / 2;
                } else if (align === 'right') {
                    lineX = blockX + blockWidth - totalLineWidth;
                }
            } else if (align === 'justify') {
                // Justifica apenas se NÃO for a última linha do parágrafo e houver mais de 1 palavra
                const isLastLineOfParagraph = (lineIndex === wrappedLines.length - 1) || (lineIndex < wrappedLines.length - 1 && wrappedLines[lineIndex + 1].length === 0);
                
                if (!isLastLineOfParagraph && trimmedLine.length > 1) {
                    let totalWordsWidth = 0;
                    trimmedLine.forEach(wordObj => {
                        ctx.font = getFontString(wordObj.bold, wordObj.italic);
                        totalWordsWidth += ctx.measureText(wordObj.text.trim()).width;
                    });
                    
                    // Divide o espaço restante proporcionalmente entre os espaços das palavras
                    const extraSpace = blockWidth - totalWordsWidth;
                    spaceSpacing = extraSpace / (trimmedLine.filter(w => w.text.trim()).length - 1);
                }
            }

            // --- RENDERIZAÇÃO DAS PALAVRAS ---
            let currentX = lineX;
            trimmedLine.forEach((wordObj, wIndex) => {
                const isBlank = !wordObj.text.trim();
                ctx.font = getFontString(wordObj.bold, wordObj.italic);

                if (align === 'justify' && spaceSpacing > 0) {
                    if (!isBlank) {
                        ctx.fillText(wordObj.text.trim(), currentX, currentY);
                        currentX += ctx.measureText(wordObj.text.trim()).width + spaceSpacing;
                    }
                } else {
                    ctx.fillText(wordObj.text, currentX, currentY);
                    currentX += ctx.measureText(wordObj.text).width;
                }
            });

            currentY += lineHeight;
        });
    },

    redrawPreview() {
        const { canvas, ctx, baseImg } = this.state.dom;
        if (!baseImg.complete || baseImg.naturalWidth === 0) return;
        
        const previewScale = baseImg.clientWidth / baseImg.naturalWidth;
        this.state.scale = isNaN(previewScale) || previewScale <= 0 ? 1 : previewScale;
        
        // A camada 'all' é para a pré-visualização completa
        this.drawCanvas(canvas, ctx, this.state.scale, 'all', true);
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
        
        // --- CORREÇÃO AQUI ---
        // Em vez de chamar a função antiga, chama a nova função de redesenho
        this.redrawPreview();
        // --- FIM DA CORREÇÃO ---

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