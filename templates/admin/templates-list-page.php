<?php
/**
 * Template da Interface de Administração Unificada (Front-end).
 * VERSÃO FINAL: Com cartões quadrados e thumbnails.
 */
if ( ! defined( 'WPINC' ) ) { die; }

global $wpdb;

// Busca todos os dados necessários
$current_page_url = remove_query_arg( array('action', 'template_id', '_wpnonce') );
$templates = BCEK_Database::get_all_templates();
$categories = BCEK_Database::get_all_categories();

// Cria um mapa de category_id => category_name para fácil acesso
$category_map = [];
if (!empty($categories)) {
    foreach ($categories as $category) {
        $category_map[$category->category_id] = $category->name;
    }
}
?>
<div class="bcek-admin-container">

    <div id="bcek-categories-panel" class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 w-full mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            Gerir Categorias
            <svg class="title-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
        </h2>
        <div class="flex flex-col md:flex-row gap-8">
            <div class="w-full md:w-1/3">
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Adicionar Nova Categoria</h3>
                <form id="bcek-add-category-form">
                    <div class="space-y-2">
                        <label for="bcek-new-category-name" class="block text-sm font-medium text-gray-600">Nome da Categoria</label>
                        <input type="text" id="bcek-new-category-name" class="w-full bg-gray-100 rounded-lg p-3 border border-gray-300 focus:ring-2 focus:ring-blue-500" required>
                        <?php wp_nonce_field( 'bcek_add_category_nonce', 'bcek_add_category_nonce_field' ); ?>
                    </div>
                    <button type="submit" class="mt-4 w-full bg-blue-500 text-white font-semibold py-2 px-5 rounded-lg hover:bg-blue-600 transition-colors">
                        Adicionar Categoria
                    </button>
                </form>
            </div>
            <div class="w-full md:w-2/3">
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Categorias Existentes</h3>
                <div id="bcek-categories-list" class="bg-gray-50 rounded-lg p-4 border max-h-60 overflow-y-auto custom-scrollbar">
                    <?php if ( ! empty( $categories ) ) : ?>
                        <?php foreach ( $categories as $category ) : ?>
                            <div class="flex justify-between items-center p-2 border-b" id="category-row-<?php echo esc_attr($category->category_id); ?>">
                                <div>
                                    <span class="text-gray-700 font-medium"><?php echo esc_html($category->name); ?></span>
                                    <span class="block text-xs text-gray-400 font-mono">ID: bcek-category-<?php echo esc_html($category->slug); ?></span>
                                </div>
                                <a href="#" class="bcek-delete-category-btn text-red-500 hover:text-red-700" data-category-id="<?php echo esc_attr($category->category_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('bcek_delete_category_nonce')); ?>" title="Apagar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></svg>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p id="bcek-no-categories-msg" class="text-gray-500">Nenhuma categoria encontrada.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="admin-panel" class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 w-full">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <h2 class="text-2xl font-bold text-gray-800">
                Gerenciador de Templates
                <svg class="title-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
            </h2>
            <a href="<?php echo esc_url( add_query_arg( 'action', 'add_new', $current_page_url ) ); ?>" id="create-new-btn" class="bcek-button-primary bg-blue-500 text-white font-semibold py-2 px-5 rounded-full hover:bg-blue-600 transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                Criar Novo Template
            </a>
        </div>

        <div id="bcek-templates-table" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php if ( ! empty( $templates ) ) : ?>
                <?php foreach ( $templates as $template ) : ?>
                    <?php
                    $edit_link = esc_url( add_query_arg( ['action' => 'edit', 'template_id' => $template->template_id], $current_page_url ) );
                    
                    // --- AJUSTE PRINCIPAL: LÓGICA DO THUMBNAIL ---
                    $image_url = 'https://placehold.co/400x400/f3f4f6/cbd5e0?text=PREVIEW'; // Placeholder padrão
                    if ( ! empty( $template->base_image_id ) ) {
                        // Pede ao WordPress o nosso novo thumbnail quadrado 'bcek_template_thumb'
                        $thumb_array = wp_get_attachment_image_src( $template->base_image_id, 'bcek_template_thumb' );
                        if ($thumb_array) {
                            $image_url = $thumb_array[0];
                        } else {
                            // Se falhar (ex: imagem antiga), usa um thumbnail padrão como fallback
                            $fallback_thumb = wp_get_attachment_image_src( $template->base_image_id, 'medium' );
                            $image_url = $fallback_thumb ? $fallback_thumb[0] : $template->base_image_url;
                        }
                    }
                    ?>
                    <div id="template-row-<?php echo esc_attr( $template->template_id ); ?>" class="template-card-v2 group relative rounded-2xl shadow-md overflow-hidden bg-gray-200">
                        <a href="<?php echo $edit_link; ?>" class="block">
                            <div class="template-preview-area aspect-square w-full bg-gray-100">
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $template->name ); ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="template-info-bar p-4 bg-blue-500">
                                <h3 class="font-bold text-white truncate"><?php echo esc_html( $template->name ); ?></h3>
                                <p class="text-sm text-blue-200"><?php echo esc_html( $category_map[$template->category_id] ?? 'Sem Categoria' ); ?></p>
                            </div>
                        </a>
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center gap-4 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                            <a href="<?php echo $edit_link; ?>" class="edit-btn bg-white text-gray-800 p-2 rounded-full hover:bg-gray-200" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg></a>
                            <a href="#" class="bcek-duplicate-template-btn bg-white text-gray-800 p-2 rounded-full hover:bg-gray-200" data-template-id="<?php echo esc_attr( $template->template_id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bcek_duplicate_template_nonce' ) ); ?>" title="Duplicar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z" /><path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h6a2 2 0 00-2-2H5z" /></svg>
                            </a>
                            <a href="#" class="bcek-delete-template-btn bg-white text-red-600 p-2 rounded-full hover:bg-gray-200" data-template-id="<?php echo esc_attr( $template->template_id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bcek_delete_template_nonce' ) ); ?>" title="Apagar"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="text-center text-gray-500 col-span-full">Nenhum template encontrado. <a href="<?php echo esc_url( add_query_arg( 'action', 'add_new', $current_page_url ) ); ?>" class="text-blue-500 hover:underline">Crie o primeiro!</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>