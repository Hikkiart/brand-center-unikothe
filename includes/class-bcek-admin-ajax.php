<?php
/**
 * Classe para gerir requisições AJAX do painel de administração (front-end e back-end).
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class BCEK_Admin_Ajax {

    public function __construct() {
        // Hook para salvar os dados do template (usado pelo seu editor)
        add_action( 'wp_ajax_bcek_save_template_data', array( $this, 'save_template_data_handler' ) );
        
        // Hook para o upload da imagem base (usado pelo seu editor)
        add_action( 'wp_ajax_bcek_handle_base_image_upload', array( $this, 'handle_base_image_upload' ) );
        
        add_action( 'wp_ajax_bcek_delete_template', array( $this, 'delete_template_handler' ) );
        
        add_action( 'wp_ajax_bcek_add_category', array( $this, 'add_category_handler' ) );
        add_action( 'wp_ajax_bcek_delete_category', array( $this, 'delete_category_handler' ) );
        
        // Se precisar que utilizadores não logados usem, descomente a linha abaixo
        // add_action( 'wp_ajax_nopriv_bcek_handle_base_image_upload', array( $this, 'handle_base_image_upload' ) );
    }

    /**
     * Manipulador AJAX para o upload da imagem base.
     * Usa as funções nativas do WordPress para um upload seguro.
     */
    public function handle_base_image_upload() {
        // 1. Verificações de segurança
        check_ajax_referer('bcek_save_template_nonce', 'nonce');
        if ( ! current_user_can('upload_files') ) {
            wp_send_json_error(['message' => 'Permissão insuficiente para fazer upload de ficheiros.'], 403);
            return;
        }
        if ( empty($_FILES['base_image_file']) ) {
            wp_send_json_error(['message' => 'Nenhum ficheiro recebido.'], 400);
            return;
        }

        // 2. Inclui os ficheiros necessários do WordPress
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // 3. Deixa o WordPress lidar com o upload
        $attachment_id = media_handle_upload('base_image_file', 0);

        // 4. Envia a resposta
        if ( is_wp_error($attachment_id) ) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()], 500);
        } else {
            wp_send_json_success([
                'id' => $attachment_id, 
                'url' => wp_get_attachment_url($attachment_id)
            ]);
        }
    }

    /**
     * Manipulador AJAX para salvar todos os dados do template.
     * Refatorado para maior clareza e segurança.
     */
    public function save_template_data_handler() {
        // --- DEBUG NO SERVIDOR ---
        // Usa a sua função de log para guardar tudo o que foi recebido via POST.
        bcek_log_to_file($_POST, 'DADOS RECEBIDOS PELA FUNÇÃO save_template_data_handler');

        // 1. Verificações de segurança
        check_ajax_referer( 'bcek_save_template_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { 
            wp_send_json_error(['message' => 'Permissão negada.']); 
            return; 
        }
        
        // 2. Coleta e sanitiza todos os dados recebidos do JavaScript
        $template_id = isset( $_POST['template_id'] ) ? intval( $_POST['template_id'] ) : 0;
        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : 'Novo Template';
        $base_image_id = isset( $_POST['base_image_id'] ) ? intval( $_POST['base_image_id'] ) : 0;
        $base_image_url = isset( $_POST['base_image_url'] ) ? esc_url_raw( $_POST['base_image_url'] ) : '';
        
        // --- A CORREÇÃO ESTÁ AQUI ---
        // Se a categoria for vazia ou 0, definimos como NULL para o banco de dados.
        $category_id = isset( $_POST['category_id'] ) && intval($_POST['category_id']) > 0 ? intval( $_POST['category_id'] ) : null;

        $fields = isset($_POST['fields']) ? (array) $_POST['fields'] : array();
        $removed_ids = isset($_POST['removed_ids']) ? array_map('intval', (array) $_POST['removed_ids']) : array();

        $is_new = ($template_id === 0);

        // Prepara os dados para a tabela principal de templates
        $template_data = [
            'name' => $name,
            'base_image_id' => $base_image_id,
            'base_image_url' => $base_image_url,
            'category_id' => $category_id
        ];

        // Adiciona um log para depuração
        bcek_log_to_file($template_data, 'Dados a serem salvos na tabela de templates');

        // 3. Tenta inserir ou atualizar o template
        $saved_template_id_or_result = BCEK_Database::insert_update_template($template_data, $is_new ? null : $template_id);

        if ( $saved_template_id_or_result === false || $saved_template_id_or_result === 0 ) {
            wp_send_json_error(['message' => 'Erro ao salvar os dados principais do template.']);
            return;
        }

        $current_template_id = $is_new ? $saved_template_id_or_result : $template_id;

        // 4. Processa os campos (remover, adicionar, atualizar)
        if ( ! empty($removed_ids) ) {
            foreach ($removed_ids as $field_id) {
                if ($field_id > 0) BCEK_Database::delete_field($field_id);
            }
        }
        
        if ( ! empty($fields) ) {
            foreach ($fields as $field_post_data) {
                $field_id = isset($field_post_data['id']) ? intval($field_post_data['id']) : 0;
                $field_data = [
                    'template_id'    => $current_template_id,
                    'name'           => isset($field_post_data['name']) ? sanitize_text_field($field_post_data['name']) : '',
                    'field_type'     => isset($field_post_data['field_type']) ? sanitize_text_field($field_post_data['field_type']) : 'text',
                    'pos_x'          => isset($field_post_data['pos_x']) ? intval($field_post_data['pos_x']) : 0,
                    'pos_y'          => isset($field_post_data['pos_y']) ? intval($field_post_data['pos_y']) : 0,
                    'width'          => isset($field_post_data['width']) ? intval($field_post_data['width']) : 150,
                    'height'         => isset($field_post_data['height']) ? intval($field_post_data['height']) : 50,
                    'default_text'   => isset($field_post_data['default_text']) ? sanitize_textarea_field($field_post_data['default_text']) : '',
                    'font_family'    => isset($field_post_data['font_family']) ? sanitize_text_field($field_post_data['font_family']) : 'Montserrat',
                    'font_size'      => isset($field_post_data['font_size']) ? intval($field_post_data['font_size']) : 16,
                    'font_weight'    => isset($field_post_data['font_weight']) ? sanitize_text_field($field_post_data['font_weight']) : '400',
                    'font_color'     => isset($field_post_data['font_color']) ? sanitize_hex_color($field_post_data['font_color']) : '#000000',
                    'alignment'      => isset($field_post_data['alignment']) ? sanitize_text_field($field_post_data['alignment']) : 'left',
                    'container_shape'=> isset($field_post_data['container_shape']) ? sanitize_text_field($field_post_data['container_shape']) : 'rectangle',
                    'z_index_order'  => isset($field_post_data['z_index_order']) ? intval($field_post_data['z_index_order']) : 1,
                ];
                BCEK_Database::insert_update_field($field_data, $field_id ?: null);
            }
        }

        // 5. Prepara e envia a resposta de sucesso
        $response_data = [
            'message' => 'Template salvo com sucesso!',
            'is_new'  => $is_new,
        ];
        
        if ( $is_new ) {
            // Gera o URL de redirecionamento para o JavaScript
            $response_data['redirect_url'] = add_query_arg([
                'action' => 'edit',
                'template_id' => $current_template_id
            ], wp_get_referer());
        }

        wp_send_json_success($response_data);
    }
    
    /**
     * Manipulador AJAX para apagar um template.
     */
    public function delete_template_handler() {
        // 1. Verificações de segurança
        if ( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'bcek_delete_template_nonce') ) {
            wp_send_json_error(['message' => 'Verificação de segurança falhou.'], 403);
            return;
        }
        if ( ! current_user_can('manage_options') ) {
            wp_send_json_error(['message' => 'Permissão negada.'], 403);
            return;
        }
        if ( ! isset($_POST['template_id']) || ! is_numeric($_POST['template_id']) ) {
            wp_send_json_error(['message' => 'ID do template inválido.'], 400);
            return;
        }

        $template_id = intval($_POST['template_id']);

        // 2. Tenta apagar o template usando a função da classe de base de dados
        $deleted = BCEK_Database::delete_template($template_id);

        // 3. Envia a resposta
        if ( $deleted ) {
            wp_send_json_success(['message' => 'Template apagado com sucesso!']);
        } else {
            wp_send_json_error(['message' => 'Ocorreu um erro ao apagar o template do banco de dados.']);
        }
    }
    
    // --- NOVOS MANIPULADORES AJAX PARA CATEGORIAS ---

    public function add_category_handler() {
        check_ajax_referer('bcek_save_template_nonce', 'nonce');
        if ( ! current_user_can('manage_options') ) { wp_send_json_error(['message' => 'Acesso negado.']); return; }

        $name = isset($_POST['category_name']) ? sanitize_text_field($_POST['category_name']) : '';
        if ( empty($name) ) { wp_send_json_error(['message' => 'O nome da categoria não pode estar vazio.']); return; }

        $new_category_id = BCEK_Database::insert_category($name);

        if ($new_category_id) {
            wp_send_json_success([
                'message' => 'Categoria adicionada!',
                'category' => [
                    'id' => $new_category_id,
                    'name' => $name,
                    'slug' => sanitize_title($name)
                ]
            ]);
        } else {
            wp_send_json_error(['message' => 'Erro ao adicionar a categoria.']);
        }
    }

    public function delete_category_handler() {
        check_ajax_referer('bcek_save_template_nonce', 'nonce');
        if ( ! current_user_can('manage_options') ) { wp_send_json_error(['message' => 'Acesso negado.']); return; }

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        if ( $category_id <= 0 ) { wp_send_json_error(['message' => 'ID de categoria inválido.']); return; }

        if ( BCEK_Database::delete_category($category_id) ) {
            wp_send_json_success(['message' => 'Categoria apagada.']);
        } else {
            wp_send_json_error(['message' => 'Erro ao apagar a categoria.']);
        }
    }
}