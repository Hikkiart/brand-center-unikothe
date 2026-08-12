// assets/js/user/main.js
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const wrapper = document.getElementById('bcek-editor-wrapper');
    if (!wrapper || !window.bcek_data) {
        return;
    }

    const State = BCEK_User_State;

    State.dom = {
        wrapper: wrapper,
        canvas: document.getElementById('bcek-text-overlay-canvas'),
        ctx: document.getElementById('bcek-text-overlay-canvas').getContext('2d'),
        baseImg: document.getElementById('bcek-base-image-preview'),
        resultArea: document.getElementById('bcek-result-area'),
        loader: document.getElementById('bcek-loader'),
        cropperModal: document.getElementById('bcek-cropper-modal'),
        imageToCrop: document.getElementById('bcek-image-to-crop'),
    };
    
    if (!State.dom.canvas || !State.dom.baseImg) {
        console.error('BCEK Error: Elementos essenciais (canvas ou imagem base) não foram encontrados.');
        return;
    }

    async function initializeEditor() {
        try {
            // Passo 1: Espera que os ficheiros das fontes sejam descarregados.
            await document.fonts.ready;
            console.log('BCEK: Ficheiros de fontes descarregados.');

            // --- INÍCIO DA LÓGICA DE PRÉ-ATIVAÇÃO ---
            // Passo 2: Força o browser a renderizar as fontes antes de as usar no canvas.
            const uniqueFontFamilies = [...new Set(
                window.bcek_data.fields
                    .filter(field => field.field_type === 'text' && field.font_family)
                    .map(field => field.font_family)
            )];

            const fontPromises = uniqueFontFamilies.map(fontFamily => {
                return document.fonts.load(`16px "${fontFamily}"`);
            });

            await Promise.all(fontPromises);
            console.log('BCEK: Fontes ativadas nativamente para o canvas.');

        } catch (e) {
            console.error('BCEK: Falha ao esperar ou pré-ativar as fontes.', e);
        }
        
        // Passo 3: Com as fontes garantidamente prontas, executa a primeira renderização.
        BCEK_User_Events.updateAllInputs();
        BCEK_User_UI.redrawPreview();
    }

    // Inicializa os módulos na ordem correta
    BCEK_User_UI.init(State);
    BCEK_User_Ajax.init(State, BCEK_User_UI); 
    BCEK_User_Events.init(State, BCEK_User_UI, BCEK_User_Ajax);
    
    // Dispara a inicialização após a imagem base carregar
    if (State.dom.baseImg.complete && State.dom.baseImg.naturalWidth > 0) {
        initializeEditor();
    } else {
        State.dom.baseImg.addEventListener('load', initializeEditor);
    }
});