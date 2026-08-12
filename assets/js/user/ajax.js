// assets/js/user/ajax.js
const BCEK_User_Ajax = {
    init(state, ui) {
        this.state = state;
        this.ui = ui; // Guarda a referência ao módulo UI
    },

    async generateImage(format) {
        const { loader, resultArea, baseImg } = this.state.dom;
        const { nonce, ajax_url, template } = this.state.bcekData;

        loader.style.display = 'block';
        resultArea.innerHTML = '';

        // --- LÓGICA DE EXPORTAÇÃO COM DUAS CAMADAS ---
        
        // 1. Cria o canvas para a camada "DEBAIXO"
        const behindCanvas = document.createElement('canvas');
        behindCanvas.width = baseImg.naturalWidth;
        behindCanvas.height = baseImg.naturalHeight;
        await this.ui.drawCanvas(behindCanvas, behindCanvas.getContext('2d'), 1, 'behind');
        const behindOverlayDataUrl = behindCanvas.toDataURL('image/png');

        // 2. Cria o canvas para a camada "DE CIMA"
        const aboveCanvas = document.createElement('canvas');
        aboveCanvas.width = baseImg.naturalWidth;
        aboveCanvas.height = baseImg.naturalHeight;
        await this.ui.drawCanvas(aboveCanvas, aboveCanvas.getContext('2d'), 1, 'above');
        const aboveOverlayDataUrl = aboveCanvas.toDataURL('image/png');

        // --- FIM DA LÓGICA ---

        const dataToSend = {
            action: 'bcek_generate_image',
            nonce: nonce,
            template_id: template.template_id,
            user_inputs: this.state.userInputs,
            user_filename: document.getElementById('bcek_filename').value,
            format: format,
            // Envia as duas camadas em vez de uma
            behind_overlay_data_url: behindOverlayDataUrl,
            above_overlay_data_url: aboveOverlayDataUrl
        };
        
        jQuery.post(ajax_url, dataToSend)
            .done(function (response) {
                if (response.success) {
                    const link = `<a href="${response.data.url}" download class="inline-block bg-green-500 text-white font-bold py-2 px-4 rounded-lg">Descarregar Imagem (${format.toUpperCase()})</a><p class="text-xs text-gray-500 mt-2">${response.data.deleted_in}</p>`;
                    resultArea.innerHTML = link;
                } else {
                    resultArea.innerHTML = `<p class="text-red-500">Erro: ${response.data.message}</p>`;
                }
            })
            .fail(function () {
                resultArea.innerHTML = '<p class="text-red-500">Ocorreu um erro de comunicação.</p>';
            })
            .always(function () {
                loader.style.display = 'none';
            });
    }
};