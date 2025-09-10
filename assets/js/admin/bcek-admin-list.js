jQuery(document).ready(function($) {
    'use strict';
    
    const templatesContainer = $('#bcek-templates-table');

    // --- GUARDA DE EXECUÇÃO ---
    // Se o contentor da lista de templates (#bcek-templates-table) não existir nesta página,
    // o script para imediatamente e não faz mais nada.
    if (templatesContainer.length === 0) {
        return; 
    }

    // O código abaixo só será executado se estivermos na página da lista de templates.
    console.log('[BCEK DEBUG] LISTA: Função de apagar ATIVADA.');

    templatesContainer.on('click', '.bcek-delete-template-btn', function(e) {
        e.preventDefault(); 
        
        const button = $(this);
        const templateId = button.data('template-id');
        const nonce = button.data('nonce');

        if (!confirm('Tem a certeza que deseja apagar este template? Esta ação não pode ser desfeita.')) {
            return;
        }

        const itemContainer = button.closest('.template-card');
        itemContainer.css('opacity', '0.5');

        const data = {
            action: 'bcek_delete_template',
            template_id: templateId,
            nonce: nonce
        };
        
        $.post(bcek_list_params.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    itemContainer.fadeOut(400, function() { $(this).remove(); });
                } else {
                    alert('Erro: ' + response.data.message);
                    itemContainer.css('opacity', '1');
                }
            })
            .fail(function() {
                alert('Ocorreu um erro de comunicação com o servidor.');
                itemContainer.css('opacity', '1');
            });
    });
});