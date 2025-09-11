// assets/js/user/state.js
const BCEK_User_State = {
    // Dados recebidos do WordPress
    bcekData: window.bcek_data || { template: {}, fields: [], nonce: '', ajax_url: '', fonts_url: '' },

    // Estado dinâmico
    userInputs: {}, // Armazena os valores que o utilizador insere
    cropper: null,
    currentCroppingField: null,
    scale: 1,

    // Referências aos elementos do DOM
    dom: {
        wrapper: null,
        canvas: null,
        ctx: null,
        baseImg: null,
        resultArea: null,
        loader: null,
        cropperModal: null,
        imageToCrop: null,
    }
};