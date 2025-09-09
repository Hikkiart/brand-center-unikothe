(function(E) { // E é um atalho para BCEK_User_Editor
    'use strict';

    E.ui = {
        /**
         * A função principal que desenha tudo no canvas.
         */
        drawPreview: function() {
            console.log('[BCEK DEBUG] ui.js: A função drawPreview() foi chamada.');

            const canvas = E.dom.canvas;
            if (!canvas) { console.error('Canvas não encontrado'); return; }
            const ctx = canvas.getContext('2d');
            const baseImage = E.dom.baseImage;
            if (!baseImage) { console.error('Imagem base não encontrada'); return; }

            canvas.width = baseImage.naturalWidth;
            canvas.height = baseImage.naturalHeight;
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(baseImage, 0, 0);
            console.log('[BCEK DEBUG] ui.js: Imagem base desenhada no canvas.');

            E.config.fields.forEach(field => {
                console.log(`[BCEK DEBUG] ui.js: A processar campo ID ${field.field_id} do tipo ${field.field_type}.`);
                if (field.field_type === 'text') {
                    this.drawTextField(ctx, field);
                } else if (field.field_type === 'image') {
                    this.drawImageField(ctx, field);
                }
            });
        },


        /**
         * Desenha um campo de imagem no canvas.
         */
        drawImageField: function(ctx, field) {
            const userImage = E.userInputs[field.field_id]?.image;
            if (userImage) {
                ctx.drawImage(
                    userImage,
                    parseInt(field.pos_x, 10), parseInt(field.pos_y, 10),
                    parseInt(field.width, 10), parseInt(field.height, 10)
                );
            }
        },
        
        /**
         * Desenha um campo de texto no canvas, com quebra de linha.
         */
        drawTextField: function(ctx, field) {
            const fieldId = field.field_id;
            const text = E.userInputs[fieldId]?.text || field.default_text;
            
            console.log(`[BCEK DEBUG] ui.js: A desenhar texto para o campo ID ${fieldId}. Texto: "${text}"`);
            if (!text) return;

            ctx.fillStyle = field.font_color || '#000000';
            ctx.font = `${field.font_size}px "${field.font_family}"`;
            ctx.textAlign = field.alignment || 'left';

            const lines = this.getWrappedLines(ctx, text, field.width);
            const lineHeight = field.font_size * 1.3;
            let y = parseInt(field.pos_y, 10) + parseInt(field.font_size, 10);
            
            let x = parseInt(field.pos_x, 10);
            if (ctx.textAlign === 'center') x += field.width / 2;
            else if (ctx.textAlign === 'right') x += field.width;

            lines.forEach(line => {
                ctx.fillText(line, x, y);
                y += lineHeight;
            });
        },

        /**
         * Calcula as quebras de linha para um texto.
         */
        getWrappedLines: function(ctx, text, maxWidth) {
            const words = text.split(' ');
            let lines = [];
            let currentLine = words[0] || '';

            for (let i = 1; i < words.length; i++) {
                const word = words[i];
                const width = ctx.measureText(currentLine + " " + word).width;
                if (width < maxWidth) {
                    currentLine += " " + word;
                } else {
                    lines.push(currentLine);
                    currentLine = word;
                }
            }
            lines.push(currentLine);
            return lines;
        }
    };

})(BCEK_User_Editor);