// Módulo para gerir todos os event listeners da interface
const BCEK_Admin_Events = {
    init(state, ui) {
        this.state = state;
        this.ui = ui;
        
        // --- A CORREÇÃO ESTÁ AQUI ---
        // Em vez de abrir a biblioteca de mídia, vamos acionar o input de arquivo escondido
        this.state.dom.baseImageUpload?.addEventListener('click', (e) => {
            e.preventDefault();
            // Cria um input de arquivo dinâmico para o usuário selecionar a imagem
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/png, image/jpeg, image/gif';
            fileInput.style.display = 'none';
            
            // Quando o usuário selecionar um arquivo, chama a função de upload
            fileInput.addEventListener('change', (event) => this.handleBaseImageUpload(event));
            
            document.body.appendChild(fileInput);
            fileInput.click();
            document.body.removeChild(fileInput);
        });

        this.state.dom.addFieldBtn?.addEventListener('click', () => this.handleAddField());
        this.state.dom.saveButton?.addEventListener('click', () => this.handleSaveTemplate());
        
        if (this.state.dom.fieldsList) {
            this.state.dom.fieldsList.addEventListener('click', (e) => this.handleFieldsListClick(e));
            this.state.dom.fieldsList.addEventListener('input', (e) => this.handleFieldsListInput(e));
            this.state.dom.fieldsList.addEventListener('change', (e) => this.handleFieldsListChange(e));
        }
        
        window.addEventListener('resize', () => {
            if (this.state.imageIsLoaded) {
                this.ui.updatePreviewSize(this.state.dom.baseImagePreview.src);
            }
        });
    },

    /**
     * Manipula o upload da imagem via AJAX, replicando a lógica original do seu plugin.
     */
    handleBaseImageUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('action', 'bcek_handle_base_image_upload');
        formData.append('nonce', this.state.bcekData.nonce);
        formData.append('base_image_file', file);

        // MOSTRA O LOADER antes de iniciar o upload
        this.state.dom.imageUploadLoader.style.display = 'block';

        jQuery.ajax({
            url: this.state.bcekData.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: response => {
                if (response.success) {
                    const { id, url } = response.data;
                    this.state.dom.baseImageIdInput.value = id;
                    this.state.dom.baseImageUrlInput.value = url;
                    this.state.dom.baseImagePreview.src = url; 
                    
                    this.state.imageIsLoaded = true;
                    this.ui.showFieldControls();
                } else {
                    alert('Erro ao fazer upload da imagem: ' + response.data.message);
                }
            },
            error: () => {
                alert('Erro de comunicação ao fazer upload da imagem.');
            },
            complete: () => {
                // ESCONDE O LOADER quando o processo termina (sucesso ou erro)
                this.state.dom.imageUploadLoader.style.display = 'none';
            }
        });
    },

    // (O resto do seu arquivo events.js permanece o mesmo, com as funções handleAddField, handleSaveTemplate, etc.)
    // As funções abaixo são as que você já tem e estão corretas.

    handleAddField() {
        if (!this.state.imageIsLoaded) {
            alert('Por favor, carregue uma imagem base primeiro.');
            return;
        }
        this.ui.createField();
    },

    handleFieldsListClick(e) {
        const accordionHeader = e.target.closest('.accordion-header');
        if (accordionHeader) {
            const content = accordionHeader.nextElementSibling;
            const icon = accordionHeader.querySelector('svg:last-child');
            const isOpening = !content.style.maxHeight || content.style.maxHeight === "0px";
            document.querySelectorAll('.accordion-content').forEach(c => { c.style.maxHeight = "0px"; });
            document.querySelectorAll('.accordion-header svg:last-child').forEach(i => { i.style.transform = 'rotate(0deg)'; });
            if (isOpening) {
                content.style.maxHeight = content.scrollHeight + "px";
                if(icon) icon.style.transform = 'rotate(180deg)';
            }
        }
        const deleteBtn = e.target.closest('.delete-field-btn');
        if (deleteBtn) {
            const id = deleteBtn.closest('.accordion-item').dataset.fieldId;
            if (confirm('Tem certeza que deseja remover este campo?')) {
                this.ui.deleteField(id);
            }
        }
        // Captura o clique em qualquer variação de botão de alinhamento de texto
        const alignBtn = e.target.closest('.align-btn-group.field-text-align button, .field-text-align button');
        if (alignBtn) {
            const item = alignBtn.closest('.accordion-item');
            if (item && item.dataset.fieldId) {
                const id = item.dataset.fieldId;
                
                // 1. Atualiza visualmente os botões
                alignBtn.parentElement.querySelectorAll('button').forEach(b => b.classList.remove('is-active'));
                alignBtn.classList.add('is-active');

                // 2. Obtém o alinhamento correto de forma segura
                const val = alignBtn.dataset.value || alignBtn.dataset.align || 'left';
                const cleanAlign = (val === 'h-center' || val === 'center') ? 'center' : (val === 'right' ? 'right' : 'left');

                // 3. Atualiza o estado apenas se o campo existir
                if (this.state.fieldsState[id]) {
                    this.state.fieldsState[id].alignment = cleanAlign;

                    // 4. Aplica diretamente no elemento visual da preview sem recriar o texto
                    const previewEl = document.getElementById(`field_${id}_preview`);
                    if (previewEl) {
                        previewEl.style.textAlign = cleanAlign;
                    }
                }
            }
        }
        
        const fieldAlignBtn = e.target.closest('.field-align button');
        if(fieldAlignBtn) {
            const id = fieldAlignBtn.closest('.accordion-item').dataset.fieldId;
            const alignType = fieldAlignBtn.dataset.align;
            const img = this.state.dom.baseImagePreview;
            const containerWidth = img.naturalWidth;
            const containerHeight = img.naturalHeight;
            if (!containerWidth || !containerHeight) return;
            const fieldConfig = this.state.fieldsState[id];
            switch(alignType) {
                case 'left': fieldConfig.pos_x = 0; break;
                case 'h-center': fieldConfig.pos_x = (containerWidth / 2) - (fieldConfig.width / 2); break;
                case 'right': fieldConfig.pos_x = containerWidth - fieldConfig.width; break;
                case 'top': fieldConfig.pos_y = 0; break;
                case 'v-center': fieldConfig.pos_y = (containerHeight / 2) - (fieldConfig.height / 2); break;
                case 'bottom': fieldConfig.pos_y = containerHeight - fieldConfig.height; break;
            }
            this.ui.updateElementFromState(id);
        }
    },

    handleFieldsListInput(e) {
        const item = e.target.closest('.accordion-item');
        if (!item) return;
        const id = item.dataset.fieldId;
        const configKey = e.target.dataset.config;
        if (e.target.matches('.field-font-size-slider') || e.target.matches('.field-font-size-input')) {
            const value = e.target.value;
            const slider = item.querySelector('.field-font-size-slider');
            item.querySelector('.field-font-size-input').value = value;
            slider.value = value;
            this.ui.updateSliderBackground(slider); // <<< LINHA ADICIONADA
            }
        if (this.state.fieldsState[id] && configKey) {
            this.state.fieldsState[id][configKey] = e.target.value;
            if (configKey === 'name') {
                item.querySelector('.field-name-display').textContent = e.target.value;
            }
            this.ui.updateElementFromState(id);
        }
    },
    
    handleFieldsListChange(e) {
    // 1. Identifica o item do acordeão que foi alterado
    const item = e.target.closest('.accordion-item');
    if (!item) return;

    const id = item.dataset.fieldId;
    const configKey = e.target.dataset.config;

    // 2. Garante que o campo existe no nosso estado antes de continuar
    if (this.state.fieldsState[id] && configKey) {
        
        // 3. Atualiza o estado do campo com o novo valor do input
        this.state.fieldsState[id][configKey] = e.target.value;

        // --- LÓGICA ESPECIAL PARA CAMPOS ESPECÍFICOS ---

        // Se o campo alterado foi a família da fonte...
        if (e.target.matches('.field-font-family')) {
            const newFont = e.target.value;
            // ...atualiza as opções do seletor de "Estilo"
            const styleSelect = item.querySelector('.field-font-style');
            const defaultStyle = this.state.googleFonts[newFont]?.[0]?.value || '400';
            this.ui.populateFontStyleSelect(item, newFont, defaultStyle);
            this.state.fieldsState[id].font_weight = styleSelect.value;
        }

        // Se o campo alterado foi o tipo (Texto/Imagem)...
        if (e.target.matches('.field-type')) {
            // ...esconde/mostra os controlos relevantes...
            this.ui.updateFieldTypeUI(item);

            // ...e recalcula a altura do acordeão para se ajustar.
            const content = item.querySelector('.accordion-content');
            if (content.style.maxHeight && content.style.maxHeight !== '0px') {
                setTimeout(() => {
                    content.style.maxHeight = content.scrollHeight + 'px';
                }, 50); // Timeout para garantir que o DOM atualizou
            }
        }

        // 4. Por fim, redesenha o preview do campo com os novos dados
        this.ui.updateElementFromState(id);
        }
    },
    
   
    /**
     * Coleta todos os dados e envia via AJAX para salvar no WordPress.
     */
    handleSaveTemplate() {
        this.state.dom.saveButton.textContent = 'A Salvar...';
        this.state.dom.saveButton.disabled = true;

        const fieldsArray = Object.values(this.state.fieldsState).map(field => {
            return {
                id: String(field.id).includes('new_') ? 0 : field.id,
                name: field.name, field_type: field.field_type,
                pos_x: Math.round(field.pos_x), pos_y: Math.round(field.pos_y),
                width: Math.round(field.width), height: Math.round(field.height),
                font_family: field.font_family, font_weight: field.font_weight,
                font_size: field.font_size, font_color: field.font_color,
                alignment: field.alignment || 'left', // Garante valor válido
                default_text: field.default_text,
                z_index_order: field.z_index_order
            };
        });
        const dataToSend = {
            action: 'bcek_save_template_data',
            nonce: this.state.bcekData.nonce,
            template_id: this.state.bcekData.template ? this.state.bcekData.template.template_id : 0,
            name: this.state.dom.templateNameInput.value,
            base_image_id: this.state.dom.baseImageIdInput.value,
            base_image_url: this.state.dom.baseImageUrlInput.value,
            // --- CORREÇÃO ADICIONADA AQUI ---
            category_id: document.getElementById('template-category').value,
            // --- FIM DA CORREÇÃO ---
            fields: fieldsArray,
            removed_ids: this.state.removedFieldIds
        };

        jQuery.post(this.state.bcekData.ajax_url, dataToSend)
            .done(response => {
                if (response.success) {
                    alert(response.data.message || 'Template salvo com sucesso!');
                    
                    if (response.data.is_new) {
                        if (response.data.new_template_id) {
                            const newUrl = new URL(window.location.href);
                            newUrl.searchParams.set('action', 'edit');
                            newUrl.searchParams.set('template_id', response.data.new_template_id);
                            window.location.href = newUrl.href;
                        } else {
                            alert('Erro: Não foi possível obter o ID do novo template. A página será recarregada.');
                            window.location.reload();
                        }
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert('Erro ao salvar: ' + (response.data.message || 'Ocorreu um erro desconhecido.'));
                    this.state.dom.saveButton.textContent = 'Salvar Template';
                    this.state.dom.saveButton.disabled = false;
                }
            })
            .fail(() => {
                alert('Ocorreu um erro de comunicação com o servidor.');
                this.state.dom.saveButton.textContent = 'Salvar Template';
                this.state.dom.saveButton.disabled = false;
            });
    }
};