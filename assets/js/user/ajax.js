// assets/js/user/ajax.js
const BCEK_User_Ajax = {
    init(state) {
        this.state = state;
    },

    generateImage(format) {
        const { loader, resultArea } = this.state.dom;
        const { nonce, ajax_url, template } = this.state.bcekData;

        loader.style.display = 'block';
        resultArea.innerHTML = '';

        const dataToSend = {
            action: 'bcek_generate_image',
            nonce: nonce,
            template_id: template.template_id,
            user_inputs: this.state.userInputs,
            user_filename: document.getElementById('bcek_filename').value,
            format: format
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
