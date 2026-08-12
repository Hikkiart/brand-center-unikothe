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

        // Garante que o category_id é um número
        if (isset($data['category_id'])) {
            $data['category_id'] = intval($data['category_id']);
        }

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
    
    /**
     * --- NOVAS FUNÇÕES PARA CATEGORIAS ---
     */

    /**
     * Obtém todas as categorias da tabela.
     * @return array|object|null
     */
    public static function get_all_categories() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bcek_template_categories';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
    }

    /**
     * Insere uma nova categoria.
     * @param string $name O nome da categoria.
     * @return int|false O ID da nova categoria ou false em caso de falha.
     */
    public static function insert_category($name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bcek_template_categories';
        
        $name = sanitize_text_field($name);
        $slug = sanitize_title($name);

        // Verifica se já existe uma categoria com o mesmo slug
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE slug = %s", $slug));
        if ($exists) {
            return false; // Evita duplicados
        }

        $wpdb->insert($table_name, ['name' => $name, 'slug' => $slug]);
        return $wpdb->insert_id;
    }

    /**
     * Apaga uma categoria e desassocia os templates.
     * @param int $category_id O ID da categoria a ser apagada.
     * @return bool True em sucesso, false em falha.
     */
    public static function delete_category($category_id) {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'bcek_template_categories';
        $templates_table = $wpdb->prefix . 'bcek_templates';
        
        $category_id = intval($category_id);
        if ($category_id <= 0) return false;

        // Desassocia os templates desta categoria (define category_id como 0)
        $wpdb->update(
            $templates_table,
            ['category_id' => 0],
            ['category_id' => $category_id]
        );

        // Apaga a categoria
        $result = $wpdb->delete($categories_table, ['category_id' => $category_id], ['%d']);
        return $result !== false;
    }

    /**
     * Obtém todos os templates agrupados por categoria.
     * @return array Array associativo com categorias e os seus templates.
     */
    public static function get_templates_grouped_by_category() {
        global $wpdb;
        $templates_table = $wpdb->prefix . 'bcek_templates';
        $categories_table = $wpdb->prefix . 'bcek_template_categories';

        $sql = "
            SELECT
                c.category_id,
                c.name AS category_name,
                c.slug AS category_slug,
                t.*
            FROM
                $templates_table AS t
            LEFT JOIN
                $categories_table AS c ON t.category_id = c.category_id
            ORDER BY
                c.name ASC, t.name ASC
        ";
        
        $results = $wpdb->get_results($sql);
        $grouped = [];

        // Agrupa os resultados
        foreach ($results as $template) {
            $cat_id = $template->category_id ?: 0;
            if (!isset($grouped[$cat_id])) {
                $grouped[$cat_id] = [
                    'category_name' => $template->category_name ?: __('Sem Categoria', 'bcek'),
                    'category_slug' => $template->category_slug ?: 'uncategorized',
                    'templates' => []
                ];
            }
            $grouped[$cat_id]['templates'][] = $template;
        }

        return $grouped;
    }
    
    /**
     * Duplica um template e todos os seus campos associados.
     *
     * @param int $template_id_to_duplicate O ID do template a ser clonado.
     * @return int|false O ID do novo template criado ou false em caso de falha.
     */
    public static function duplicate_template( $template_id_to_duplicate ) {
        global $wpdb;
        $templates_table = $wpdb->prefix . 'bcek_templates';
        $fields_table    = $wpdb->prefix . 'bcek_fields';

        // 1. Obter os dados do template original.
        $original_template = self::get_template_by_id( $template_id_to_duplicate );
        if ( ! $original_template ) {
            return false;
        }

        // 2. Preparar os dados para o novo template.
        $new_template_data = (array) $original_template;
        unset( $new_template_data['template_id'] ); // Remove o ID antigo
        $new_template_data['name'] = $original_template->name . ' (Cópia)'; // Adiciona "(Cópia)" ao nome

        // 3. Inserir o novo template na base de dados.
        if ( $wpdb->insert( $templates_table, $new_template_data ) === false ) {
            return false;
        }
        $new_template_id = $wpdb->insert_id;

        // 4. Obter todos os campos do template original.
        $original_fields = self::get_fields_for_template( $template_id_to_duplicate );

        if ( ! empty( $original_fields ) ) {
            // 5. Fazer um loop e inserir uma cópia de cada campo, associando ao novo template.
            foreach ( $original_fields as $field ) {
                $new_field_data = (array) $field;
                unset( $new_field_data['field_id'] ); // Remove o ID do campo antigo
                $new_field_data['template_id'] = $new_template_id; // Associa ao novo template
                
                $wpdb->insert( $fields_table, $new_field_data );
            }
        }

        return $new_template_id;
    }
}