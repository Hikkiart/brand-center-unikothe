<?php
/**
 * Template para a interface de edição do utilizador final (nova versão).
 * As variáveis $template e $fields estão disponíveis aqui.
 */
if ( ! defined( 'WPINC' ) ) { die; }

$current_page_url = get_permalink();
?>

<div id="bcek-editor-wrapper" class="bcek-user-container" data-template-id="<?php echo esc_attr( $template->template_id ); ?>">
    <div class="flex justify-between items-center mb-6">
        <a href="<?php echo esc_url($current_page_url); ?>" class="text-gray-600 font-semibold hover:text-gray-900 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
            Voltar para a lista
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-6 md:gap-8">
        <div class="w-full md:w-2/3 lg:w-3/4 bg-gray-200 rounded-2xl p-4 flex items-center justify-center relative">
            <div id="bcek-canvas-container" class="relative shadow-lg">
                <img id="bcek-base-image-preview" src="<?php echo esc_url( $template->base_image_url ); ?>" alt="Pré-visualização" class="max-w-full max-h-full rounded-lg block">
                <canvas id="bcek-text-overlay-canvas" class="absolute top-0 left-0 pointer-events-none"></canvas>
            </div>
        </div>

        <div class="w-full md:w-1/3 lg:w-1/4 p-4 md:p-6 bg-gray-50 rounded-2xl">
            <h3 class="text-xl font-bold text-gray-800 mb-6">Personalize sua Imagem</h3>

            <form id="bcek-editor-form" class="space-y-6">
                <?php if ( ! empty( $fields ) ) : ?>
                    <?php foreach ( $fields as $index => $field ) : ?>
                        <div class="bcek-input-group" data-field-id="<?php echo esc_attr( $field->field_id ); ?>" data-field-type="<?php echo esc_attr( $field->field_type ?? 'text' ); ?>">
                            
                            <label for="bcek_field_<?php echo esc_attr( $field->field_id ); ?>" class="block text-sm font-semibold text-gray-700 mb-2">
                                <?php echo esc_html( $field->name ?: sprintf( 'Campo %d', $index + 1 ) ); ?>
                            </label>

                            <?php if ( $field->field_type === 'image' ) : ?>
                                <input type="file" id="bcek_field_<?php echo esc_attr( $field->field_id ); ?>" class="bcek-dynamic-image-input" accept="image/png, image/jpeg, image/gif" />
                                <p class="text-xs text-gray-500 mt-1">Formato: <?php echo esc_html( ucfirst( $field->container_shape ?? 'Retângulo' ) ); ?></p>
                            
                            <?php else : // Campo de Texto ?>
                                <textarea id="bcek_field_<?php echo esc_attr( $field->field_id ); ?>" class="bcek-dynamic-text-input w-full bg-white rounded-lg p-3 border border-gray-300 focus:ring-2 focus:ring-blue-500" rows="3"><?php echo esc_textarea( $field->default_text ?? '' ); ?></textarea>
                                
                                <div class="flex flex-col mt-2">
                                    <div class="flex justify-between items-center">
                                        <label class="text-xs text-gray-600">Tamanho da fonte:</label>
                                        <input type="number" class="bcek-dynamic-fontsize-input w-20 bg-white p-1 border border-gray-300 rounded-md text-sm text-center" value="<?php echo esc_attr( $field->font_size ); ?>" min="8" max="200" />
                                    </div>
                                    <input type="range" class="bcek-dynamic-fontsize-slider w-full mt-1" value="<?php echo esc_attr( $field->font_size ); ?>" min="8" max="200">
                                </div>

                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div>
                    <label for="bcek_filename" class="block text-sm font-semibold text-gray-700 mb-2">Nome do Arquivo (opcional)</label>
                    <input type="text" id="bcek_filename" class="w-full bg-white rounded-lg p-3 border border-gray-300 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="pt-4 border-t space-y-3">
                    <button type="button" class="bcek-generate-btn w-full bg-blue-500 text-white font-semibold py-3 px-5 rounded-lg hover:bg-blue-600 transition-colors" data-format="png">
                        <?php _e( 'Gerar Imagem (PNG)', 'bcek' ); ?>
                    </button>
                    <button type="button" class="bcek-generate-btn w-full bg-gray-600 text-white font-semibold py-3 px-5 rounded-lg hover:bg-gray-700 transition-colors" data-format="bmp">
                        <?php _e( 'Gerar Imagem (BMP)', 'bcek' ); ?>
                    </button>
                    <div id="bcek-loader" style="display:none;" class="text-center">Aguarde...</div>
                </div>
            </form>

            <div id="bcek-result-area" class="mt-6"></div>
        </div>
    </div>

    <div id="bcek-cropper-modal" style="display: none;">
        <div class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg">
                <h3 class="text-lg font-bold mb-4">Ajustar Imagem</h3>
                <div class="max-h-[60vh] overflow-hidden bg-gray-200">
                    <img id="bcek-image-to-crop" src="">
                </div>
                <div class="flex justify-end gap-4 mt-4">
                    <button type="button" id="bcek-cancel-crop-btn" class="bg-gray-200 text-gray-800 font-semibold py-2 px-5 rounded-lg">Cancelar</button>
                    <button type="button" id="bcek-confirm-crop-btn" class="bg-blue-500 text-white font-semibold py-2 px-5 rounded-lg">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
</div>
