(function(E, $) { // E é BCEK_User_Editor, $ é jQuery
    'use strict';

    E.events = {
        init: function() {
            // Eventos de texto
            E.dom.fieldsContainer.on('input', '.bcek-dynamic-text-input', this.handleTextInput);
            
            // --- NOVOS EVENTOS DE IMAGEM ---
            E.dom.fieldsContainer.on('change', '.bcek-dynamic-image-input', this.handleImageSelect);
            E.dom.confirmCropBtn.on('click', this.handleCropConfirm);
            E.dom.cancelCropBtn.on('click', this.closeCropperModal);
            E.dom.generateBtn.on('click', this.handleGenerateClick);
        },

        handleTextInput: function() {
            // ... (esta função permanece igual)
        },

        /**
         * --- NOVA FUNÇÃO ---
         * Chamado quando o utilizador seleciona um ficheiro de imagem.
         */
        handleImageSelect: function(event) {
            const fieldId = $(this).data('field-id');
            const file = event.target.files[0];

            if (!file || !fieldId) return;

            // Usa o FileReader para ler o ficheiro como um URL
            const reader = new FileReader();
            reader.onload = function(e) {
                E.dom.imageToCrop.src = e.target.result;
                E.activeImageFieldId = fieldId; // Guarda o ID do campo que estamos a editar
                E.events.openCropperModal();
            };
            reader.readAsDataURL(file);
        },
        
        /**
         * --- NOVA FUNÇÃO ---
         * Abre a janela (modal) de recorte e inicializa o Cropper.js.
         */
        openCropperModal: function() {
            E.dom.cropperModal.show();
            
            // Encontra os dados do campo para obter a proporção
            const field = E.config.fields.find(f => f.field_id == E.activeImageFieldId);
            const aspectRatio = field ? field.width / field.height : 1;
            
            // Destrói qualquer instância anterior do cropper
            if (E.cropper) {
                E.cropper.destroy();
            }

            // Inicializa o Cropper.js na imagem
            E.cropper = new Cropper(E.dom.imageToCrop, {
                aspectRatio: aspectRatio,
                viewMode: 1,
                background: false,
                responsive: true,
                autoCropArea: 0.9,
            });
        },

        /**
         * --- NOVA FUNÇÃO ---
         * Fecha a janela de recorte.
         */
        closeCropperModal: function() {
            E.dom.cropperModal.hide();
            if (E.cropper) {
                E.cropper.destroy();
            }
        },

        /**
         * --- NOVA FUNÇÃO ---
         * Chamado quando o utilizador confirma o recorte.
         */
        handleCropConfirm: function() {
            if (!E.cropper) return;
            
            // Obtém o canvas recortado do Cropper.js
            const croppedCanvas = E.cropper.getCroppedCanvas();

            // Converte o canvas para um elemento <img>
            const croppedImage = new Image();
            croppedImage.src = croppedCanvas.toDataURL();
            
            croppedImage.onload = function() {
                // Guarda a imagem recortada no nosso estado
                const fieldId = E.activeImageFieldId;
                if (!E.userInputs[fieldId]) E.userInputs[fieldId] = {};
                E.userInputs[fieldId].image = croppedImage;
                
                // Redesenha o preview com a nova imagem
                E.ui.drawPreview();
                E.events.closeCropperModal();
            };
        },
        
        /**
         * --- NOVA FUNÇÃO ---
         * Chamado quando o utilizador clica em "Gerar Imagem".
         */
        handleGenerateClick: function() {
            E.dom.loader.show();
            E.dom.generateBtn.prop('disabled', true).text('A Gerar...');

            // Prepara os dados para enviar para o servidor
            const dataToSend = {
                action: 'bcek_generate_user_image',
                nonce: E.config.nonce,
                template_id: E.config.template.template_id,
                user_inputs: {}
            };

            // Converte as imagens recortadas para Base64 para envio
            for (const fieldId in E.userInputs) {
                dataToSend.user_inputs[fieldId] = {};
                if (E.userInputs[fieldId].text) {
                    dataToSend.user_inputs[fieldId].text = E.userInputs[fieldId].text;
                }
                if (E.userInputs[fieldId].image) {
                    // O 'image' é um elemento <img>, o seu src tem os dados em Base64
                    dataToSend.user_inputs[fieldId].image = E.userInputs[fieldId].image.src;
                }
            }
            
            // Faz a chamada AJAX
            $.post(E.config.ajax_url, dataToSend)
                .done(response => {
                    if (response.success) {
                        // Cria um link temporário e clica nele para iniciar o download
                        const link = document.createElement('a');
                        link.href = response.data.imageUrl;
                        link.download = `template-${E.config.template.template_id}.png`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert('Erro ao gerar imagem: ' + response.data.message);
                    }
                })
                .fail(() => {
                    alert('Erro de comunicação com o servidor.');
                })
                .always(() => {
                    E.dom.loader.hide();
                    E.dom.generateBtn.prop('disabled', false).text('Gerar Imagem');
                });
        }
    };

})(BCEK_User_Editor, jQuery);