<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Insere ou atualiza um template no banco de dados.
 */
function bcek_db_insert_update_template( $data, $template_id = null ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bcek_templates';
    $defaults = array( 'name' => '', 'base_image_id' => 0, 'base_image_url' => '' );
    $data = wp_parse_args( $data, $defaults );
    $data['name'] = sanitize_text_field($data['name']);
    $data['base_image_url'] = esc_url_raw($data['base_image_url']);
    $data['base_image_id'] = intval($data['base_image_id']);

    if ( $template_id ) { 
        $result = $wpdb->update( $table_name, $data, array( 'template_id' => $template_id ) );
        if ($result === false) {
            // Em vez de error_log, vamos tentar exibir na tela para depuração mais fácil
            echo "<div style='background-color:pink; color:red; padding:10px; border:1px solid red;'>BCEK DB ERROR (Update Template): " . esc_html($wpdb->last_error) . "</div>";
        }
        return $result !== false ? $template_id : false;
    } else { 
        $result = $wpdb->insert( $table_name, $data );
        if ($result === false) {
            echo "<div style='background-color:pink; color:red; padding:10px; border:1px solid red;'>BCEK DB ERROR (Insert Template): " . esc_html($wpdb->last_error) . "</div>";
            return false;
        }
        return $wpdb->insert_id; 
    }
}

/**
 * Busca um template pelo ID.
 */
function bcek_db_get_template_by_id( $template_id ) {
    global $wpdb; $table_name = $wpdb->prefix . 'bcek_templates';
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE template_id = %d", $template_id ) );
}

/**
 * Busca todos os templates.
 */
function bcek_db_get_all_templates() {
    global $wpdb; $table_name = $wpdb->prefix . 'bcek_templates';
    return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY name ASC" );
}

/**
 * Deleta um template e seus campos associados.
 */
function bcek_db_delete_template( $template_id ) {
    global $wpdb; $table_templates = $wpdb->prefix . 'bcek_templates'; $table_fields = $wpdb->prefix . 'bcek_fields';
    $wpdb->delete( $table_fields, array( 'template_id' => $template_id ), array( '%d' ) );
    $result = $wpdb->delete( $table_templates, array( 'template_id' => $template_id ), array( '%d' ) );
    return $result !== false;
}

/**
 * Insere ou atualiza um campo de texto.
 */
function bcek_db_insert_update_field( $data, $field_id = null ) {
    global $wpdb;
    // $wpdb->show_errors(); // Descomente esta linha para o WordPress tentar mostrar erros SQL na tela (pode ser intrusivo)
    
    $table_name = $wpdb->prefix . 'bcek_fields';
    $defaults = array(
        'template_id' => 0, 'pos_x' => 0, 'pos_y' => 0, 'width' => 100, 'height' => 50,
        'font_family' => 'Montserrat-Regular', 'font_size' => 16, 'font_color' => '#000000',
        'alignment' => 'left', 'line_height_multiplier' => 1.3, 'default_text' => ''
    );
    $data = wp_parse_args( $data, $defaults );

    $data['template_id'] = intval($data['template_id']);
    $data['pos_x'] = intval($data['pos_x']); $data['pos_y'] = intval($data['pos_y']);
    $data['width'] = intval($data['width']); $data['height'] = intval($data['height']);
    $data['font_family'] = sanitize_text_field($data['font_family']);
    $data['font_size'] = intval($data['font_size']);
    $data['font_color'] = sanitize_hex_color($data['font_color']);
    $data['alignment'] = sanitize_text_field($data['alignment']);
    $data['line_height_multiplier'] = floatval($data['line_height_multiplier']);
    $data['default_text'] = sanitize_textarea_field($data['default_text']);

    if (empty($data['template_id'])) {
        echo "<div style='background-color:pink; color:red; padding:10px; border:1px solid red;'>BCEK DB DEBUG: ERRO FATAL - Tentativa de salvar campo com template_id inválido ou zero. Dados do campo: <pre>" . esc_html(print_r($data, true)) . "</pre></div>";
        return false; 
    }
    
    if ( $field_id && $field_id > 0 ) { 
        $result = $wpdb->update( $table_name, $data, array( 'field_id' => $field_id ) );
        if ($result === false) {
            echo "<div style='background-color:pink; color:red; padding:10px; border:1px solid red;'>BCEK DB DEBUG (Update Field Error): " . esc_html($wpdb->last_error) . "<br>Dados: <pre>" . esc_html(print_r($data, true)) . "</pre>Where: field_id = " . esc_html($field_id) . "</div>";
        }
        return $result !== false ? $field_id : false;
    } else {
        $result = $wpdb->insert( $table_name, $data );
        if ($result === false) {
            echo "<div style='background-color:pink; color:red; padding:10px; border:1px solid red;'>BCEK DB DEBUG (Insert Field Error): " . esc_html($wpdb->last_error) . "<br>Dados: <pre>" . esc_html(print_r($data, true)) . "</pre></div>";
            return false;
        }
        return $wpdb->insert_id; 
    }
}

/**
 * Busca campos de um template específico.
 */
function bcek_db_get_fields_for_template( $template_id ) {
    global $wpdb; $table_name = $wpdb->prefix . 'bcek_fields';
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE template_id = %d ORDER BY field_id ASC", $template_id ) );
}

/**
 * Deleta um campo específico.
 */
function bcek_db_delete_field( $field_id ) {
    global $wpdb; $table_name = $wpdb->prefix . 'bcek_fields';
    return $wpdb->delete( $table_name, array( 'field_id' => $field_id ), array( '%d' ) ) !== false;
}
?>