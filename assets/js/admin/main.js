jQuery(document).ready(function($) {
    'use strict';

    const editorElement = document.getElementById('template-editor');

    // --- GUARDA DE EXECUÇÃO ---
    // Se o elemento principal do editor não existir nesta página, o script para aqui.
    if (!editorElement) {
        return;
    }

    console.log('[BCEK DEBUG] EDITOR: Elemento #template-editor encontrado. A iniciar o editor.');

    function startEditor() {
        const State = BCEK_Admin_State;

        // **A CORREÇÃO** - Passo 1: Preencher o objeto dom AGORA que a página está carregada.
        State.dom = {
            editor: document.getElementById('template-editor'),
            addFieldBtn: document.getElementById('add-field-btn'),
            previewContainer: document.getElementById('editor-preview-container'),
            baseImagePreview: document.getElementById('base-image-preview'),
            baseImageUpload: document.getElementById('base-image-upload'),
            fieldsList: document.getElementById('fields-list'),
            saveButton: document.getElementById('save-template-btn'),
            templateNameInput: document.getElementById('template-name'),
            baseImageIdInput: document.getElementById('base-image-id'),
            baseImageUrlInput: document.getElementById('base-image-url'),
            imageUploadLoader: document.getElementById('image-upload-loader')
        };
        
        // Se o elemento principal do editor não existir, interrompe a execução.
        if (!State.dom.editor) {
            return;
        }

        // Passo 2: Inicializa os outros módulos, agora com o DOM pronto.
        BCEK_Admin_UI.init(State);
        BCEK_Admin_Events.init(State, BCEK_Admin_UI);
        BCEK_Admin_Interact.init(State, BCEK_Admin_UI);

        // Passo 3: Continua com a lógica de inicialização do editor.
        initializeEditorContent();
    }

    function initializeEditorContent() {
        const { bcekData, fieldsState, dom } = BCEK_Admin_State;
        
        if (bcekData.template) {
            dom.templateNameInput.value = bcekData.template.name || 'Novo Template';
            dom.baseImageIdInput.value = bcekData.template.base_image_id || '';
            dom.baseImageUrlInput.value = bcekData.template.base_image_url || '';
        }

        if (bcekData.fields && bcekData.fields.length > 0) {
            bcekData.fields.forEach(field => {
                const config = {
                    id: field.field_id, name: field.name || `Campo ${field.field_id}`,
                    field_type: field.field_type, default_text: field.default_text,
                    pos_x: parseInt(field.pos_x, 10), pos_y: parseInt(field.pos_y, 10),
                    width: parseInt(field.width, 10), height: parseInt(field.height, 10),
                    font_color: field.font_color, font_size: parseInt(field.font_size, 10),
                    font_family: field.font_family, font_weight: field.font_weight || '700',
                    alignment: field.alignment, z_index_order: parseInt(field.z_index_order, 10)
                };
                fieldsState[config.id] = config;
                BCEK_Admin_UI.createFieldSettings(config);
            });
        }
        
        const baseImg = dom.baseImagePreview;
        
        const onImageReady = () => {
            // --- A CORREÇÃO DA OPACIDADE ESTÁ AQUI ---
            // Garante que a imagem esteja totalmente visível assim que for carregada.
            baseImg.style.opacity = '1';

            // 1. Ajusta o tamanho do contentor e calcula a escala.
            BCEK_Admin_UI.updatePreviewSize(); 
            
            // 2. Com a escala correta, (re)cria os elementos visuais.
            Object.values(fieldsState).forEach(config => {
                document.getElementById(`field_${config.id}_preview`)?.remove();
                BCEK_Admin_UI.createFieldPreview(config);
            });
        };

        baseImg.addEventListener('load', onImageReady);

        const imageUrl = bcekData.template ? bcekData.template.base_image_url : '';
        if (imageUrl) {
            baseImg.src = imageUrl;
            BCEK_Admin_State.imageIsLoaded = true;
            BCEK_Admin_UI.showFieldControls();
        }

        if (baseImg.complete && baseImg.naturalWidth > 0) {
            onImageReady();
        }
    }

    // Inicia todo o processo.
    startEditor();
});