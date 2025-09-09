jQuery(document).ready(function($) {
    'use strict';
    
    // Lógica para apagar templates (permanece igual)
    const templatesContainer = $('#bcek-templates-table');
    if (templatesContainer.length > 0) {
        templatesContainer.on('click', '.bcek-delete-template-btn', function(e) {
            e.preventDefault();
            const button = $(this);
            const templateId = button.data('template-id');
            const nonce = button.data('nonce');

            if (!confirm('Tem a certeza que deseja apagar este template?')) return;

            const itemContainer = button.closest('.template-card');
            itemContainer.css('opacity', '0.5');

            $.post(bcek_list_params.ajax_url, {
                action: 'bcek_delete_template',
                template_id: templateId,
                nonce: nonce
            }).done(response => {
                if (response.success) {
                    itemContainer.fadeOut(400, function() { $(this).remove(); });
                } else {
                    alert('Erro: ' + response.data.message);
                    itemContainer.css('opacity', '1');
                }
            }).fail(() => {
                alert('Erro de comunicação.');
                itemContainer.css('opacity', '1');
            });
        });
    }

    // --- LÓGICA CORRIGIDA PARA GESTÃO DE CATEGORIAS ---
    const categoriesPanel = $('#categories-panel');
    if (categoriesPanel.length > 0) {
        const categoryList = $('#bcek-category-list');
        const newCategoryInput = $('#bcek-new-category-name');
        
        // **CORREÇÃO:** Lê o nonce do atributo data-* que adicionámos ao HTML
        const nonce = categoriesPanel.data('nonce');
        
        // Adicionar Categoria
        $('#bcek-add-category-btn').on('click', function() {
            const categoryName = newCategoryInput.val().trim();
            if (categoryName === '') {
                alert('Por favor, insira um nome para a categoria.');
                return;
            }

            $(this).text('A adicionar...').prop('disabled', true);

            $.post(bcek_list_params.ajax_url, {
                action: 'bcek_add_category',
                category_name: categoryName,
                nonce: nonce // Usa a variável nonce que lemos do HTML
            }).done(response => {
                if (response.success) {
                    const newCat = response.data.category;
                    const newLi = `
                        <li id="category-item-${newCat.id}" class="flex justify-between items-center bg-gray-100 p-2 rounded-lg" data-category-slug="${newCat.slug}">
                            <span class="category-name">${newCat.name}</span>
                            <button class="bcek-delete-category-btn text-red-500 hover:text-red-700 p-1" data-category-id="${newCat.id}" title="Apagar Categoria">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            </button>
                        </li>`;
                    $('#bcek-no-categories-msg').remove();
                    categoryList.append(newLi);
                    newCategoryInput.val('');
                } else {
                    alert('Erro: ' + response.data.message);
                }
            }).fail(() => {
                alert('Erro de comunicação.');
            }).always(() => {
                $(this).text('Adicionar Categoria').prop('disabled', false);
            });
        });

        // Apagar Categoria
        categoryList.on('click', '.bcek-delete-category-btn', function() {
            const button = $(this);
            const categoryId = button.data('category-id');
            
            if (!confirm('Tem a certeza que deseja apagar esta categoria? Os templates associados não serão apagados, mas ficarão sem categoria.')) return;
            
            const listItem = $(`#category-item-${categoryId}`);
            listItem.css('opacity', '0.5');

            $.post(bcek_list_params.ajax_url, {
                action: 'bcek_delete_category',
                category_id: categoryId,
                nonce: nonce // Usa a mesma variável nonce
            }).done(response => {
                if (response.success) {
                    listItem.fadeOut(400, function() { $(this).remove(); });
                } else {
                    alert('Erro: ' + response.data.message);
                    listItem.css('opacity', '1');
                }
            }).fail(() => {
                alert('Erro de comunicação.');
                listItem.css('opacity', '1');
            });
        });
    }
});