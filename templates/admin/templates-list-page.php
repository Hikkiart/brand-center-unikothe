<?php
/**
 * Template da Interface de Administração Unificada (Front-end).
 */
if ( ! defined( 'WPINC' ) ) { die; }
global $wpdb;

$current_page_url = remove_query_arg( array('action', 'template_id', '_wpnonce') );
$templates = BCEK_Database::get_all_templates();
$categories = BCEK_Database::get_all_categories();
?>
<div class="bcek-admin-container flex flex-col lg:flex-row gap-8">

    <div class="w-full lg:w-3/4">
        <div id="admin-panel" class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 w-full">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div class="inline-block bg-blue-500 text-white font-bold py-2 px-5 rounded-full text-base">Gerenciador de Templates</div>
                <a href="<?php echo esc_url( add_query_arg( 'action', 'add_new', $current_page_url ) ); ?>" id="create-new-btn" class="bcek-button-primary bg-blue-500 text-white font-semibold py-2 px-5 rounded-full hover:bg-blue-600 transition-colors flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                    Criar Novo Template
                </a>
            </div>

            <div id="bcek-templates-table" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if ( ! empty( $templates ) ) : ?>
                    <?php foreach ( $templates as $template ) : ?>
                        <?php
                        $edit_link = esc_url( add_query_arg( ['action' => 'edit', 'template_id' => $template->template_id], $current_page_url ) );
                        $fields_count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}bcek_fields WHERE template_id = %d", $template->template_id) );
                        // Encontra o nome da categoria para este template
                        $category_name = 'Sem Categoria';
                        if ( ! empty($template->category_id) && ! empty($categories) ) {
                            foreach ( $categories as $cat ) {
                                if ( $cat->category_id == $template->category_id ) {
                                    $category_name = $cat->name;
                                    break;
                                }
                            }
                        }
                        ?>
                        <div id="template-row-<?php echo esc_attr( $template->template_id ); ?>" class="template-card group relative rounded-lg shadow-md overflow-hidden transition-transform hover:-translate-y-1">
                            <a href="<?php echo $edit_link; ?>" class="block">
                                <img src="<?php echo esc_url( $template->base_image_url ?: 'https://via.placeholder.com/400x300.png?text=Sem+Imagem' ); ?>" alt="<?php echo esc_attr( $template->name ); ?>" class="w-full h-48 object-cover">
                            </a>
                            <div class="p-4 bg-gray-50">
                                <h3 class="font-bold text-gray-800 truncate"><?php echo esc_html( $template->name ); ?></h3>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider"><?php echo esc_html( $category_name ); ?></p>
                            </div>
                            </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="text-center text-gray-500 col-span-full">Nenhum template encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-1/4">
        <div id="categories-panel" class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 w-full" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bcek_save_template_nonce' ) ); ?>">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Gerir Categorias</h3>
            
            <div class="add-category-form space-y-2 mb-6">
                <input type="text" id="bcek-new-category-name" placeholder="Nome da nova categoria" class="w-full bg-gray-100 rounded-lg p-3 border border-gray-300">
                <button type="button" id="bcek-add-category-btn" class="w-full bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-gray-700">Adicionar Categoria</button>
            </div>
            
            <ul id="bcek-category-list" class="space-y-2">
                <?php if ( ! empty( $categories ) ) : ?>
                    <?php foreach ( $categories as $category ) : ?>
                        <li id="category-item-<?php echo esc_attr($category->category_id); ?>" class="flex justify-between items-center bg-gray-100 p-2 rounded-lg" data-category-slug="<?php echo esc_attr($category->slug); ?>">
                            <span class="category-name"><?php echo esc_html($category->name); ?></span>
                            <button class="bcek-delete-category-btn text-red-500 hover:text-red-700 p-1" data-category-id="<?php echo esc_attr($category->category_id); ?>" title="Apagar Categoria">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            </button>
                        </li>
                    <?php endforeach; ?>
                <?php else : ?>
                    <li id="bcek-no-categories-msg" class="text-center text-gray-500 text-sm">Nenhuma categoria encontrada.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>