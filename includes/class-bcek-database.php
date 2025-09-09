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

        // Garante que o category_id é um número ou null
        $data['category_id'] = isset($data['category_id']) && is_numeric($data['category_id']) ? intval($data['category_id']) : null;

        if ( $template_id ) {
            $wpdb->update( $table_name, $data, array( 'template_id' => $template_id ) );
            return $template_id;
        } else {
            // Lógica de inserção (com debug)
            $result = $wpdb->insert( $table_name, $data );

            // Se a inserção falhar ($result retorna false), regista o erro exato do MySQL.
            if ( $result === false ) {
                bcek_log_to_file($wpdb->last_error, 'ERRO DE INSERT NA BASE DE DADOS');
                return false; // Retorna false para indicar a falha.
            }
            // Se for bem-sucedido, retorna o ID da nova linha.
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
    
    // --- NOVAS FUNÇÕES PARA CATEGORIAS ---

    /**
     * Obtém todas as categorias.
     * @return array
     */
    public static function get_all_categories() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bcek_categories';
        return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY name ASC" );
    }

    /**
     * Insere uma nova categoria.
     * @param string $name O nome da categoria.
     * @return int|false O ID da nova categoria ou false em caso de erro.
     */
    public static function insert_category( $name ) {
        global $wpdb;
        $name = sanitize_text_field($name);
        $slug = sanitize_title($name); // Cria um slug amigável (o seu "id css")

        if ( empty($name) ) return false;

        $table_name = $wpdb->prefix . 'bcek_categories';
        $wpdb->insert(
            $table_name,
            array( 'name' => $name, 'slug' => $slug ),
            array( '%s', '%s' )
        );
        return $wpdb->insert_id;
    }

    /**
     * Apaga uma categoria e desassocia os templates.
     * @param int $category_id O ID da categoria a ser apagada.
     * @return bool
     */
    public static function delete_category( $category_id ) {
        global $wpdb;
        $category_id = intval($category_id);
        if ( $category_id <= 0 ) return false;

        // Desassocia os templates que usam esta categoria
        $templates_table = $wpdb->prefix . 'bcek_templates';
        $wpdb->update(
            $templates_table,
            array( 'category_id' => null ),
            array( 'category_id' => $category_id )
        );

        // Apaga a categoria
        $categories_table = $wpdb->prefix . 'bcek_categories';
        $result = $wpdb->delete( $categories_table, array( 'category_id' => $category_id ), array( '%d' ) );
        
        return $result !== false;
    }
}