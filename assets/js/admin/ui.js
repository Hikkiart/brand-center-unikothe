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
        previewEl.innerHTML = `<div class="field-content w-full h-full overflow-hidden p-1"></div>
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
        settingsEl.innerHTML = `
            <div class="accordion-header w-full flex justify-between items-center py-2 cursor-pointer">
                <span class="font-semibold text-gray-800 field-name-display">${config.name}</span>
                <div class="flex items-center gap-2">
                    <button type="button" class="delete-field-btn text-red-500 hover:text-red-700 p-1 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></svg></button>
                    <svg class="w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
            <div class="accordion-content space-y-4 pt-2" style="max-height: 0; overflow: hidden;">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nome do Campo</label><input type="text" value="${config.name}" data-config="name" class="w-full bg-gray-100 rounded-lg p-2"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Campo</label><select data-config="field_type" class="field-type w-full bg-gray-100 rounded-lg p-2"><option value="text">Texto</option><option value="image">Imagem</option></select></div>
                <div class="text-controls space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Texto Padrão</label><textarea data-config="default_text" class="w-full bg-gray-100 rounded-lg p-2">${config.default_text}</textarea></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Fonte</label><select data-config="font_family" class="field-font-family w-full bg-gray-100 rounded-lg p-2"></select></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Estilo</label><select data-config="font_weight" class="field-font-style w-full bg-gray-100 rounded-lg p-2"></select></div>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex justify-between items-center">
                            <label class="block text-sm font-medium text-gray-700">Tamanho da Fonte</label>
                            <input type="number" min="8" max="200" value="${config.font_size}" data-config="font_size" class="w-20 bg-gray-100 rounded-lg p-1 text-center text-sm font-mono field-font-size-input">
                        </div>
                        <input type="range" min="8" max="200" value="${config.font_size}" data-config="font_size" class="w-full mt-1 field-font-size-slider">
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Cor</label><input type="color" value="${config.font_color}" data-config="font_color" class="w-full h-10 p-1 bg-gray-100 cursor-pointer"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Alinhamento do Texto</label><div class="align-btn-group field-text-align grid grid-cols-4 rounded-lg bg-gray-100 p-1">
                        <button type="button" data-value="left" title="Esquerda" class="p-1"><svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg></button>
                        <button type="button" data-value="center" title="Centro" class="p-1"><svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg></button>
                        <button type="button" data-value="right" title="Direita" class="p-1"><svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 10a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg></button>
                        <button type="button" data-value="justify" title="Justificado" class="p-1"><svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg></button>
                    </div></div>
                </div>
                <div class="image-controls hidden space-y-4">
                    <p class="text-sm text-gray-500">A imagem do utilizador será inserida aqui.</p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Alinhamento do Campo</label><div class="align-btn-group field-align grid grid-cols-3 rounded-lg bg-gray-100 p-1">
                    <button type="button" data-align="left" title="Alinhar à Esquerda" class="p-1 rounded-md flex justify-center items-center"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a1 1 0 00-1 1v12a1 1 0 001 1h2a1 1 0 001-1V4a1 1 0 00-1-1H4zM16 5a1 1 0 00-1-1H9a1 1 0 00-1 1v10a1 1 0 001 1h6a1 1 0 001-1V5z"></path></svg></button>
                    <button type="button" data-align="h-center" title="Centralizar na Horizontal" class="p-1 rounded-md flex justify-center items-center"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 3a1 1 0 00-1 1v12a1 1 0 001 1h2a1 1 0 001-1V4a1 1 0 00-1-1H9zM4 9a1 1 0 00-1 1v.01a1 1 0 102 0V10a1 1 0 00-1-1zm13 0a1 1 0 00-1 1v.01a1 1 0 102 0V10a1 1 0 00-1-1z"></path></svg></button>
                    <button type="button" data-align="right" title="Alinhar à Direita" class="p-1 rounded-md flex justify-center items-center"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M14 3a1 1 0 00-1 1v12a1 1 0 001 1h2a1 1 0 001-1V4a1 1 0 00-1-1h-2zM3 5a1 1 0 00-1 1v10a1 1 0 001 1h6a1 1 0 001-1V5a1 1 0 00-1-1H3z"></path></svg></button>
                    <button type="button" data-align="top" title="Alinhar ao Topo" class="p-1 rounded-md flex justify-center items-center"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM5 16a1 1 0 011-1h8a1 1 0 011 1v1a1 1 0 01-1 1H6a1 1 0 01-1-1v-1z"></path></svg></button>
                    <button type="button" data-align="v-center" title="Centralizar na Vertical" class="p-1 rounded-md flex justify-center items-center"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 9a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V9zM4 3a1 1 0 011-1h.01a1 1 0 110 2H4a1 1 0 01-1-1zm12 0a1 1 0 011-1h.01a1 1 0 110 2H16a1 1 0 01-1-1z"></path></svg></button>
                    <button type="button" data-align="bottom" title="Alinhar ao Fundo" class="p-1 rounded-md flex justify-center items-center"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 14a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zM5 3a1 1 0 011-1h8a1 1 0 011 1v1a1 1 0 01-1 1H6a1 1 0 01-1-1V3z"></path></svg></button>
                </div></div>
                <div><label class="block text-sm">Camada</label><select data-config="z_index_order" class="w-full bg-gray-100 rounded-lg p-2"><option value="1">Por Cima da Imagem Base</option><option value="0">Por Baixo da Imagem Base</option></select></div>
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

    updateFieldTypeUI(settingsEl) {
        const fieldType = settingsEl.querySelector('.field-type').value;
        settingsEl.querySelector('.text-controls').classList.toggle('hidden', fieldType !== 'text');
        settingsEl.querySelector('.image-controls').classList.toggle('hidden', fieldType !== 'image');
    },
    
    /**
     * NOVO: Calcula a quebra de linha para um texto, respeitando a largura da caixa.
     * Esta função mede o texto palavra por palavra para evitar quebras a meio.
     */
    _calculateWrappedText(config) {
        const text = config.default_text || '';
        const words = text.split(' ');
        if (words.length === 0) return [];

        // Cria um elemento temporário e invisível para medir o texto
        const measurer = document.createElement('span');
        measurer.style.position = 'absolute';
        measurer.style.visibility = 'hidden';
        measurer.style.fontFamily = `'${config.font_family}', sans-serif`;
        measurer.style.fontSize = `${config.font_size}px`; // Mede em escala 1:1
        const fontStyle = String(config.font_weight || '400');
        measurer.style.fontWeight = fontStyle.replace('i', '');
        measurer.style.fontStyle = fontStyle.includes('i') ? 'italic' : 'normal';
        document.body.appendChild(measurer);

        let lines = [];
        let currentLine = words[0];

        for (let i = 1; i < words.length; i++) {
            measurer.innerText = currentLine + ' ' + words[i];
            // Verifica se a linha com a nova palavra ultrapassa a largura da caixa
            if (measurer.offsetWidth > config.width) {
                lines.push(currentLine); // Guarda a linha anterior
                currentLine = words[i]; // Começa uma nova linha
            } else {
                currentLine += ' ' + words[i]; // Adiciona a palavra à linha atual
            }
        }
        lines.push(currentLine); // Adiciona a última linha

        // Remove o medidor do DOM
        document.body.removeChild(measurer);
        return lines;
    },

    updateElementFromState(id) {
        const config = this.state.fieldsState[id];
        const element = document.getElementById(`field_${id}_preview`);
        if (!element || !config) return;

        const scale = this.state.currentScale;
        if (!scale || scale <= 0) return;

        // Estilos da caixa principal do preview (draggable)
        element.style.left = `${config.pos_x * scale}px`;
        element.style.top = `${config.pos_y * scale}px`;
        element.style.width = `${config.width * scale}px`;
        element.style.height = `${config.height * scale}px`;
        element.style.zIndex = parseInt(config.z_index_order, 10) > 0 ? 10 : -1;
        element.style.borderRadius = config.container_shape === 'circle' ? '50%' : '0.5rem';

        const contentEl = element.querySelector('.field-content');
        
        if (config.field_type === 'text') {
            // --- NOVA LÓGICA DE TEXTO ---

            // 1. Calcula as linhas com base na largura da caixa, sem quebrar palavras
            let lines = this._calculateWrappedText(config);

            // 2. Calcula a altura de uma linha e o espaçamento
            const lineHeight = config.font_size * 1.3; // Espaçamento padrão de 130%
            let totalTextHeight = lines.length * lineHeight;

            // 3. Oculta as linhas que ultrapassam a altura da caixa
            while (totalTextHeight > config.height && lines.length > 0) {
                lines.pop(); // Remove a última linha
                totalTextHeight = lines.length * lineHeight;
            }

            // 4. Aplica os estilos e o texto final
            contentEl.innerHTML = lines.join('<br>'); // Usa <br> para quebras de linha
            contentEl.style.color = config.font_color;
            contentEl.style.fontSize = `${config.font_size * scale}px`;
            contentEl.style.lineHeight = `${1.3 * scale}`; // Aplica o espaçamento com escala
            contentEl.style.fontFamily = `'${config.font_family}', sans-serif`;
            const fontStyle = String(config.font_weight || '400');
            contentEl.style.fontWeight = fontStyle.replace('i', '');
            contentEl.style.fontStyle = fontStyle.includes('i') ? 'italic' : 'normal';
            contentEl.style.textAlign = config.alignment;
            
            // Garante os estilos corretos para a caixa de texto
            contentEl.style.display = 'block';
            contentEl.style.wordWrap = '';
            contentEl.style.whiteSpace = '';
            contentEl.style.backgroundColor = 'transparent';
            contentEl.style.border = 'none';

        } else { // Estilos para campo de imagem
            contentEl.innerHTML = 'Área para Imagem'; // Usa innerHTML para consistência
            contentEl.style.color = '#3B82F6';
            contentEl.style.fontSize = `${12 * scale}px`;
            contentEl.style.backgroundColor = 'rgba(59, 130, 246, 0.2)';
            contentEl.style.border = '2px dashed rgba(59, 130, 246, 0.5)';
            contentEl.style.borderRadius = config.container_shape === 'circle' ? '50%' : '0';
            contentEl.style.display = 'flex';
            contentEl.style.justifyContent = 'center';
            contentEl.style.alignItems = 'center';
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
    }
};