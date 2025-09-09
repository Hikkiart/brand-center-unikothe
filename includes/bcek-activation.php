<?php
/**
 * Funções executadas na ativação do plugin.
 * Principalmente para a criação/atualização das tabelas da base de dados.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Função principal de ativação.
 * O WordPress chama esta função quando o plugin é ativado.
 */
function bcek_activate_plugin() {
    bcek_create_database_tables();
}

/**
 * Cria ou atualiza as tabelas personalizadas do plugin no banco de dados.
 */
function bcek_create_database_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // --- 1. TABELA DE TEMPLATES (COM A COLUNA CORRIGIDA) ---
    $table_name_templates = $wpdb->prefix . 'bcek_templates';
    $sql_templates = "CREATE TABLE $table_name_templates (
        template_id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) DEFAULT '' NOT NULL,
        base_image_url varchar(255) DEFAULT '' NOT NULL,
        base_image_id mediumint(9) DEFAULT 0 NOT NULL,
        category_id BIGINT(20) UNSIGNED DEFAULT NULL, -- <-- COLUNA ADICIONADA AQUI
        PRIMARY KEY  (template_id)
    ) $charset_collate;";
    dbDelta( $sql_templates );

    // --- 2. TABELA DE CAMPOS (INALTERADA) ---
    $table_name_fields = $wpdb->prefix . 'bcek_fields';
    $sql_fields = "CREATE TABLE $table_name_fields (
        field_id mediumint(9) NOT NULL AUTO_INCREMENT,
        template_id mediumint(9) NOT NULL,
        name varchar(255) DEFAULT '' NOT NULL,
        field_type varchar(50) DEFAULT 'text' NOT NULL,
        pos_x int(11) DEFAULT 0 NOT NULL,
        pos_y int(11) DEFAULT 0 NOT NULL,
        width int(11) DEFAULT 150 NOT NULL,
        height int(11) DEFAULT 50 NOT NULL,
        default_text text,
        font_family varchar(100),
        font_size int(11),
        font_weight varchar(50),
        font_color varchar(7),
        alignment varchar(20),
        container_shape VARCHAR(50) NOT NULL DEFAULT 'rectangle',
        z_index_order int(11) DEFAULT 1 NOT NULL,
        field_order int(11) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (field_id),
        KEY template_id (template_id)
    ) $charset_collate;";
    dbDelta( $sql_fields );

    // --- 3. NOVA TABELA DE CATEGORIAS ---
    $table_name_categories = $wpdb->prefix . 'bcek_categories';
    $sql_categories = "CREATE TABLE $table_name_categories (
      category_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(200) NOT NULL,
      slug VARCHAR(200) NOT NULL,
      PRIMARY KEY  (category_id),
      UNIQUE KEY slug (slug)
    ) $charset_collate;";
    dbDelta( $sql_categories );
}