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

    /**
     * VERSÃO FINAL: Garante o carregamento das fontes antes da primeira renderização.
     */
    async function initializeEditor() {
        try {
            // Espera que TODAS as fontes declaradas no CSS (incluindo as da Google) estejam prontas
            await document.fonts.ready;
            console.log('BCEK: Fontes do documento prontas.');

        } catch (e) {
            console.error('BCEK: Falha ao esperar pelas fontes.', e);
        }
        
        // AGORA, com as fontes garantidamente prontas, faz a primeira renderização.
        BCEK_User_Events.updateAllInputs();
    }

    // Inicializa os módulos
    BCEK_User_UI.init(State);
    BCEK_User_Ajax.init(State);
    BCEK_User_Events.init(State, BCEK_User_UI, BCEK_User_Ajax);
    
    // Dispara a inicialização após a imagem base carregar
    if (State.dom.baseImg.complete && State.dom.baseImg.naturalWidth > 0) {
        initializeEditor();
    } else {
        State.dom.baseImg.addEventListener('load', initializeEditor);
    }
});