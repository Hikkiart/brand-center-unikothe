<?php
/**
 * Template para a lista de templates do utilizador final.
 */
if ( ! defined( 'WPINC' ) ) { die; }

// Obtém o URL da página atual para construir os links do editor
$current_page_url = remove_query_arg('template_id');
?>

<div class="bcek-user-container">
    
    <div class="bcek-list-header">
        <h1 class="bcek-list-title">Selecione um Template</h1>
        <p class="bcek-list-subtitle">Escolha um modelo para começar a personalizar.</p>
    </div>

    <div class="bcek-categories-wrapper">
        <?php
        // Loop através das categorias
        foreach ($categories as $category):
            // Verifica se existem templates nesta categoria
            if (isset($templates_by_category[$category->category_id])):
        ?>
            <div class="bcek-category-section">
                <h2 class="bcek-category-title"><?php echo esc_html($category->name); ?></h2>
                <div class="bcek-templates-grid">
                    <?php
                    // Loop através dos templates desta categoria
                    foreach ($templates_by_category[$category->category_id] as $template):
                        $edit_link = esc_url(add_query_arg('template_id', $template->template_id, $current_page_url));
                    ?>
                        <a href="<?php echo $edit_link; ?>" class="bcek-template-card">
                            <div class="bcek-card-thumbnail">
                                <img src="<?php echo esc_url($template->base_image_url ?: 'https://placehold.co/400x300/E2E8F0/A0AEC0?text=Template'); ?>" alt="<?php echo esc_attr($template->name); ?>">
                            </div>
                            <div class="bcek-card-content">
                                <h3 class="bcek-card-title"><?php echo esc_html($template->name); ?></h3>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php 
            endif;
        endforeach; 

        // Mostra os templates sem categoria, se existirem
        if (isset($templates_by_category['uncategorized'])):
        ?>
            <div class="bcek-category-section">
                <h2 class="bcek-category-title">Outros</h2>
                <div class="bcek-templates-grid">
                    <?php
                    foreach ($templates_by_category['uncategorized'] as $template):
                        $edit_link = esc_url(add_query_arg('template_id', $template->template_id, $current_page_url));
                    ?>
                        <a href="<?php echo $edit_link; ?>" class="bcek-template-card">
                            <div class="bcek-card-thumbnail">
                                <img src="<?php echo esc_url($template->base_image_url ?: 'https://placehold.co/400x300/E2E8F0/A0AEC0?text=Template'); ?>" alt="<?php echo esc_attr($template->name); ?>">
                            </div>
                            <div class="bcek-card-content">
                                <h3 class="bcek-card-title"><?php echo esc_html($template->name); ?></h3>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>