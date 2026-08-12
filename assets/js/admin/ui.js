// Módulo para todas as funções que manipulam a interface (DOM)
const BCEK_Admin_UI = {
    init(state) {
        this.state = state;
    },

  
    updatePreviewSize() {
        const { previewContainer, baseImagePreview } = this.state.dom;

        if (!baseImagePreview || !baseImagePreview.naturalWidth || baseImagePreview.naturalWidth === 0) {
            this.state.currentScale = 1;
            previewContainer.style.width = '100%';
            previewContainer.style.height = '400px'; // Tamanho padrão
            return;
        }

        // 1. Calcula a proporção (ratio) correta para a imagem caber no ecrã.
        const containerWrapper = previewContainer.parentElement;
        const maxWidth = containerWrapper.offsetWidth;
        const maxHeight = window.innerHeight * 0.7;
        const ratio = Math.min(maxWidth / baseImagePreview.naturalWidth, maxHeight / baseImagePreview.naturalHeight, 1);
        
        // 2. Define o tamanho do contentor usando esta proporção.
        previewContainer.style.width = `${baseImagePreview.naturalWidth * ratio}px`;
        previewContainer.style.height = `${baseImagePreview.naturalHeight * ratio}px`;

        // 3. **A CORREÇÃO FINAL:** Usa a mesma proporção que acabámos de calcular como a nossa nova escala.
        // Isto evita o "problema de tempo" de ler o tamanho do DOM imediatamente após o alterar.
        this.state.currentScale = ratio;
        
        // 4. Redesenha todos os campos com a escala correta e consistente.
        Object.keys(this.state.fieldsState).forEach(id => this.updateElementFromState(id));
    },

    createField(options = {}) {
        this.state.fieldCounter++;
        const id = options.id || `new_${this.state.fieldCounter}`;
        const defaults = { 
            id: id, name: `Campo ${this.state.fieldCounter}`, 
            field_type: 'text', default_text: 'Novo Texto', 
            pos_x: 20, pos_y: 20, width: 200, height: 50, 
            font_color: '#000000', font_size: 20, 
            font_family: 'Montserrat', font_weight: '700', 
            alignment: 'left', z_index_order: 1 
        };
        const config = { ...defaults, ...options };
        
        this.state.fieldsState[config.id] = config;
        
        this.createFieldPreview(config);
        this.createFieldSettings(config);
    },

    deleteField(id) {
        if (String(id).indexOf('new_') === -1) {
            this.state.removedFieldIds.push(id);
        }
        delete this.state.fieldsState[id];
        document.getElementById(`field_${id}_preview`)?.remove();
        document.querySelector(`.accordion-item[data-field-id="${id}"]`)?.remove();
    },

    createFieldPreview(config) {
    const previewEl = document.createElement('div');
    previewEl.id = `field_${config.id}_preview`;
    previewEl.dataset.fieldId = config.id;
    previewEl.className = 'draggable-field';
    // A classe 'p-1' foi REMOVIDA daqui para garantir consistência
    previewEl.innerHTML = `<div class="field-content w-full h-full overflow-hidden"></div>
        <div class="resize-handle top-left"></div><div class="resize-handle top-right"></div>
        <div class="resize-handle bottom-left"></div><div class="resize-handle bottom-right"></div>
        <div class="resize-handle top-center"></div><div class="resize-handle bottom-center"></div>
        <div class="resize-handle middle-left"></div><div class="resize-handle middle-right"></div>
        <div class="debug-info"></div>`;
    this.state.dom.previewContainer.appendChild(previewEl);
    this.updateElementFromState(config.id);
    },

    createFieldSettings(config) {
        const settingsEl = document.createElement('div');
        settingsEl.className = 'accordion-item border-t pt-4 mt-4';
        settingsEl.dataset.fieldId = config.id;
        // Substitua o conteúdo de settingsEl.innerHTML por isto:
        settingsEl.innerHTML = `
            <div class="accordion-header">
                <span class="field-name-display">${config.name}</span>
                <div class="actions">
                    <button type="button" class="delete-field-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                    <span class="toggle-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </span>
                </div>
            </div>
            <div class="accordion-content">
                <div class="accordion-content-inner">
        
                    <div class="form-group-inline">
                        <div class="form-group" style="flex-basis: 60%;">
                            <label>Nome do campo</label>
                            <input type="text" data-config="name" value="${config.name}">
                        </div>
                        <div class="form-group" style="flex-basis: 40%;">
                            <label>Tipo de Campo</label>
                            <select data-config="field_type" class="field-type">
                                <option value="text">Texto</option>
                                <option value="image">Imagem</option>
                            </select>
                        </div>
                    </div>
        
                    <div class="text-controls">
                        <div class="form-group">
                            <label>Texto Padrão</label>
                            <textarea data-config="default_text" rows="3" style="resize: none; overflow-y: auto;">${config.default_text}</textarea>
                        </div>
                        <div class="form-group-inline">
                            <div class="form-group" style="flex-basis: 60%;">
                                <label>Fonte</label>
                                <select data-config="font_family" class="field-font-family"></select>
                            </div>
                            <div class="form-group" style="flex-basis: 40%;">
                                <label>Estilo</label>
                                <select data-config="font_weight" class="field-font-style"></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tamanho da Fonte</label>
                            <div class="font-size-control">
                                <div class="font-size-input-wrapper">
                                    <input type="number" min="8" max="200" value="${config.font_size}" data-config="font_size" class="field-font-size-input">
                                </div>
                                <input type="range" min="8" max="200" value="${config.font_size}" data-config="font_size" class="field-font-size-slider">
                            </div>
                        </div>
                        <div class="form-group-inline">
                            <div class="form-group color-control-wrapper" style="flex-basis: 30%;">
                                <label>Cor</label>
                                <input type="color" value="${config.font_color}" data-config="font_color">
                            </div>
                            <div class="form-group text-align-wrapper" style="flex-basis: 70%;">
                                <label>Alinhamento do Texto</label>
                                <div class="align-btn-group field-text-align">
                                    <button type="button" data-value="left" title="Esquerda"><svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg></button>
                                    <button type="button" data-value="center" title="Centro"><svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg></button>
                                    <button type="button" data-value="right" title="Direita"><svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 10a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg></button>
                                    <button type="button" data-value="justify" title="Justificado"><svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <div class="image-controls">
                        </div>
                    
                    <div class="form-group">
                        <label>Alinhamento do Campo</label>
                        <div class="align-btn-group field-align">
                            <button type="button" data-align="left" title="Alinhar à Esquerda"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a1 1 0 00-1 1v12a1 1 0 001 1h2a1 1 0 001-1V4a1 1 0 00-1-1H4zM16 5a1 1 0 00-1-1H9a1 1 0 00-1 1v10a1 1 0 001 1h6a1 1 0 001-1V5z"></path></svg></button>
                            <button type="button" data-align="h-center" title="Centralizar na Horizontal"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 3a1 1 0 00-1 1v12a1 1 0 001 1h2a1 1 0 001-1V4a1 1 0 00-1-1H9zM4 9a1 1 0 00-1 1v.01a1 1 0 102 0V10a1 1 0 00-1-1zm13 0a1 1 0 00-1 1v.01a1 1 0 102 0V10a1 1 0 00-1-1z"></path></svg></button>
                            <button type="button" data-align="right" title="Alinhar à Direita"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M14 3a1 1 0 00-1 1v12a1 1 0 001 1h2a1 1 0 001-1V4a1 1 0 00-1-1h-2zM3 5a1 1 0 00-1 1v10a1 1 0 001 1h6a1 1 0 001-1V5a1 1 0 00-1-1H3z"></path></svg></button>
                            <button type="button" data-align="top" title="Alinhar ao Topo"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM5 16a1 1 0 011-1h8a1 1 0 011 1v1a1 1 0 01-1 1H6a1 1 0 01-1-1v-1z"></path></svg></button>
                            <button type="button" data-align="v-center" title="Centralizar na Vertical"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 9a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V9zM4 3a1 1 0 011-1h.01a1 1 0 110 2H4a1 1 0 01-1-1zm12 0a1 1 0 011-1h.01a1 1 0 110 2H16a1 1 0 01-1-1z"></path></svg></button>
                            <button type="button" data-align="bottom" title="Alinhar ao Fundo"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 14a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zM5 3a1 1 0 011-1h8a1 1 0 011 1v1a1 1 0 01-1 1H6a1 1 0 01-1-1V3z"></path></svg></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Camadas</label>
                        <select data-config="z_index_order">
                            <option value="1">Por Cima da Imagem Base</option>
                            <option value="0">Por Baixo da Imagem Base</option>
                        </select>
                    </div>
        
                </div>
            </div>
        `;
        this.state.dom.fieldsList.appendChild(settingsEl);

        const fontFamilySelect = settingsEl.querySelector('.field-font-family');
        Object.keys(this.state.googleFonts).forEach(font => { fontFamilySelect.add(new Option(font, font)); });
        
        settingsEl.querySelector('.field-type').value = config.field_type;
        fontFamilySelect.value = config.font_family;
        
        this.populateFontStyleSelect(settingsEl, config.font_family, config.font_weight);

        settingsEl.querySelector('[data-config="z_index_order"]').value = config.z_index_order;
        const alignBtn = settingsEl.querySelector(`.align-btn-group.field-text-align button[data-value="${config.alignment}"]`);
        if(alignBtn) alignBtn.classList.add('is-active');

        this.updateFieldTypeUI(settingsEl);
    },

    populateFontStyleSelect(settingsEl, fontFamily, selectedStyle) {
        const fontStyleSelect = settingsEl.querySelector('.field-font-style');
        fontStyleSelect.innerHTML = '';
        const styles = this.state.googleFonts[fontFamily] || [];
        styles.forEach(style => {
            const option = new Option(style.name, style.value);
            fontStyleSelect.add(option);
        });
        fontStyleSelect.value = selectedStyle;
    },

    // Substitua a sua função updateFieldTypeUI por esta versão com debug
    updateFieldTypeUI(settingsEl) {
    const fieldType = settingsEl.querySelector('.field-type').value;
    const textControls = settingsEl.querySelector('.text-controls');
    const imageControls = settingsEl.querySelector('.image-controls');

    // Verifica se ambos os contentores foram encontrados antes de continuar
    if (textControls && imageControls) {
        // Em vez de usar classList.toggle, vamos alterar o estilo 'display' diretamente.
        // Isto é mais forte e garante que o elemento é removido do layout.
        if (fieldType === 'text') {
            textControls.style.display = 'block'; // Mostra os controlos de texto
            imageControls.style.display = 'none';  // Esconde os controlos de imagem
        } else {
            textControls.style.display = 'none';   // Esconde os controlos de texto
            imageControls.style.display = 'block'; // Mostra os controlos de imagem
        }
    }
},
    


    updateElementFromState(id) {
        const config = this.state.fieldsState[id];
        const element = document.getElementById(`field_${id}_preview`);
        if (!element || !config) return;

        const scale = this.state.currentScale;
        if (!scale || scale <= 0) return;

        // Estilos do contêiner principal (draggable)
        element.style.left = `${config.pos_x * scale}px`;
        element.style.top = `${config.pos_y * scale}px`;
        element.style.width = `${config.width * scale}px`;
        element.style.height = `${config.height * scale}px`;
        element.style.zIndex = parseInt(config.z_index_order, 10) > 0 ? 10 : 5;

        const contentEl = element.querySelector('.field-content');
        
        // Limpa e redefine estilos para todos os tipos de campo
        contentEl.innerHTML = '';
        const baseStyles = {
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
            overflow: 'hidden',
            padding: '0px'
        };
        for (const property in baseStyles) {
            contentEl.style[property] = baseStyles[property];
        }

        if (config.field_type === 'text') {
            // --- INÍCIO DA CORREÇÃO FINAL DE POSICIONAMENTO ---
            const scaledFontSize = config.font_size * scale;
            const lineHeight = parseFloat(config.line_height_multiplier) || 1.3;
            
            // Calcula o deslocamento vertical para anular o espaço extra da line-height.
            // O valor 0.21 é um fator de ajuste comum para alinhar texto de forma precisa.
            const verticalOffset = (scaledFontSize * (lineHeight - 1)) / 2 - (scaledFontSize * 0.21);
            
            const fontStyleValue = String(config.font_weight || '400');
            let finalFontWeight = fontStyleValue.replace('i', '');

            // Se o peso for Regular (400), usa Medium (500) para a renderização
            if (finalFontWeight === '400') {
                finalFontWeight = '500';
            }

            const textStyles = {
                display: 'block',
                // O padding lateral é mantido, mas o vertical é zerado.
                padding: `0 ${4 * scale}px`, 
                // A margem negativa puxa o texto para cima, alinhando-o visualmente com o Canvas.
                margin: `-${verticalOffset}px 0 0 0`,
                overflow: 'hidden',
                lineHeight: `${lineHeight}`,
                color: config.font_color,
                fontSize: `${scaledFontSize}px`,
                fontFamily: `'${config.font_family}', sans-serif`,
                fontWeight: String(config.font_weight || '400').replace('i', ''),
                fontStyle: String(config.font_weight || '').includes('i') ? 'italic' : 'normal',
                textAlign: config.alignment,
                backgroundColor: 'transparent',
                border: 'none',
                boxSizing: 'border-box'
            };
            // Aplica os estilos de forma segura
            for (const property in textStyles) {
                contentEl.style[property] = textStyles[property];
            }

            const textToRender = (config.default_text || '').replace(/\n/g, ' <br> ');
            const words = textToRender.split(/\s+/);

            let tempContainer = document.createElement('div');
            tempContainer.style.visibility = 'hidden';
            tempContainer.style.position = 'absolute';
            tempContainer.style.width = contentEl.clientWidth + 'px';
            
            // Aplica os mesmos estilos ao contêiner temporário
            for (const property in textStyles) {
                tempContainer.style[property] = textStyles[property];
            }
            
            // CORREÇÃO: Em vez de document.body, injetamos no "element" (que é a caixa 
            // position: absolute sendo arrastada). Isso blinda a performance e impede Reflow global.
            element.appendChild(tempContainer);
            
            for (const word of words) {
                if (word === '<br>') {
                    tempContainer.innerHTML += '<br>';
                    continue;
                }
                const currentContent = tempContainer.innerHTML;
                tempContainer.innerHTML += (currentContent ? ' ' : '') + word;
                
                if (tempContainer.scrollHeight > contentEl.clientHeight) {
                    tempContainer.innerHTML = currentContent;
                    break;
                }
            }
            contentEl.innerHTML = tempContainer.innerHTML;
            element.removeChild(tempContainer);

        } else { // Para o tipo 'image'
            const imageStyles = {
                color: '#3B82F6',
                fontSize: `${12 * scale}px`,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                border: '2px dashed rgba(59, 130, 246, 0.5)',
                borderRadius: config.container_shape === 'circle' ? '50%' : '0'
            };
            // Aplica os estilos de forma segura
            for (const property in imageStyles) {
                contentEl.style[property] = imageStyles[property];
            }
            contentEl.innerHTML = 'Área para Imagem';
        }
        
        this.drawDebugInfo(id);
    },
    
    drawDebugInfo(id) {
        const config = this.state.fieldsState[id];
        const element = document.getElementById(`field_${id}_preview`);
        if (!element || !config) return;

        const debugEl = element.querySelector('.debug-info');
        if (debugEl) {
            const scale = this.state.currentScale.toFixed(4);
            debugEl.innerText = `ID: ${id}\nX: ${Math.round(config.pos_x)}, Y: ${Math.round(config.pos_y)}\nW: ${Math.round(config.width)}, H: ${Math.round(config.height)}\nScale: ${scale}`;
        }
    },
    
    /**
     * ADICIONADO: Esta é a função que estava em falta.
     * Mostra os controlos de campos após o upload da imagem base.
     */
    showFieldControls() {
        const prompt = document.getElementById('upload-prompt');
        const { fieldsList, addFieldBtn } = this.state.dom;

        if (prompt) prompt.style.display = 'none';
        if (fieldsList) fieldsList.style.display = 'block';
        if (addFieldBtn) addFieldBtn.style.display = 'flex';
    },
    
    updateSliderBackground(slider) {
    const percentage = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
    slider.style.background = `linear-gradient(to right, #2563EB ${percentage}%, #E5E7EB ${percentage}%)`;
    }
    
};