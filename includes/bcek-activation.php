<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Função executada na ativação do plugin.
 * Cria e atualiza as tabelas personalizadas no banco de dados.
 */
function bcek_activate_plugin() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $charset_collate = $wpdb->get_charset_collate();

    // Tabela de Templates
    $table_name_templates = $wpdb->prefix . 'bcek_templates';
    $sql_templates = "CREATE TABLE $table_name_templates (
        template_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        base_image_id BIGINT(20) UNSIGNED DEFAULT 0,
        base_image_url VARCHAR(255) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (template_id)
    ) $charset_collate;";
    dbDelta( $sql_templates );

    // Tabela de Campos (COM AS NOVAS COLUNAS)
    $table_name_fields = $wpdb->prefix . 'bcek_fields';
    $sql_fields = "CREATE TABLE $table_name_fields (
        field_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        template_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) DEFAULT '' NOT NULL,
        field_type VARCHAR(20) DEFAULT 'text' NOT NULL,
        pos_x INT NOT NULL DEFAULT 0,
        pos_y INT NOT NULL DEFAULT 0,
        width INT NOT NULL DEFAULT 100,
        height INT NOT NULL DEFAULT 50,
        font_family VARCHAR(50),
        font_weight VARCHAR(20) DEFAULT '400',
        font_size INT,
        font_color VARCHAR(7),
        alignment VARCHAR(10),
        line_height_multiplier FLOAT DEFAULT 1.3,
        default_text TEXT,
        container_shape VARCHAR(20),
        z_index_order INT DEFAULT 1,
        field_order INT DEFAULT 0,
        PRIMARY KEY  (field_id),
        KEY template_id (template_id)
    ) $charset_collate;";
    dbDelta( $sql_fields );

    update_option('bcek_db_version', '1.2.0'); // Atualiza a versão para referência
}
?>