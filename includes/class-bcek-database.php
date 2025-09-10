<?php
/**
 * Classe para gerir todas as interações com o banco de dados.
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

class BCEK_Database {

    /**
     * Obtém todos os templates da tabela.
     * @return array|object|null
     */
    public static function get_all_templates() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bcek_templates';
        return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY name ASC" );
    }

    /**
     * Obtém um único template pelo seu ID.
     * @param int $template_id
     * @return object|null
     */
    public static function get_template_by_id( $template_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bcek_templates';
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE template_id = %d", $template_id ) );
    }

    /**
     * Obtém todos os campos para um determinado template.
     * @param int $template_id
     * @return array|object|null
     */
    public static function get_fields_for_template( $template_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bcek_fields';
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE template_id = %d ORDER BY field_order ASC", $template_id ) );
    }

    /**
     * Insere ou atualiza um template.
     * @param array $data
     * @param int|null $template_id
     * @return int|false
     */
    public static function insert_update_template( $data, $template_id = null ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bcek_templates';

        if ( $template_id ) {
            $wpdb->update( $table_name, $data, array( 'template_id' => $template_id ) );
            return $template_id;
        } else {
            $wpdb->insert( $table_name, $data );
            return $wpdb->insert_id;
        }
    }

    /**
     * Insere ou atualiza um campo de template.
     * @param array $data
     * @param int|null $field_id
     * @return int|false
     */
    public static function insert_update_field( $data, $field_id = null ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bcek_fields';

        if ( $field_id ) {
            $wpdb->update( $table_name, $data, array( 'field_id' => $field_id ) );
            return $field_id;
        } else {
            $wpdb->insert( $table_name, $data );
            return $wpdb->insert_id;
        }
    }

    /**
     * Apaga um único campo pelo seu ID.
     * @param int $field_id
     * @return bool
     */
    public static function delete_field( $field_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bcek_fields';
        $result = $wpdb->delete( $table_name, array( 'field_id' => $field_id ), array( '%d' ) );
        return $result !== false;
    }

    /**
     * --- FUNÇÃO CORRIGIDA ---
     * Apaga um template e todos os seus campos associados.
     * @param int $template_id O ID do template a ser apagado.
     * @return bool Retorna true em caso de sucesso, false em caso de falha.
     */
    public static function delete_template( $template_id ) {
        global $wpdb;
        $templates_table = $wpdb->prefix . 'bcek_templates';
        $fields_table = $wpdb->prefix . 'bcek_fields';

        // Garante que o ID é um número inteiro válido.
        $template_id = intval($template_id);
        if ($template_id <= 0) {
            return false;
        }

        // 1. Apaga todos os campos associados a este template.
        // A função $wpdb->delete retorna o número de linhas apagadas ou `false` em caso de erro.
        $wpdb->delete( $fields_table, array( 'template_id' => $template_id ), array( '%d' ) );

        // 2. Apaga o template principal.
        $result = $wpdb->delete( $templates_table, array( 'template_id' => $template_id ), array( '%d' ) );

        // 3. Retorna true apenas se a exclusão do template principal tiver sido bem-sucedida.
        // O `!== false` é importante para tratar casos em que 0 linhas são apagadas (o que não é um erro).
        return $result !== false;
    }
}