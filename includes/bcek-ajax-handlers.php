<?php
/**
 * Manipuladores para requisições AJAX do plugin Brand Center Editor Kothe.
 */

// Medida de segurança: impede o acesso direto ao ficheiro.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Manipulador AJAX para a ação 'bcek_generate_image'.
 * Adiciona logging para depuração.
 */
function bcek_ajax_generate_image() {
    // 1. Verificar Nonce de Segurança (Reativado)
    check_ajax_referer( 'bcek_generate_image_nonce', 'nonce' );

    // 2. Coletar e Sanitizar Dados da Requisição POST
    $template_id = isset( $_POST['template_id'] ) ? intval( $_POST['template_id'] ) : 0;
    $user_inputs = isset( $_POST['user_inputs'] ) && is_array($_POST['user_inputs']) ? wp_unslash($_POST['user_inputs']) : array();
    $user_filename = isset( $_POST['user_filename'] ) ? sanitize_text_field( wp_unslash($_POST['user_filename']) ) : '';
    $format = isset($_POST['format']) && $_POST['format'] === 'bmp' ? 'bmp' : 'png'; 

    // --- LOGGING NO SERVIDOR ---
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log("==================================================");
        error_log("--- BCEK DEBUG INICIADO: " . date("Y-m-d H:i:s") . " ---");
        error_log("Template ID Recebido: " . $template_id);
        error_log("Dados de User Inputs Recebidos: \n" . print_r($user_inputs, true));
    }
    // -------------------------

    // 3. Validação dos Dados
    if ( ! $template_id ) {
        wp_send_json_error( array( 'message' => __( 'ID do template inválido recebido.', 'bcek' ) ) );
    }

    // 4. Buscar Dados do Template e Campos
    $template = bcek_db_get_template_by_id( $template_id );
    if ( ! $template ) {
        wp_send_json_error( array( 'message' => __( 'Template não encontrado no banco de dados para o ID: ', 'bcek' ) . $template_id ) );
    }

    $fields = bcek_db_get_fields_for_template( $template_id );
    
    // --- LOGGING NO SERVIDOR ---
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log("Campos recuperados da Base de Dados para o Template: \n" . print_r($fields, true));
    }
    // -------------------------


    // 5. Chamar a Função Principal de Geração de Imagem
    $result = bcek_generate_image_with_gd( $template, $fields, $user_inputs, $user_filename, $format );

    // 6. Enviar Resposta JSON
    if ( is_wp_error( $result ) ) {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log("ERRO na geração da imagem: " . $result->get_error_message());
        }
        wp_send_json_error( array( 
            'message' => $result->get_error_message(),
            'wp_error_code' => $result->get_error_code()
        ) );
    } else {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log("SUCESSO na geração. URL: " . $result['url']);
        }
        wp_send_json_success( $result );
    }

    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log("--- BCEK DEBUG FINALIZADO ---");
        error_log("==================================================");
    }
}
// Registra a função para ser chamada via AJAX pelo WordPress para utilizadores logados.
add_action( 'wp_ajax_bcek_generate_image', 'bcek_ajax_generate_image' );
