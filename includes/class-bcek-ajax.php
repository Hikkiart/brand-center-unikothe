<?php
/**
 * Classe para gerir todas as requisições AJAX do plugin.
 */

// Medida de segurança
if ( ! defined( 'WPINC' ) ) {
    die;
}

class BCEK_Ajax {

    /**
     * Construtor da classe. Adiciona os hooks para os handlers AJAX.
     */
    public function __construct() {
        // Registra o handler para ser chamado via AJAX para utilizadores logados.
        add_action( 'wp_ajax_bcek_generate_image', array( $this, 'generate_image_handler' ) );
    }

    /**
     * Manipulador AJAX para a ação 'bcek_generate_image'.
     * Valida os dados e chama a função de geração de imagem.
     */
    public function generate_image_handler() {
        // 1. Verificar Nonce de Segurança
        check_ajax_referer( 'bcek_generate_image_nonce', 'nonce' );

        // 2. Coletar e Sanitizar Dados da Requisição POST
        $template_id = isset( $_POST['template_id'] ) ? intval( $_POST['template_id'] ) : 0;
        $user_inputs = isset( $_POST['user_inputs'] ) && is_array($_POST['user_inputs']) ? wp_unslash($_POST['user_inputs']) : array();
        $user_filename = isset( $_POST['user_filename'] ) ? sanitize_text_field( wp_unslash($_POST['user_filename']) ) : '';
        $format = isset($_POST['format']) && $_POST['format'] === 'bmp' ? 'bmp' : 'png';
        
        // NOVO: Capturamos as imagens de overlay aqui e usamos wp_unslash por segurança
        $behind_overlay = isset($_POST['behind_overlay_data_url']) ? wp_unslash($_POST['behind_overlay_data_url']) : '';
        $above_overlay = isset($_POST['above_overlay_data_url']) ? wp_unslash($_POST['above_overlay_data_url']) : '';

        // 3. Validação dos Dados
        if ( ! $template_id ) {
            wp_send_json_error( array( 'message' => __( 'ID do template inválido recebido.', 'bcek' ) ) );
            return;
        }

        // 4. Buscar Dados do Template e Campos
        $template = BCEK_Database::get_template_by_id( $template_id );
        if ( ! $template ) { /* ... erro ... */ }

        $fields = BCEK_Database::get_fields_for_template( $template_id );

        // Passamos os overlays como novos parâmetros
        $result = BCEK_Image_Generator::generate( $template, $fields, $user_inputs, $user_filename, $format, $behind_overlay, $above_overlay );

        // 6. Enviar Resposta JSON
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 
                'message' => $result->get_error_message(),
                'wp_error_code' => $result->get_error_code()
            ) );
        } else {
            wp_send_json_success( $result );
        }
    }
}