jQuery(document).ready(function($) {
    'use strict';
    
    const container = $('.bcek-admin-container');
    if (container.length === 0) return;

    // --- LÓGICA PARA APAGAR TEMPLATES ---
    $('#bcek-templates-table').on('click', '.bcek-delete-template-btn', function(e) {
        e.preventDefault(); 
        
        const button = $(this);
        if (!confirm('Tem a certeza que deseja apagar este template? Esta ação não pode ser desfeita.')) {
            return;
        }

        const itemContainer = button.closest('.template-card-v2');
        itemContainer.css('opacity', '0.5');

        $.post(bcek_list_params.ajax_url, {
            action: 'bcek_delete_template',
            template_id: button.data('template-id'),
            nonce: button.data('nonce')
        })
        .done(response => {
            if (response.success) {
                itemContainer.fadeOut(400, () => $(this).remove());
            } else {
                alert('Erro ao apagar: ' + response.data.message);
                itemContainer.css('opacity', '1');
            }
        })
        .fail(() => {
            alert('Ocorreu um erro de comunicação com o servidor.');
            itemContainer.css('opacity', '1');
        });
    });

    // --- LÓGICA PARA CATEGORIAS ---
    $('#bcek-add-category-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const button = form.find('button[type="submit"]');
        const categoryNameInput = $('#bcek-new-category-name');
        const categoryName = categoryNameInput.val().trim();

        if (!categoryName) {
            alert('Por favor, insira um nome para a categoria.');
            return;
        }

        button.prop('disabled', true).text('A adicionar...');

        $.post(bcek_list_params.ajax_url, {
            action: 'bcek_add_category',
            name: categoryName,
            nonce: $('#bcek_add_category_nonce_field').val()
        })
        .done(response => {
            if (response.success) {
                const newCat = response.data.category;
                const newRow = `
                    <div class="flex justify-between items-center p-2 border-b" id="category-row-${newCat.id}">
                        <div>
                            <span class="text-gray-700 font-medium">${$('<div>').text(newCat.name).html()}</span>
                            <span class="block text-xs text-gray-400 font-mono">ID: bcek-category-${newCat.slug}</span>
                        </div>
                        <a href="#" class="bcek-delete-category-btn text-red-500 hover:text-red-700" data-category-id="${newCat.id}" data-nonce="${newCat.nonce}" title="Apagar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></svg>
                        </a>
                    </div>`;
                $('#bcek-categories-list').append(newRow);
                $('#bcek-no-categories-msg').hide();
                categoryNameInput.val('');
            } else {
                alert('Erro: ' + response.data.message);
            }
        })
        .fail(() => alert('Ocorreu um erro de comunicação.'))
        .always(() => button.prop('disabled', false).text('Adicionar Categoria'));
    });

    $('#bcek-categories-list').on('click', '.bcek-delete-category-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        if (!confirm('Tem a certeza que deseja apagar esta categoria? Os templates associados ficarão "Sem Categoria".')) return;
        
        const row = button.closest('.flex');
        row.css('opacity', '0.5');

        $.post(bcek_list_params.ajax_url, {
            action: 'bcek_delete_category',
            category_id: button.data('category-id'),
            nonce: button.data('nonce')
        })
        .done(response => {
            if (response.success) {
                row.fadeOut(300, () => row.remove());
            } else {
                alert('Erro: ' + response.data.message);
                row.css('opacity', '1');
            }
        })
        .fail(() => {
            alert('Ocorreu um erro de comunicação.');
            row.css('opacity', '1');
        });
    });
    
    // --- LÓGICA PARA DUPLICAR TEMPLATES ---
    $('#bcek-templates-table').on('click', '.bcek-duplicate-template-btn', function(e) {
        e.preventDefault(); 
    
        const button = $(this);
        const itemContainer = button.closest('.template-card-v2');
    
        // Efeito visual para mostrar que algo está a acontecer
        itemContainer.css('opacity', '0.5');
    
        $.post(bcek_list_params.ajax_url, {
            action: 'bcek_duplicate_template',
            template_id: button.data('template-id'),
            nonce: button.data('nonce')
        })
        .done(response => {
            if (response.success) {
                // Recarrega a página para mostrar o novo template
                location.reload();
            } else {
                alert('Erro ao duplicar: ' + response.data.message);
                itemContainer.css('opacity', '1');
            }
        })
        .fail(() => {
            alert('Ocorreu um erro de comunicação com o servidor.');
            itemContainer.css('opacity', '1');
        });
    });
});