<?php
/**
 * Template da Interface de Administração Unificada (Front-end).
 * Controla a exibição da lista de templates e do editor.
 */
if ( ! defined( 'WPINC' ) ) { die; }

// --- A CORREÇÃO ESTÁ AQUI ---
// Declaramos a variável global $wpdb para que possamos usá-la neste ficheiro.
global $wpdb;

// Obtém o URL base da página atual, removendo parâmetros de ação.
$current_page_url = remove_query_arg( array('action', 'template_id', '_wpnonce') );
$templates = BCEK_Database::get_all_templates();
?>
<div class="bcek-admin-container">

    <div id="admin-panel" class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 w-full">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div class="inline-block bg-blue-500 text-white font-bold py-2 px-5 rounded-full text-base">Gerenciador de Templates</div>
            <a href="<?php echo esc_url( add_query_arg( 'action', 'add_new', $current_page_url ) ); ?>" id="create-new-btn" class="bcek-button-primary bg-blue-500 text-white font-semibold py-2 px-5 rounded-full hover:bg-blue-600 transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                Criar Novo Template
            </a>
        </div>

        <div id="bcek-templates-table" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

            <?php if ( ! empty( $templates ) ) : ?>
                <?php foreach ( $templates as $template ) : ?>
                    <?php
                    // Prepara os links e a contagem de campos para cada cartão
                    $edit_link = esc_url( add_query_arg( ['action' => 'edit', 'template_id' => $template->template_id], $current_page_url ) );
                    $fields_count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}bcek_fields WHERE template_id = %d", $template->template_id) );
                    ?>
                    <div id="template-row-<?php echo esc_attr( $template->template_id ); ?>" class="template-card group relative rounded-lg shadow-md overflow-hidden transition-transform hover:-translate-y-1">
                        <a href="<?php echo $edit_link; ?>" class="block">
                            <img src="<?php echo esc_url( $template->base_image_url ?: 'https://via.placeholder.com/400x300.png?text=Sem+Imagem' ); ?>" alt="<?php echo esc_attr( $template->name ); ?>" class="w-full h-48 object-cover">
                        </a>
                        <div class="p-4 bg-gray-50">
                            <h3 class="font-bold text-gray-800 truncate"><?php echo esc_html( $template->name ); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo $fields_count; ?> campos</p>
                        </div>
                        
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center gap-4 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="<?php echo $edit_link; ?>" class="edit-btn bg-white text-gray-800 p-2 rounded-full hover:bg-gray-200" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg>
                            </a>
                            
                            <a href="#" 
                               class="bcek-delete-template-btn bg-white text-red-600 p-2 rounded-full hover:bg-gray-200" 
                               data-template-id="<?php echo esc_attr( $template->template_id ); ?>" 
                               data-nonce="<?php echo esc_attr( wp_create_nonce( 'bcek_delete_template_nonce' ) ); ?>"
                               title="Deletar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="text-center text-gray-500 col-span-full">Nenhum template encontrado. <a href="<?php echo esc_url( add_query_arg( 'action', 'add_new', $current_page_url ) ); ?>" class="text-blue-500 hover:underline">Crie o primeiro!</a></p>
            <?php endif; ?>

        </div>
    </div>

</div>