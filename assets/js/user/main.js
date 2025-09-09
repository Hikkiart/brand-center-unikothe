(function(E, $) { // E é BCEK_User_Editor, $ é jQuery
    'use strict';

    $(document).ready(function() {
        console.log('[BCEK DEBUG] main.js: Documento pronto. A iniciar o editor...');

        // 1. Cache dos elementos do DOM
        E.dom = {
            baseImage: $('#bcek-base-image-preview')[0],
            canvas: $('#bcek-text-overlay-canvas')[0],
            fieldsContainer: $('#fields-container'),
            generateBtn: $('#generate-image-btn'),
            loader: $('#bcek-loader'),
            resultArea: $('#bcek-result-area'),
            cropperModal: $('#bcek-cropper-modal'),
            imageToCrop: $('#bcek-image-to-crop')[0],
            confirmCropBtn: $('#bcek-confirm-crop-btn'),
            cancelCropBtn: $('#bcek-cancel-crop-btn')
        };
        console.log('[BCEK DEBUG] main.js: Elementos do DOM guardados em cache.', E.dom);

        // --- Inicializa o estado com os valores padrão ---
        if (E.config.fields && Array.isArray(E.config.fields)) {
            E.config.fields.forEach(field => {
                if (field.field_type === 'text') {
                    if (!E.userInputs[field.field_id]) E.userInputs[field.field_id] = {};
                    E.userInputs[field.field_id].text = field.default_text || '';
                }
            });
            console.log('[BCEK DEBUG] main.js: Estado inicial dos inputs preenchido:', E.userInputs);
        } else {
            console.error('[BCEK DEBUG] main.js: ERRO - E.config.fields não é um array ou não existe.');
        }

        // Garante que a imagem base esteja carregada antes de desenhar
        if (E.dom.baseImage && E.dom.baseImage.complete && E.dom.baseImage.naturalWidth > 0) {
            console.log('[BCEK DEBUG] main.js: Imagem base já carregada (cache). A chamar drawPreview().');
            E.ui.drawPreview();
        } else if (E.dom.baseImage) {
            console.log('[BCEK DEBUG] main.js: A aguardar pelo carregamento da imagem base...');
            E.dom.baseImage.onload = function() {
                console.log('[BCEK DEBUG] main.js: Imagem base carregada com sucesso. A chamar drawPreview().');
                E.ui.drawPreview();
            };
            E.dom.baseImage.onerror = function() {
                 console.error('[BCEK DEBUG] main.js: ERRO ao carregar a imagem base.');
            }
        } else {
            console.error('[BCEK DEBUG] main.js: ERRO - Elemento da imagem base não encontrado.');
        }

        // 2. Inicia os "ouvintes" de eventos
        console.log('[BCEK DEBUG] main.js: A iniciar os eventos...');
        E.events.init();
    });

})(BCEK_User_Editor, jQuery);