<?php
/**
 * Template para a lista de templates do utilizador final.
 * A variável $templates está disponível aqui.
 */
if ( ! defined( 'WPINC' ) ) { die; }

$current_page_url = get_permalink();
?>

<div class="bcek-user-container">
    <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 w-full">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div class="inline-block bg-blue-500 text-white font-bold py-2 px-5 rounded-full text-base">
                Escolha um Template para começar
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php if ( ! empty( $templates ) ) : ?>
                <?php foreach ( $templates as $template ) : ?>
                    <?php
                        $edit_link = esc_url( add_query_arg( ['template_id' => $template->template_id], $current_page_url ) );
                        $image_url = $template->base_image_url ?: 'https://placehold.co/400x300/E2E8F0/A0AEC0?text=Sem+Imagem';
                    ?>
                    <div class="group relative rounded-lg shadow-md overflow-hidden transition-transform hover:-translate-y-1">
                        <a href="<?php echo $edit_link; ?>" class="block">
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr( $template->name ); ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="p-4 bg-gray-50">
                                <h3 class="font-bold text-gray-800 truncate"><?php echo esc_html( $template->name ); ?></h3>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="text-center text-gray-500 col-span-full">Nenhum template disponível no momento.</p>
            <?php endif; ?>
        </div>
    </div>
</div>