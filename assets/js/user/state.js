// Objeto global para o nosso editor de utilizador
var BCEK_User_Editor = {
    // Dados passados do PHP (template, campos, etc.)
    config: window.bcek_data || {},
    
    // Objeto para guardar os inputs atuais do utilizador
    userInputs: {},
    
    // Cache de elementos do DOM
    dom: {},

    // Estado da aplicação
    isRendering: false,
    cropper: null, // <-- Objeto para a instância do Cropper.js
    activeImageFieldId: null // <-- Guarda o ID do campo de imagem que está a ser editado
};

// --- DEBUG ---
console.log('[BCEK DEBUG] state.js: Dados recebidos do PHP:', BCEK_User_Editor.config);