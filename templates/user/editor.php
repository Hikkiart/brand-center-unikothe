<?php
/**
 * Template para a interface de edição do utilizador final (versão com editor de texto rico).
 */
if ( ! defined( 'WPINC' ) ) { die; }

$current_page_url = get_permalink();
?>

<div id="bcek-editor-wrapper" class="bcek-user-container">
    
    <div class="mb-6">
        <a href="<?php echo esc_url( remove_query_arg('template_id', $current_page_url) ); ?>" class="text-gray-600 font-semibold hover:text-gray-900 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
            Voltar para a lista
        </a>
    </div>

    <div class="bcek-user-container-wrapper">
        <main class="bcek-preview-area">
            <div id="bcek-canvas-container">
                <img id="bcek-base-image-preview" src="<?php echo esc_url( $template->base_image_url ); ?>" alt="Pré-visualização">
                <canvas id="bcek-text-overlay-canvas"></canvas>
            </div>
        </main>

        <aside class="bcek-controls-sidebar">
            <div class="bcek-controls-header">
                <h2 class="text-xl font-bold text-gray-800">Personalizar</h2>
                <div class="relative">
                    <button id="export-btn" class="bg-blue-500 text-white font-semibold py-2 px-5 rounded-full hover:bg-blue-600 transition-colors flex items-center gap-2">
                        <span><?php _e( 'Exportar', 'bcek' ); ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="export-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-1 hidden z-10">
                        <a href="#" class="bcek-generate-btn block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-format="png"><?php _e( 'Exportar como PNG', 'bcek' ); ?></a>
                        <a href="#" class="bcek-generate-btn block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-format="bmp"><?php _e( 'Exportar como BMP', 'bcek' ); ?></a>
                    </div>
                </div>
            </div>
            
            <div class="bcek-controls-body custom-scrollbar">
                <form id="bcek-editor-form" class="space-y-4">
                    
                    <?php if ( ! empty( $fields ) ) : ?>
                        <?php foreach ( $fields as $index => $field ) : ?>
                            <div class="accordion-item border-b border-gray-200">
                                <button type="button" class="accordion-header">
                                    <span><?php echo esc_html( $field->name ?: sprintf( 'Campo %d', $index + 1 ) ); ?></span>
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div class="accordion-content">
                                    <div class="accordion-content-inner">
                                        <div class="bcek-input-group space-y-2" data-field-id="<?php echo esc_attr( $field->field_id ); ?>" data-field-type="<?php echo esc_attr( $field->field_type ?? 'text' ); ?>">
                                            
                                            <?php if ( $field->field_type === 'image' ) : ?>
                                                <input type="file" id="bcek_field_<?php echo esc_attr( $field->field_id ); ?>" class="bcek-dynamic-image-input" accept="image/png, image/jpeg, image/gif" />
                                                <p class="text-xs text-gray-500 mt-1">Formato: <?php echo esc_html( ucfirst( $field->container_shape ?? 'Retângulo' ) ); ?></p>
                                            
                                            <?php else : // --- BLOCO DE TEXTO MODIFICADO --- ?>
                                                
                                                <div class="flex items-center gap-2 mb-2 p-1 bg-gray-100 rounded-md">
                                                    <button type="button" class="bcek-format-btn p-2 rounded hover:bg-gray-200" data-command="bold" title="Negrito">
                                                        <span class="font-black text-sm">N</span>
                                                    </button>
                                                    <button type="button" class="bcek-format-btn p-2 rounded hover:bg-gray-200" data-command="italic" title="Itálico">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.13 3.67H12.5v1.88h-1.63L9.2 13.5h1.88v1.88H6.25v-1.88h1.62L9.54 5.55H7.5V3.67h2.63z"></path></svg>
                                                    </button>
                                                </div>
                                                
                                                <div id="bcek_field_<?php echo esc_attr( $field->field_id ); ?>" class="bcek-rich-text-input w-full bg-white rounded-lg p-3 border border-gray-300 focus:ring-2 focus:ring-blue-500 min-h-[80px]" contenteditable="true"><?php echo wp_kses_post( $field->default_text ?? '' ); ?></div>
                                                
                                                <div class="pt-2">
                                                    <label class="block text-xs font-medium text-gray-500">Tamanho da Fonte</label>
                                                    <input type="range" class="bcek-dynamic-fontsize-slider w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" value="<?php echo esc_attr( $field->font_size ); ?>" min="8" max="200">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="pt-4 space-y-4">
                        <div>
                            <label for="bcek_filename" class="block text-sm font-medium text-gray-700">Nome do Arquivo (opcional)</label>
                            <input type="text" id="bcek_filename" class="mt-1 w-full bg-gray-100 rounded-lg border-none focus:ring-2 focus:ring-blue-500 py-2 px-3">
                        </div>
                        <div id="bcek-loader" style="display:none;" class="text-center text-sm text-gray-500">A gerar imagem...</div>
                        <div id="bcek-result-area" class="text-center"></div>
                    </div>

                </form>
            </div>
        </aside>

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
</div>