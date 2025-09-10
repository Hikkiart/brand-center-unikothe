// Módulo para a lógica de arrastar e redimensionar (Versão Final Definitiva)
const BCEK_Admin_Interact = {
    init(state, ui) {
        this.state = state;
        this.ui = ui;
        this.originalAspectRatio = 1;

        interact('.draggable-field')
            .on('down', (event) => {
                 document.querySelectorAll('.draggable-field').forEach(el => el.classList.remove('is-active'));
                 event.currentTarget.classList.add('is-active');
            })
            .draggable({
                allowFrom: '.field-content',
                ignoreFrom: '.resize-handle',
                listeners: {
                    move: (event) => this.dragMoveListener(event)
                },
                modifiers: [
                    interact.modifiers.restrictRect({ restriction: 'parent' })
                ]
            })
            .resizable({
                // --- USANDO A SUA CONFIGURAÇÃO QUE FUNCIONA ---
                // Esta é a base que ativa os cantos corretamente no seu ambiente.
                edges: { left: true, right: true, bottom: true, top: true },
                
                listeners: {
                    start: (event) => {
                        const target = event.target;
                        const id = target.dataset.fieldId;
                        const config = this.state.fieldsState[id];
                        // Guarda a proporção no início de cada redimensionamento
                        this.originalAspectRatio = config.width / config.height;
                    },
                    move: (event) => this.resizeMoveListener(event)
                },
                modifiers: [
                    // --- CORREÇÃO 2: LIMITES DO PREVIEW ---
                    // 'restrictSize' é mais robusto para o redimensionamento proporcional
                    // e irá resolver o problema da altura/largura a ultrapassar os limites.
                    interact.modifiers.restrictSize({
                        max: 'parent',
                        min: { width: 20, height: 20 }
                    })
                ],
                inertia: false
            });
    },

    dragMoveListener(event) {
        const target = event.target;
        const id = target.dataset.fieldId;
        const scale = this.state.currentScale;
        if (!scale || scale === 0) return;
        
        const newX = (this.state.fieldsState[id].pos_x || 0) + (event.dx / scale);
        const newY = (this.state.fieldsState[id].pos_y || 0) + (event.dy / scale);

        this.state.fieldsState[id].pos_x = newX;
        this.state.fieldsState[id].pos_y = newY;
        
        this.ui.updateElementFromState(id);
    },

    /**
     * --- FUNÇÃO ATUALIZADA ---
     * Implementa a lógica do Shift manualmente para funcionar com a sua configuração 'edges'.
     */
    resizeMoveListener(event) {
        const target = event.target;
        const id = target.dataset.fieldId;
        const scale = this.state.currentScale;
        const parentBounds = event.target.parentElement.getBoundingClientRect();
        const parentWidth = parentBounds.width;
        const parentHeight = parentBounds.height;

        if (!scale || scale === 0) return;

        let newWidth = event.rect.width;
        let newHeight = event.rect.height;
        let newPosX = (this.state.fieldsState[id].pos_x || 0) + (event.deltaRect.left / scale);
        let newPosY = (this.state.fieldsState[id].pos_y || 0) + (event.deltaRect.top / scale);

        // --- LÓGICA DO SHIFT E LIMITES APRIMORADA ---
        if (event.shiftKey) {
            // Calcula as dimensões proporcionais
            if (Math.abs(event.deltaRect.width) > Math.abs(event.deltaRect.height)) {
                newHeight = newWidth / this.originalAspectRatio;
            } else {
                newWidth = newHeight * this.originalAspectRatio;
            }
        }
        
        // Garante que o campo não "vaze" para a esquerda ou para cima
        if (newPosX < 0) { newWidth += (newPosX * scale); newPosX = 0; }
        if (newPosY < 0) { newHeight += (newPosY * scale); newPosY = 0; }

        // Garante que o campo não "vaze" para a direita ou para baixo
        if ((newPosX * scale + newWidth) > parentWidth) {
            newWidth = parentWidth - (newPosX * scale);
            if (event.shiftKey) newHeight = newWidth / this.originalAspectRatio; // Recalcula a altura se Shift estiver pressionado
        }
        if ((newPosY * scale + newHeight) > parentHeight) {
            newHeight = parentHeight - (newPosY * scale);
            if (event.shiftKey) newWidth = newHeight * this.originalAspectRatio; // Recalcula a largura se Shift estiver pressionado
        }
        
        // Atualiza o estado com as novas dimensões e posições validadas
        this.state.fieldsState[id].width = newWidth / scale;
        this.state.fieldsState[id].height = newHeight / scale;
        this.state.fieldsState[id].pos_x = newPosX;
        this.state.fieldsState[id].pos_y = newPosY;
        
        this.ui.updateElementFromState(id);
    }
};