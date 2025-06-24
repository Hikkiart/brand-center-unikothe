<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Função executada na ativação do plugin.
 * Cria as tabelas personalizadas no banco de dados.
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

    // Tabela de Campos
    $table_name_fields = $wpdb->prefix . 'bcek_fields';
    $sql_fields = "CREATE TABLE $table_name_fields (
        field_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        template_id BIGINT(20) UNSIGNED NOT NULL,
        pos_x INT NOT NULL DEFAULT 0,
        pos_y INT NOT NULL DEFAULT 0,
        width INT NOT NULL DEFAULT 100,
        height INT NOT NULL DEFAULT 50,
        font_family VARCHAR(50) NOT NULL DEFAULT 'Montserrat-Regular',
        font_size INT NOT NULL DEFAULT 16,
        font_color VARCHAR(7) NOT NULL DEFAULT '#000000',
        alignment VARCHAR(10) NOT NULL DEFAULT 'left', /* left, center, right */
        line_height_multiplier FLOAT NOT NULL DEFAULT 1.3, /* Novo campo para multiplicador da altura da linha */
        default_text TEXT,
        PRIMARY KEY  (field_id),
        KEY template_id (template_id)
    ) $charset_collate;";
    dbDelta( $sql_fields );

    // Adiciona uma opção para versionamento do DB, se necessário no futuro
    // Se o plugin já estiver ativo e esta for uma atualização, dbDelta cuidará de adicionar a nova coluna.
    $current_version = get_option('bcek_db_version', '0.0.0');
    if (version_compare($current_version, BCEK_VERSION, '<')) {
        // Aqui você poderia rodar lógicas de atualização específicas se necessário,
        // mas dbDelta já tenta atualizar a estrutura da tabela.
        update_option('bcek_db_version', BCEK_VERSION);
    }
}
?>