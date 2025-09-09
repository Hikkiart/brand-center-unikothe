<?php
/**
 * Template para a interface de edição do utilizador final.
 * Baseado no design de Editor.html
 * As variáveis $template (objeto) e $fields (array) são passadas pelo shortcode.
 */
if ( ! defined( 'WPINC' ) ) { die; }

// Obtém o URL da página da lista para o botão "Voltar"
$list_page_url = remove_query_arg('template_id');
?>

<div class="bcek-user-editor-container bg-gray-100 p-4 sm:p-6 md:p-8">
    <div class="max-w-7xl mx-auto">

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <a href="<?php echo esc_url($list_page_url); ?>" class="text-sm font-semibold text-gray-600 hover:text-gray-900 flex items-center gap-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    Voltar para a lista
                </a>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mt-1">
                    <?php echo esc_html($template->name); ?>
                </h1>
            </div>
            <div class="flex items-center gap-3">
                <button id="generate-image-btn" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    Gerar Imagem
                </button>
                <span id="bcek-loader" style="display:none;">
                    <img src="<?php echo esc_url( admin_url( '/images/spinner-2x.gif' ) ); ?>" alt="Loading..." width="20" height="20">
                </span>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">

            <div class="w-full lg:w-2/3">
                <div id="preview-container" class="relative bg-white p-4 rounded-xl shadow-lg aspect-w-16 aspect-h-9 flex items-center justify-center">
                    <div id="canvas-wrapper" class="relative">
                        <img id="bcek-base-image-preview" src="<?php echo esc_url($template->base_image_url); ?>" alt="Pré-visualização do Template" style="max-width: 100%; display: block; border-radius: 8px;">
                        <canvas id="bcek-text-overlay-canvas"></canvas>
                    </div>
                </div>
                <div id="bcek-result-area" class="mt-4"></div>
            </div>

            <div class="w-full lg:w-1/3">
                <div id="fields-container" class="bg-white p-6 rounded-xl shadow-lg space-y-5 custom-scrollbar">
                    <h2 class="text-xl font-bold text-gray-800 border-b pb-3 mb-4">Personalize os Campos</h2>
                    
                    <form id="bcek-editor-form">
                        <?php if ( ! empty( $fields ) ) : ?>
                            <?php foreach ( $fields as $field ) : ?>
                                <div class="bcek-input-group" data-field-id="<?php echo esc_attr( $field->field_id ); ?>" data-field-type="<?php echo esc_attr( $field->field_type ?? 'text' ); ?>">
                                    
                                    <?php // Gera um campo de TEXTO
                                    if ( ! isset( $field->field_type ) || $field->field_type === 'text' ) : ?>
                                        <label for="bcek_field_<?php echo esc_attr($field->field_id); ?>" class="block text-sm font-medium text-gray-700 mb-1"><?php echo esc_html($field->name); ?></label>
                                        <textarea
                                            id="bcek_field_<?php echo esc_attr($field->field_id); ?>"
                                            class="bcek-dynamic-text-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            rows="3"
                                            data-field-id="<?php echo esc_attr($field->field_id); ?>"
                                        ><?php echo esc_textarea($field->default_text ?? ''); ?></textarea>
                                    
                                    <?php // Gera um campo de IMAGEM
                                    elseif ( $field->field_type === 'image' ) : ?>
                                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo esc_html($field->name); ?></label>
                                        <input
                                            type="file"
                                            id="bcek_field_<?php echo esc_attr($field->field_id); ?>"
                                            class="bcek-dynamic-image-input mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                            data-field-id="<?php echo esc_attr( $field->field_id ); ?>"
                                            accept="image/png, image/jpeg, image/gif"
                                        />
                                    <?php endif; ?>

                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="text-gray-500">Nenhum campo configurável para este template.</p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        
        <div id="bcek-cropper-modal" class="bcek-modal-overlay" style="display: none;">
            <div class="bcek-modal-content">
                <h3 class="bcek-modal-title">Enquadrar Imagem</h3>
                <div class="bcek-modal-body">
                    <div class="bcek-cropper-container">
                        <img id="bcek-image-to-crop" src="" alt="Imagem para recortar">
                    </div>
                </div>
                <div class="bcek-modal-footer">
                    <button type="button" id="bcek-cancel-crop-btn" class="button">Cancelar</button>
                    <button type="button" id="bcek-confirm-crop-btn" class="button button-primary">Confirmar Enquadramento</button>
                </div>
            </div>
        </div>

    </div>
</div>