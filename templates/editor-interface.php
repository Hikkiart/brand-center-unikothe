<?php
/**
 * Template para a interface de edição do utilizador final.
 * Este ficheiro é incluído pelo shortcode handler (bcek_render_editor_shortcode).
 * As variáveis $template (objeto do template) e $fields (array de objetos de campos) 
 * são passadas e estão disponíveis neste escopo.
 */

// Medida de segurança: impede o acesso direto ao ficheiro.
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>

<div id="bcek-editor-wrapper" class="bcek-editor-wrapper" data-template-id="<?php echo esc_attr( $template->template_id ); ?>">

    <!-- Área de Preview: Contém a imagem base e o canvas para o overlay de texto/imagem -->
    <div class="bcek-preview-area">
        <div id="bcek-canvas-container" style="position: relative; width: auto; height: auto;">
            <?php if ( ! empty( $template->base_image_url ) ) : ?>
                <img id="bcek-base-image-preview"
                     src="<?php echo esc_url( $template->base_image_url ); ?>"
                     alt="<?php _e( 'Pré-visualização do Template', 'bcek' ); ?>"
                     style="max-width: 100%; display: block;">
                <canvas id="bcek-text-overlay-canvas"
                        style="position: absolute; left: 0; top: 0;"></canvas>
            <?php else : ?>
                <p><?php _e( 'Imagem base do template não configurada.', 'bcek' ); ?></p>
                <canvas id="bcek-text-overlay-canvas"
                        style="border: 1px solid #ccc;"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Área de Controles: Contém os inputs para o utilizador personalizar os campos -->
    <div class="bcek-controls-area">
        <h3><?php _e( 'Personalize seu Design', 'bcek' ); ?></h3>

        <form id="bcek-editor-form">
            <?php if ( ! empty( $fields ) ) : ?>
                <?php foreach ( $fields as $index => $field ) : ?>
                    <div class="bcek-input-group" data-field-id="<?php echo esc_attr( $field->field_id ); ?>" data-field-type="<?php echo esc_attr( $field->field_type ?? 'text' ); ?>">

                        <?php if ( isset( $field->field_type ) && $field->field_type === 'image' ) : ?>
                            <label for="bcek_field_image_<?php echo esc_attr( $field->field_id ); ?>">
                                <?php printf( __( 'Imagem para Bloco %d', 'bcek' ), $index + 1 ); ?>
                            </label>
                            <input
                                type="file"
                                id="bcek_field_image_<?php echo esc_attr( $field->field_id ); ?>"
                                name="bcek_user_images[<?php echo esc_attr( $field->field_id ); ?>]"
                                class="bcek-dynamic-image-input"
                                data-field-id="<?php echo esc_attr( $field->field_id ); ?>"
                                accept="image/png, image/jpeg, image/gif"
                                style="margin-bottom: 10px;"
                            />
                            <p class="description" style="font-size:0.85em; margin-top: -5px;">
                                Contêiner: <?php echo esc_html( $field->width ?? 'N/A' ); ?>px L x <?php echo esc_html( $field->height ?? 'N/A' ); ?>px A,
                                Formato: <?php echo esc_html( ucfirst( $field->container_shape ?? 'retângulo' ) ); ?>
                            </p>

                        <?php elseif ( ! isset( $field->field_type ) || $field->field_type === 'text' ) : ?>
                            <label for="bcek_field_text_<?php echo esc_attr( $field->field_id ); ?>">
                                <?php printf( __( 'Texto para Bloco %d', 'bcek' ), $index + 1 ); ?>
                            </label>
                            <textarea
                                id="bcek_field_text_<?php echo esc_attr( $field->field_id ); ?>"
                                name="bcek_user_inputs[<?php echo esc_attr( $field->field_id ); ?>][text]"
                                rows="3"
                                class="bcek-dynamic-text-input"
                                data-field-id="<?php echo esc_attr( $field->field_id ); ?>"
                                placeholder="<?php echo esc_attr( $field->default_text ?: sprintf( __( 'Digite o texto para o bloco %d', 'bcek' ), $index + 1 ) ); ?>"
                            ><?php echo esc_textarea( $field->default_text ?? '' ); ?></textarea>

                            <label for="bcek_field_fontsize_<?php echo esc_attr( $field->field_id ); ?>" style="margin-top: 5px; font-size: 0.9em;">
                                <?php _e( 'Tamanho da Fonte (px):', 'bcek' ); ?>
                            </label>
                            <input
                                type="number"
                                id="bcek_field_fontsize_<?php echo esc_attr( $field->field_id ); ?>"
                                name="bcek_user_inputs[<?php echo esc_attr( $field->field_id ); ?>][fontSize]"
                                class="bcek-dynamic-fontsize-input small-text"
                                data-field-id="<?php echo esc_attr( $field->field_id ); ?>"
                                value="<?php echo esc_attr( $field->font_size ); ?>"
                                min="8" max="200" step="1" style="width: 70px; padding: 5px;"
                            />
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p><?php _e( 'Nenhum campo configurado para este template.', 'bcek' ); ?></p>
            <?php endif; ?>

            <div class="bcek-input-group">
                <label for="bcek_filename"><?php _e( 'Nome do Arquivo (opcional):', 'bcek' ); ?></label>
                <input type="text" id="bcek_filename" name="bcek_user_filename" class="regular-text" placeholder="<?php _e( 'Ex: minha-arte-final', 'bcek' ); ?>">
            </div>

            <div class="bcek-actions">
                <button type="button" id="bcek-generate-image-button-png" class="button button-primary button-large bcek-generate-btn" data-format="png">
                    <?php _e( 'Gerar Imagem (PNG)', 'bcek' ); ?>
                </button>

                <!-- NOVO BOTÃO BMP -->
                <button type="button" id="bcek-generate-image-button-bmp" class="button button-secondary button-large bcek-generate-btn" data-format="bmp" style="margin-left: 10px;">
                    <?php _e( 'Gerar Imagem (BMP)', 'bcek' ); ?>
                </button>

                <span id="bcek-loader" style="display:none; margin-left: 10px;">
                    <img src="<?php echo esc_url( admin_url( '/images/spinner-2x.gif' ) ); ?>" alt="Loading..." width="20" height="20">
                </span>
            </div>
        </form>

        <div id="bcek-result-area" style="margin-top: 20px;"></div>
    </div>

    <!-- CÓDIGO DO MODAL DE CORTE -->
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