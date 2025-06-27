<?php
/**
 * Funções para renderizar as páginas de administração do plugin
 * Brand Center Editor Kothe no painel do WordPress.
 */

// Medida de segurança: impede o acesso direto ao ficheiro.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// ... (as funções bcek_render_admin_dashboard_page e bcek_render_admin_templates_page permanecem inalteradas) ...
function bcek_render_admin_dashboard_page() {
    ?>
    <div class="wrap bcek-admin-wrap">
        <h1><?php _e( 'Brand Center Editor Kothe - Dashboard', 'bcek' ); ?></h1>
        <p><?php _e( 'Bem-vindo ao painel de controle do Brand Center Editor Kothe.', 'bcek' ); ?></p>
        <p>
            <a href="<?php echo admin_url('admin.php?page=brand-center-kothe-templates'); ?>" class="button button-primary">
                <?php _e( 'Gerenciar Templates', 'bcek' ); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=brand-center-kothe-add-new'); ?>" class="button">
                <?php _e( 'Adicionar Novo Template', 'bcek' ); ?>
            </a>
        </p>
    </div>
    <?php
}

function bcek_render_admin_templates_page() {
    if ( isset( $_GET['action'], $_GET['template_id'], $_GET['_wpnonce'] ) && 
         $_GET['action'] === 'delete' && 
         wp_verify_nonce( $_GET['_wpnonce'], 'bcek_delete_template_' . intval($_GET['template_id']) ) ) { 
        
        $template_id_to_delete = intval($_GET['template_id']); 
        if ( bcek_db_delete_template( $template_id_to_delete ) ) { 
            echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Template deletado com sucesso.', 'bcek' ) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Erro ao deletar o template.', 'bcek' ) . '</p></div>';
        }
    }

    $templates = bcek_db_get_all_templates(); 
    ?>
    <div class="wrap bcek-admin-wrap">
        <h1>
            <?php _e( 'Templates de Imagem', 'bcek' ); ?>
            <a href="<?php echo admin_url('admin.php?page=brand-center-kothe-add-new'); ?>" class="page-title-action">
                <?php _e( 'Adicionar Novo', 'bcek' ); ?>
            </a>
        </h1>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-primary"><?php _e( 'Nome', 'bcek' ); ?></th>
                    <th scope="col" class="manage-column"><?php _e( 'Imagem Base', 'bcek' ); ?></th>
                    <th scope="col" class="manage-column"><?php _e( 'Shortcode', 'bcek' ); ?></th>
                    <th scope="col" class="manage-column"><?php _e( 'Ações', 'bcek' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $templates ) ) : ?>
                    <?php foreach ( $templates as $template ) : ?>
                        <tr>
                            <td class="column-primary" data-colname="<?php _e( 'Nome', 'bcek' ); ?>">
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=brand-center-kothe-add-new&action=edit&template_id=' . $template->template_id); ?>">
                                        <?php echo esc_html( $template->name ); ?>
                                    </a>
                                </strong>
                            </td>
                            <td data-colname="<?php _e( 'Imagem Base', 'bcek' ); ?>">
                                <?php if ( ! empty( $template->base_image_url ) ) : ?>
                                    <img src="<?php echo esc_url( $template->base_image_url ); ?>" alt="<?php echo esc_attr( $template->name ); ?>" style="max-width: 100px; max-height: 50px; border:1px solid #ddd;">
                                <?php else : ?>
                                    <?php _e( 'Nenhuma', 'bcek' ); ?>
                                <?php endif; ?>
                            </td>
                             <td data-colname="<?php _e( 'Shortcode', 'bcek' ); ?>">
                                <code>[brand_center_editor template_id="<?php echo $template->template_id; ?>"]</code>
                            </td>
                            <td data-colname="<?php _e( 'Ações', 'bcek' ); ?>">
                                <a href="<?php echo admin_url('admin.php?page=brand-center-kothe-add-new&action=edit&template_id=' . $template->template_id); ?>"><?php _e( 'Editar', 'bcek' ); ?></a> |
                                <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=brand-center-kothe-templates&action=delete&template_id=' . $template->template_id), 'bcek_delete_template_' . $template->template_id ); ?>"
                                   onclick="return confirm('<?php _e( 'Tem certeza que deseja deletar este template e todos os seus campos? Esta ação não pode ser desfeita.', 'bcek' ); ?>');"
                                   style="color: red;"><?php _e( 'Deletar', 'bcek' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4"><?php _e( 'Nenhum template encontrado.', 'bcek' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}


/**
 * Renderiza a página para adicionar um novo template ou editar um existente.
 */
function bcek_render_admin_add_edit_template_page() {
    $template_id = isset( $_GET['template_id'] ) ? intval( $_GET['template_id'] ) : 0;
    $is_editing = $template_id > 0;
    $template_data = null;
    $fields_data = array();

    if ( $is_editing ) {
        $template_data = bcek_db_get_template_by_id( $template_id );
        if ( ! $template_data ) { echo '<div class="notice notice-error"><p>' . __( 'Template não encontrado.', 'bcek' ) . '</p></div>'; return; }
        $fields_data = bcek_db_get_fields_for_template( $template_id );
    }

    if ( isset( $_POST['bcek_save_template_nonce'] ) && wp_verify_nonce( $_POST['bcek_save_template_nonce'], 'bcek_save_template_action' ) ) {
        $name = isset( $_POST['bcek_template_name'] ) ? sanitize_text_field( $_POST['bcek_template_name'] ) : '';
        $base_image_id = isset( $_POST['bcek_base_image_id'] ) ? intval( $_POST['bcek_base_image_id'] ) : 0;
        $base_image_url = isset( $_POST['bcek_base_image_url'] ) ? esc_url_raw( $_POST['bcek_base_image_url'] ) : '';
        $template_db_data = array( 'name' => $name, 'base_image_id' => $base_image_id, 'base_image_url' => $base_image_url );
        $saved_template_id_or_result = bcek_db_insert_update_template( $template_db_data, $template_id ?: null );

        if ( $saved_template_id_or_result !== false ) { 
            $current_template_id = ($template_id > 0) ? $template_id : $saved_template_id_or_result;

            if (isset($_POST['bcek_removed_fields']) && !empty($_POST['bcek_removed_fields'])) {
                $removed_field_ids = array_map('intval', explode(',', $_POST['bcek_removed_fields']));
                foreach ($removed_field_ids as $removed_field_id) { if ($removed_field_id > 0) bcek_db_delete_field($removed_field_id); }
            }
            
            $fields_saved_count = 0;
            if ( isset( $_POST['bcek_fields'] ) && is_array( $_POST['bcek_fields'] ) ) {
                foreach ( $_POST['bcek_fields'] as $index => $field_post_data ) {
                    $field_id = isset( $field_post_data['id'] ) ? intval( $field_post_data['id'] ) : 0;
                    $field_data = array(
                        'template_id' => $current_template_id, 
                        'field_type'  => isset( $field_post_data['field_type'] ) ? sanitize_text_field( $field_post_data['field_type'] ) : 'text',
                        'pos_x'       => isset( $field_post_data['pos_x'] ) ? intval( $field_post_data['pos_x'] ) : 0,
                        'pos_y'       => isset( $field_post_data['pos_y'] ) ? intval( $field_post_data['pos_y'] ) : 0,
                        'width'       => isset( $field_post_data['width'] ) ? intval( $field_post_data['width'] ) : 100,
                        'height'      => isset( $field_post_data['height'] ) ? intval( $field_post_data['height'] ) : 50,
                        'font_family' => isset( $field_post_data['font_family'] ) ? sanitize_text_field( $field_post_data['font_family'] ) : null,
                        'font_size'   => isset( $field_post_data['font_size'] ) ? intval( $field_post_data['font_size'] ) : null,
                        'font_color'  => isset( $field_post_data['font_color'] ) ? sanitize_hex_color( $field_post_data['font_color'] ) : null,
                        'alignment'   => isset( $field_post_data['alignment'] ) ? sanitize_text_field( $field_post_data['alignment'] ) : null,
                        'line_height_multiplier' => isset( $field_post_data['line_height_multiplier'] ) ? floatval( $field_post_data['line_height_multiplier'] ) : null,
                        'default_text' => isset( $field_post_data['default_text'] ) ? sanitize_textarea_field( $field_post_data['default_text'] ) : null,
                        'container_shape' => isset( $field_post_data['container_shape'] ) ? sanitize_text_field( $field_post_data['container_shape'] ) : null,
                        'z_index_order'   => isset( $field_post_data['z_index_order'] ) ? intval( $field_post_data['z_index_order'] ) : 0,
                    );
                    if ($field_data['field_type'] === 'text') {
                        $field_data['container_shape'] = 'rectangle';
                        $field_data['z_index_order'] = 0;
                        $field_data['font_size'] = max(1, intval($field_data['font_size']));
                        $field_data['line_height_multiplier'] = max(0.5, floatval($field_data['line_height_multiplier']));
                    } elseif ($field_data['field_type'] === 'image') {
                        $field_data['font_family'] = null; $field_data['font_size'] = null; $field_data['font_color'] = null; $field_data['alignment'] = null; $field_data['line_height_multiplier'] = null; $field_data['default_text'] = null;
                        $field_data['container_shape'] = !empty($field_data['container_shape']) ? $field_data['container_shape'] : 'rectangle';
                        $field_data['z_index_order'] = isset($field_data['z_index_order']) ? intval($field_data['z_index_order']) : 0;
                    }
                    $saved_field_result = bcek_db_insert_update_field( $field_data, $field_id ?: null );
                    if ($saved_field_result !== false) $fields_saved_count++;
                }
            }

            if (isset($_POST['bcek_fields_order']) && !empty($_POST['bcek_fields_order'])) {
                $field_order = array_map('intval', explode(',', $_POST['bcek_fields_order']));
                foreach ($field_order as $position => $field_id) {
                    if ($field_id > 0) {
                        bcek_db_update_field_order($field_id, $position);
                    }
                }
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Template salvo com sucesso. %d campos processados.', 'bcek' ), $fields_saved_count ) . "</p></div>";
            if (!$is_editing && $saved_template_id_or_result && $template_id === 0) { $template_id = $saved_template_id_or_result; $is_editing = true; }
            $template_data = bcek_db_get_template_by_id( $template_id ); $fields_data = bcek_db_get_fields_for_template( $template_id ); 
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Erro ao salvar dados principais do template.', 'bcek' ) . '</p></div>';
        }
    }

    $font_options = array( 'Montserrat-Regular' => 'Montserrat Regular', 'Montserrat-Bold' => 'Montserrat Bold', 'Montserrat-Black' => 'Montserrat Black' );
    $alignment_options = array( 'left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita', 'justify' => 'Justificado' );
    $field_type_options = array( 'text' => 'Campo de Texto', 'image' => 'Campo de Imagem' );
    $container_shape_options = array( 'rectangle' => 'Retangular', 'circle' => 'Circular' );
    $z_index_options = array( 0 => 'Por Baixo da Imagem Base', 1 => 'Por Cima da Imagem Base'); 

    ?>
    <div class="wrap bcek-admin-wrap">
        <h1><?php echo $is_editing ? __( 'Editar Template', 'bcek' ) : __( 'Adicionar Novo Template', 'bcek' ); ?></h1>
        <form method="POST" action="" id="post">
            <?php wp_nonce_field( 'bcek_save_template_action', 'bcek_save_template_nonce' ); ?>
            <input type="hidden" name="bcek_template_id" value="<?php echo $template_id; ?>">
            <input type="hidden" id="bcek_removed_fields_input" name="bcek_removed_fields" value="">
            <input type="hidden" id="bcek_fields_order" name="bcek_fields_order" value="">
            <div id="bcek-admin-editor-layout" style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div id="bcek-admin-form-fields" style="flex: 2; min-width: 450px;">
                    <table class="form-table"><tbody><tr><th scope="row"><label for="bcek_template_name"><?php _e( 'Nome do Template', 'bcek' ); ?></label></th><td><input name="bcek_template_name" type="text" id="bcek_template_name" value="<?php echo esc_attr( $template_data->name ?? '' ); ?>" class="regular-text bcek-preview-input" required></td></tr><tr><th scope="row"><label for="bcek_base_image_button"><?php _e( 'Imagem Base', 'bcek' ); ?></label></th><td><input type="hidden" name="bcek_base_image_id" id="bcek_base_image_id" value="<?php echo esc_attr( $template_data->base_image_id ?? 0 ); ?>"><input type="hidden" name="bcek_base_image_url" id="bcek_base_image_url" value="<?php echo esc_attr( $template_data->base_image_url ?? '' ); ?>"><button type="button" class="button" id="bcek_base_image_button"><?php _e( 'Selecionar Imagem', 'bcek' ); ?></button><button type="button" class="button" id="bcek_remove_base_image_button" style="<?php echo empty($template_data->base_image_url) ? 'display:none;' : ''; ?>"><?php _e( 'Remover Imagem', 'bcek' ); ?></button></td></tr></tbody></table>
                    <hr>
                    <h2><?php _e( 'Campos do Template', 'bcek' ); ?></h2>
                    <div id="bcek_fields_container">
                        <?php
                        if ( ! empty( $fields_data ) ) {
                            foreach ( $fields_data as $index => $field ) {
                                bcek_render_field_template( $index, $font_options, $alignment_options, $field_type_options, $container_shape_options, $z_index_options, $field );
                            }
                        }
                        ?>
                    </div>
                    <button type="button" class="button" id="bcek_add_field_button"><?php _e( 'Adicionar Novo Campo', 'bcek' ); ?></button>
                </div>
                <div id="bcek-admin-preview-area" style="flex: 1; min-width: 350px; position: sticky; top: 50px; background-color: #f8f9fa; padding:15px; border: 1px solid #ddd;">
                    <h3><?php _e('Preview do Template', 'bcek'); ?></h3>
                    <p class="description" style="margin-top:0; margin-bottom: 5px;"><?php _e('Arraste e redimensione os campos diretamente no preview.', 'bcek'); ?></p>
                    <div id="bcek-admin-canvas-container" style="position: relative; border: 1px dashed #ccc; background-color: #fff; min-height: 200px; overflow: hidden; touch-action: none;">
                        <img id="bcek_admin_base_image_preview_img" src="<?php echo esc_url( $template_data->base_image_url ?? '' ); ?>" style="max-width: 100%; display: block; opacity: 0.7;">
                        <canvas id="bcek_admin_fields_preview_canvas" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%;"></canvas>
                        <!-- NOVO: Contêiner para os elementos interativos -->
                        <div id="bcek-admin-interactive-overlay" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; z-index: 10;"></div>
                    </div>
                </div>
            </div>
            <p class="submit" style="clear: both; padding-top: 20px;"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $is_editing ? __( 'Salvar Alterações', 'bcek' ) : __( 'Criar Template', 'bcek' ); ?>"><?php if ($is_editing): ?><a href="<?php echo admin_url('admin.php?page=brand-center-kothe-templates'); ?>" class="button" style="margin-left: 10px;"><?php _e( 'Cancelar', 'bcek' ); ?></a><?php endif; ?></p>
            <!-- TEMPLATE PARA NOVO CAMPO -->
            <script type="text/template" id="bcek-field-template">
            <?php
            bcek_render_field_template('{{FIELD_INDEX}}', $font_options, $alignment_options, $field_type_options, $container_shape_options, $z_index_options, null);
            ?>
            </script>
        </form>
    </div>
    <!-- NOVO: Carrega a biblioteca interact.js -->
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('bcek_fields_container');
        var hiddenOrderInput = document.getElementById('bcek_fields_order');
        if (container && hiddenOrderInput && typeof Sortable !== 'undefined') {
            new Sortable(container, {
                animation: 150,
                handle: '.bcek-field-item',
                draggable: '.bcek-field-item',
                onSort: function () {
                    var order = [];
                    container.querySelectorAll('.bcek-field-item').forEach(function (item) {
                        var id = item.getAttribute('data-field-id');
                        if (id) order.push(id);
                    });
                    hiddenOrderInput.value = order.join(',');
                }
            });
            var initialOrder = [];
            container.querySelectorAll('.bcek-field-item').forEach(function (item) {
                var id = item.getAttribute('data-field-id');
                if (id) initialOrder.push(id);
            });
            hiddenOrderInput.value = initialOrder.join(',');
        }
    });
    </script>
    <?php
}

/**
 * Helper para renderizar o HTML de um único bloco de configuração de campo.
 */
function bcek_render_field_template( $index, $font_options, $alignment_options, $field_type_options, $container_shape_options, $z_index_options, $field_data = null ) { 
    $field_id = $field_data->field_id ?? 0; 
    $field_real_index = is_numeric($index) ? $index : '{{FIELD_INDEX}}'; 
    $display_index = is_numeric($index) ? ($index + 1) : '{{FIELD_INDEX_DISPLAY}}'; 
    $default_line_height = 1.3; 
    $current_field_type = $field_data->field_type ?? 'text'; 
    $current_z_index = $field_data->z_index_order ?? 0; 
    ?>
    <div class="bcek-field-item" data-index="<?php echo esc_attr($field_real_index); ?>" data-field-id="<?php echo esc_attr($field_id); ?>" data-field-type="<?php echo esc_attr($current_field_type); ?>" style="border:1px solid #ddd; margin-bottom:8px; background:#fafafa; cursor:move;">
        <input type="hidden" name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][id]" value="<?php echo esc_attr($field_id); ?>">
        <h4>
            <?php printf( __( 'Campo %s', 'bcek' ), $display_index ); ?>
            <select name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][field_type]" class="bcek-field-type-selector bcek-preview-input" style="margin-left: 10px;">
                <?php foreach ( $field_type_options as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_field_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button button-small bcek-remove-field-button" style="margin-left:10px; color: #a00; border-color: #a00; background: #f1f1f1;">Remover</button>
        </h4>
        <table class="form-table">
            <tr>
                <th><?php _e( 'Posição (X, Y)', 'bcek' ); ?></th>
                <td>
                    <input type="number" name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][pos_x]" value="<?php echo esc_attr( $field_data->pos_x ?? 0 ); ?>" class="small-text bcek-preview-input"> px,
                    <input type="number" name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][pos_y]" value="<?php echo esc_attr( $field_data->pos_y ?? 0 ); ?>" class="small-text bcek-preview-input"> px
                </td>
            </tr>
            <tr class="bcek-field-dimension-row"> 
                <th><?php _e( 'Tamanho do Bloco/Contêiner (Largura, Altura)', 'bcek' ); ?></th>
                <td>
                    <input type="number" name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][width]" value="<?php echo esc_attr( $field_data->width ?? 150 ); ?>" class="small-text bcek-preview-input"> px,
                    <input type="number" name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][height]" value="<?php echo esc_attr( $field_data->height ?? 50 ); ?>" class="small-text bcek-preview-input"> px
                </td>
            </tr>
            <tbody class="bcek-text-fields" style="display: <?php echo $current_field_type === 'text' ? 'table-row-group' : 'none'; ?>;">
                <tr><th><?php _e( 'Fonte', 'bcek' ); ?></th><td><select name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][font_family]" class="bcek-preview-input"><?php foreach ( $font_options as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( ($field_data->font_family ?? 'Montserrat-Regular'), $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
                <tr>
                    <th><?php _e( 'Tamanho da Fonte (px)', 'bcek' ); ?></th>
                    <td>
                        <input type="number" name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][font_size]" value="<?php echo esc_attr(max(1, $field_data->font_size ?? 16)); ?>" class="small-text bcek-preview-input" min="1">
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Espaçamento entre Linhas', 'bcek' ); ?></th>
                    <td>
                        <input type="number" step="0.1" min="0.5" max="5" name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][line_height_multiplier]" value="<?php echo esc_attr(max(0.5, $field_data->line_height_multiplier ?? $default_line_height)); ?>" class="small-text bcek-preview-input">
                        <p class="description"><?php _e('Ex: 1.0 para normal, 1.3 para padrão.', 'bcek'); ?></p>
                    </td>
                </tr>
                <tr><th><?php _e( 'Cor da Fonte', 'bcek' ); ?></th><td><input type="text" name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][font_color]" value="<?php echo esc_attr( $field_data->font_color ?? '#000000' ); ?>" class="bcek-color-picker bcek-preview-input"></td></tr>
                <tr><th><?php _e( 'Alinhamento', 'bcek' ); ?></th><td><select name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][alignment]" class="bcek-preview-input"><?php foreach ( $alignment_options as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( ($field_data->alignment ?? 'left'), $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
                <tr><th><?php _e( 'Texto Padrão', 'bcek' ); ?></th><td><textarea name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][default_text]" rows="3" class="large-text bcek-preview-input"><?php echo esc_textarea( $field_data->default_text ?? '' ); ?></textarea></td></tr>
            </tbody>
            <tbody class="bcek-image-fields" style="display: <?php echo $current_field_type === 'image' ? 'table-row-group' : 'none'; ?>;">
                <tr>
                    <th><?php _e( 'Formato do Contêiner', 'bcek' ); ?></th>
                    <td>
                        <select name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][container_shape]" class="bcek-preview-input">
                            <?php foreach ( $container_shape_options as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( ($field_data->container_shape ?? 'rectangle'), $value ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Ordem da Camada', 'bcek' ); ?></th>
                    <td>
                        <select name="bcek_fields[<?php echo esc_attr($field_real_index); ?>][z_index_order]" class="bcek-preview-input">
                            <?php foreach ( $z_index_options as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_z_index, $value ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Define se esta imagem aparece por cima ou por baixo da imagem base do template.', 'bcek');?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <hr style="border-style: dashed; border-color: #ddd; margin-top: 15px;">
    </div>
    <?php
}
?>