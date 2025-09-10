// assets/js/user/main.js
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const wrapper = document.getElementById('bcek-editor-wrapper');
    if (!wrapper || !window.bcek_data) {
        return;
    }

    const State = BCEK_User_State;

    // Mapeia os elementos do DOM para o objeto de estado
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
    
    // Inicializa os módulos
    BCEK_User_UI.init(State);
    BCEK_User_Ajax.init(State);
    BCEK_User_Events.init(State, BCEK_User_UI, BCEK_User_Ajax);
});
