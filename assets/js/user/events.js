// assets/js/user/events.js
const BCEK_User_Events = {
    init(state, ui, ajax) {
        this.state = state;
        this.ui = ui;
        this.ajax = ajax;
        
        this.state.dom.baseImg.addEventListener('load', () => this.onBaseImageLoad());
        if (this.state.dom.baseImg.complete && this.state.dom.baseImg.naturalWidth > 0) {
            this.onBaseImageLoad();
        }

        window.addEventListener('resize', () => this.ui.drawCanvas());
        
        const wrapper = this.state.dom.wrapper;
        // Ouve múltiplos eventos no editor de texto para garantir a atualização em tempo real
        wrapper.addEventListener('input', (e) => this.handleFormInput(e));
        wrapper.addEventListener('keyup', (e) => this.handleFormKeyUp(e));
        wrapper.addEventListener('mouseup', (e) => this.handleFormMouseUp(e));

        wrapper.addEventListener('change', (e) => this.handleFileChange(e));
        wrapper.addEventListener('click', (e) => this.handleButtonClick(e));
        
        document.getElementById('bcek-confirm-crop-btn').addEventListener('click', () => this.ui.confirmCrop());
        document.getElementById('bcek-cancel-crop-btn').addEventListener('click', () => this.ui.cancelCrop());

        window.addEventListener('click', (e) => {
            const exportBtn = document.getElementById('export-btn');
            const exportDropdown = document.getElementById('export-dropdown');
            if (exportBtn && !exportBtn.contains(e.target)) {
                exportDropdown.classList.add('hidden');
            }
        });
    },

    async onBaseImageLoad() {
        // Esta função permanece igual, está correta.
        const { bcekData } = this.state;
        const fontFamilies = new Set(bcekData.fields.filter(f => f.field_type === 'text').map(f => f.font_family));
        
        for (const family of fontFamilies) {
            const fontFace = new FontFace(family, `url(${bcekData.fonts_url}${family}.ttf)`);
            try {
                await fontFace.load();
                document.fonts.add(fontFace);
            } catch (e) {
                console.error(`Falha ao carregar a fonte: ${family}`, e);
            }
        }
        
        this.updateAllInputs();
    },

    handleFormInput(e) {
        const target = e.target;
        if (target.matches('.bcek-rich-text-input, .bcek-dynamic-fontsize-slider')) {
            this.updateAllInputs();

            // NOVO: Lógica para ajustar a altura do acordeão
            if (target.matches('.bcek-rich-text-input')) {
                const accordionContent = target.closest('.accordion-content');
                const accordionItem = target.closest('.accordion-item');
                // Só ajusta a altura se o acordeão estiver aberto
                if (accordionContent && accordionItem.classList.contains('open')) {
                    accordionContent.style.maxHeight = accordionContent.scrollHeight + "px";
                }
            }
        }
    },
    
    // Novas funções para garantir que os botões de formato se atualizam
    handleFormKeyUp(e) {
        if(e.target.matches('.bcek-rich-text-input')) this.updateFormatButtons(e.target);
    },
    handleFormMouseUp(e) {
        if(e.target.matches('.bcek-rich-text-input')) this.updateFormatButtons(e.target);
    },

    handleFileChange(e) {
        if (e.target.matches('.bcek-dynamic-image-input')) {
            const file = e.target.files[0];
            if (!file) return;
            const fieldId = e.target.closest('.bcek-input-group').dataset.fieldId;
            const field = this.state.bcekData.fields.find(f => String(f.field_id) === fieldId);
            if(field) this.ui.showCropper(file, field);
        }
    },

    handleButtonClick(e) {
        const button = e.target.closest('button, a');
        if (!button) return;

        if (button.matches('.bcek-generate-btn')) {
            e.preventDefault();
            this.ajax.generateImage(button.dataset.format);
            document.getElementById('export-dropdown').classList.add('hidden');
        }
        
        if (button.id === 'export-btn') {
            e.stopPropagation();
            document.getElementById('export-dropdown').classList.toggle('hidden');
        }

        if (button.matches('.accordion-header')) {
            this.handleAccordionClick(button);
        }
        
        if (button.matches('.bcek-format-btn')) {
            e.preventDefault();
            document.execCommand(button.dataset.command, false, null);
            const editor = button.closest('.accordion-content-inner').querySelector('.bcek-rich-text-input');
            editor.dispatchEvent(new Event('input', { bubbles: true }));
            this.updateFormatButtons(editor);
        }
    },

    handleAccordionClick(button) {
        const item = button.closest('.accordion-item');
        const content = button.nextElementSibling;
        
        if (item.classList.contains('open')) {
            content.style.maxHeight = null;
            item.classList.remove('open');
        } else {
            document.querySelectorAll('.accordion-item.open').forEach(openItem => {
                openItem.classList.remove('open');
                openItem.querySelector('.accordion-content').style.maxHeight = null;
            });
            content.style.maxHeight = content.scrollHeight + "px";
            item.classList.add('open');
        }
    },
    
    updateFormatButtons(editorElement) {
        const group = editorElement.closest('.bcek-input-group');
        const boldBtn = group.querySelector('[data-command="bold"]');
        const italicBtn = group.querySelector('[data-command="italic"]');

        if (document.queryCommandState('bold')) boldBtn.classList.add('is-active');
        else boldBtn.classList.remove('is-active');

        if (document.queryCommandState('italic')) italicBtn.classList.add('is-active');
        else italicBtn.classList.remove('is-active');
    },
    
    updateAllInputs() {
        this.state.userInputs = {};
        document.querySelectorAll('.bcek-input-group').forEach(group => {
            const fieldId = group.dataset.fieldId;
            const fieldType = group.dataset.fieldType;
            
            this.state.userInputs[fieldId] = this.state.userInputs[fieldId] || { type: fieldType };

            if (fieldType === 'text') {
                this.state.userInputs[fieldId].text = group.querySelector('.bcek-rich-text-input').innerHTML;
                this.state.userInputs[fieldId].fontSize = group.querySelector('.bcek-dynamic-fontsize-slider').value;
            }
        });
        this.ui.drawCanvas();
    }
};