<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Insere ou atualiza um template no banco de dados.
 * VERSÃO RESTAURADA para corresponder ao seu código funcional.
 */
function bcek_db_insert_update_template( $data, $template_id = null ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bcek_templates';
    
    // Usa a sua lógica original, sem o campo de debug.
    $defaults = array( 'name' => '', 'base_image_id' => 0, 'base_image_url' => '' );
    $data = wp_parse_args( $data, $defaults );
    $data['name'] = sanitize_text_field($data['name']);
    $data['base_image_url'] = esc_url_raw($data['base_image_url']);
    $data['base_image_id'] = intval($data['base_image_id']);

    if ( $template_id ) { 
        $result = $wpdb->update( $table_name, $data, array( 'template_id' => $template_id ) );
        if ($result === false) {
            error_log("BCEK DB ERROR (Update Template): " . $wpdb->last_error);
        }
        return $result !== false ? $template_id : false;
    } else { 
        $result = $wpdb->insert( $table_name, $data );
        if ($result === false) {
            error_log("BCEK DB ERROR (Insert Template): " . $wpdb->last_error);
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
 * Insere ou atualiza um campo.
 */
function bcek_db_insert_update_field( $data, $field_id = null ) {
    global $wpdb;
    $wpdb->show_errors(true);
    
    $table_name = $wpdb->prefix . 'bcek_fields';
    $defaults = array(
        'template_id' => 0, 'field_type' => 'text', 'pos_x' => 0, 'pos_y' => 0, 'width' => 100, 'height' => 50,
        'font_family' => 'Montserrat-Regular', 'font_size' => 16, 'font_color' => '#000000',
        'alignment' => 'left', 'line_height_multiplier' => 1.3, 'default_text' => '',
        'container_shape' => 'rectangle', 'z_index_order' => 0,
        'field_order' => 0
    );
    $data = wp_parse_args( $data, $defaults );

    if (empty($data['template_id'])) {
        error_log("BCEK DB DEBUG: ERRO FATAL - Tentativa de salvar campo com template_id inválido ou zero.");
        return false; 
    }
    
    if ( $field_id && $field_id > 0 ) { 
        $result = $wpdb->update( $table_name, $data, array( 'field_id' => $field_id ) );
        if ($result === false) {
            error_log("BCEK DB DEBUG (Update Field Error): " . $wpdb->last_error . " | Dados: " . print_r($data, true));
        }
        return $result !== false ? $field_id : false;
    } else {
        $result = $wpdb->insert( $table_name, $data );
        if ($result === false) {
            error_log("BCEK DB DEBUG (Insert Field Error): " . $wpdb->last_error . " | Dados: " . print_r($data, true));
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
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE template_id = %d ORDER BY field_order ASC, field_id ASC", $template_id ) );
}

/**
 * Deleta um campo específico.
 */
function bcek_db_delete_field( $field_id ) {
    global $wpdb; $table_name = $wpdb->prefix . 'bcek_fields';
    return $wpdb->delete( $table_name, array( 'field_id' => $field_id ), array( '%d' ) ) !== false;
}

/**
 * Atualiza o campo field_order de um campo de template.
 */
function bcek_db_update_field_order($field_id, $order) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bcek_fields';
    return $wpdb->update(
        $table_name,
        array('field_order' => intval($order)),
        array('field_id' => intval($field_id))
    );
}