// assets/js/user/events.js
const BCEK_User_Events = {
    init(state, ui, ajax) {
        this.state = state;
        this.ui = ui;
        this.ajax = ajax;
        
        this.state.dom.baseImg.addEventListener('load', () => this.onBaseImageLoad());
        if (this.state.dom.baseImg.complete) {
            this.onBaseImageLoad();
        }

        window.addEventListener('resize', () => this.ui.drawCanvas());
        
        const wrapper = this.state.dom.wrapper;
        wrapper.addEventListener('input', (e) => this.handleFormInput(e));
        wrapper.addEventListener('change', (e) => this.handleFileChange(e));
        wrapper.addEventListener('click', (e) => this.handleButtonClick(e));
        
        document.getElementById('bcek-confirm-crop-btn').addEventListener('click', () => this.ui.confirmCrop());
        document.getElementById('bcek-cancel-crop-btn').addEventListener('click', () => this.ui.cancelCrop());
    },

    async onBaseImageLoad() {
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
        // Agora ouve tanto o input numérico como o slider
        if (e.target.matches('.bcek-dynamic-text-input, .bcek-dynamic-fontsize-input, .bcek-dynamic-fontsize-slider')) {
            
            // Sincroniza o slider e o input numérico
            if(e.target.matches('.bcek-dynamic-fontsize-input') || e.target.matches('.bcek-dynamic-fontsize-slider')) {
                const group = e.target.closest('.bcek-input-group');
                group.querySelector('.bcek-dynamic-fontsize-input').value = e.target.value;
                group.querySelector('.bcek-dynamic-fontsize-slider').value = e.target.value;
            }

            this.updateAllInputs();
        }
    },

    handleFileChange(e) {
        if (e.target.matches('.bcek-dynamic-image-input')) {
            const file = e.target.files[0];
            if (!file) return;
            const fieldId = e.target.closest('.bcek-input-group').dataset.fieldId;
            const field = this.state.bcekData.fields.find(f => String(f.field_id) === fieldId);
            if(field) {
                this.ui.showCropper(file, field);
            }
        }
    },

    handleButtonClick(e) {
        if (e.target.matches('.bcek-generate-btn')) {
            this.ajax.generateImage(e.target.dataset.format);
        }
    },
    
    updateAllInputs() {
        this.state.userInputs = {};
        document.querySelectorAll('.bcek-input-group').forEach(group => {
            const fieldId = group.dataset.fieldId;
            const fieldType = group.dataset.fieldType;
            
            this.state.userInputs[fieldId] = this.state.userInputs[fieldId] || { type: fieldType };

            if (fieldType === 'text') {
                this.state.userInputs[fieldId].text = group.querySelector('.bcek-dynamic-text-input').value;
                this.state.userInputs[fieldId].fontSize = group.querySelector('.bcek-dynamic-fontsize-input').value;
            }
        });
        this.ui.drawCanvas();
    }
};
