<?php
/**
 * Template para a lista de templates do utilizador final (versão com drop-down de filtro).
 * A variável $grouped_templates está disponível aqui.
 */
if ( ! defined( 'WPINC' ) ) { die; }

$current_page_url = get_permalink();
?>

<div class="bcek-user-container">
    <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 w-full">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <h2 class="text-2xl font-bold text-gray-800">Escolha um Template para começar</h2>
        </div>

        <?php if ( ! empty( $grouped_templates ) ) : ?>
            <div class="bcek-filter-dropdown-wrapper mb-10">
                <label for="bcek-category-filter" class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Categoria:</label>
                <select id="bcek-category-filter" class="bcek-filter-select">
                    <option value="*"><?php _e( 'Todas as Categorias', 'bcek' ); ?></option>
                    <?php foreach ( $grouped_templates as $group ) : ?>
                        <option value=".bcek-category-<?php echo esc_attr($group['category_slug']); ?>">
                            <?php echo esc_html( $group['category_name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="bcek-templates-grid">
                <?php foreach ( $grouped_templates as $group ) : ?>
                    <?php
                        $category_css_class = 'bcek-category-' . esc_attr($group['category_slug']);
                    ?>
                    <div class="bcek-template-group mb-10 <?php echo $category_css_class; ?>">
                        <h3 class="text-xl font-semibold text-gray-700 border-b pb-2 mb-6">
                            <?php echo esc_html( $group['category_name'] ); ?>
                        </h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            <?php foreach ( $group['templates'] as $template ) : ?>
                                <?php
                                    $edit_link = esc_url( add_query_arg( ['template_id' => $template->template_id], $current_page_url ) );
                                    
                                    $image_url = 'https://placehold.co/400x400/f3f4f6/cbd5e0?text=PREVIEW';
                                    if ( ! empty( $template->base_image_id ) ) {
                                        $thumb_array = wp_get_attachment_image_src( $template->base_image_id, 'bcek_template_thumb' );
                                        if ($thumb_array) {
                                            $image_url = $thumb_array[0];
                                        } else {
                                            $fallback_thumb = wp_get_attachment_image_src( $template->base_image_id, 'medium' );
                                            $image_url = $fallback_thumb ? $fallback_thumb[0] : $template->base_image_url;
                                        }
                                    }
                                ?>
                                <div class="template-card-v2 group relative rounded-2xl shadow-md overflow-hidden bg-gray-200">
                                    <a href="<?php echo $edit_link; ?>" class="block">
                                        <div class="template-preview-area aspect-square w-full bg-gray-100">
                                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $template->name ); ?>" class="w-full h-full object-cover">
                                        </div>
                                        <div class="template-info-bar p-4 bg-blue-500 min-h-[80px] flex flex-col justify-center">
                                            <h3 class="font-bold text-white truncate"><?php echo esc_html( $template->name ); ?></h3>
                                            <p class="text-sm text-blue-200"><?php echo esc_html( $group['category_name'] ); ?></p>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="text-center text-gray-500 col-span-full">Nenhum template disponível no momento.</p>
        <?php endif; ?>
    </div>
</div>