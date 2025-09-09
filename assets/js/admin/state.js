// Módulo para gerir o estado da aplicação do editor
const BCEK_Admin_State = {
    fieldCounter: 0,
    fieldsState: {},
    removedFieldIds: [],
    currentScale: 1,
    imageIsLoaded: false,
    bcekData: window.bcek_admin_data || { template: null, fields: [], ajax_url: '', nonce: '' },
    googleFonts: {
        'Poppins': [{value: '400', name: 'Regular'}, {value: '400i', name: 'Italic'}, {value: '700', name: 'Bold'}, {value: '700i', name: 'Bold Italic'}, {value: '900', name: 'Black'}],
        'Roboto': [{value: '400', name: 'Regular'}, {value: '400i', name: 'Italic'}, {value: '700', name: 'Bold'}, {value: '700i', name: 'Bold Italic'}, {value: '900', name: 'Black'}],
        'Montserrat': [{value: '400', name: 'Regular'}, {value: '400i', name: 'Italic'}, {value: '700', name: 'Bold'}, {value: '700i', name: 'Bold Italic'}, {value: '900', name: 'Black'}],
        'Lobster': [{value: '400', name: 'Regular'}],
        'Playfair Display': [{value: '400', name: 'Regular'}, {value: '400i', name: 'Italic'}, {value: '700', name: 'Bold'}, {value: '700i', name: 'Bold Italic'}]
    },
    
    // As propriedades do DOM são declaradas aqui, mas serão preenchidas no main.js
    dom: {
        editor: null,
        addFieldBtn: null,
        previewContainer: null,
        baseImagePreview: null,
        baseImageUpload: null,
        fieldsList: null,
        saveButton: null,
        templateNameInput: null,
        baseImageIdInput: null,
        baseImageUrlInput: null,
        imageUploadLoader: null
    }
};