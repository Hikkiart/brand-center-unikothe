<?php
/**
 * Definição e gestão de shortcodes para o plugin Brand Center Editor Kothe.
 */

// Medida de segurança: impede o acesso direto ao ficheiro.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Regista o shortcode principal do plugin: [brand_center_editor].
 * Este shortcode será usado para exibir a interface de edição do utilizador numa página ou post.
 */
function bcek_register_shortcodes() {
    // Associa a tag do shortcode 'brand_center_editor' à função de callback 'bcek_render_editor_shortcode'.
    add_shortcode( 'brand_center_editor', 'bcek_render_editor_shortcode' );
}
// Adiciona a função ao hook 'init' do WordPress para garantir que o shortcode seja registado cedo no carregamento.
add_action( 'init', 'bcek_register_shortcodes' );

/**
 * Função de callback para o shortcode [brand_center_editor].
 * É responsável por buscar os dados do template, enfileirar os scripts/estilos necessários,
 * passar dados para o JavaScript e renderizar o HTML da interface de edição do utilizador.
 *
 * @param array $atts Atributos fornecidos ao shortcode. Espera-se 'template_id'.
 * @return string HTML da interface do editor, ou uma mensagem de erro se o template não for válido.
 */
function bcek_render_editor_shortcode( $atts ) {
    // Define os atributos padrão para o shortcode e mescla-os com os atributos fornecidos.
    $atts = shortcode_atts( array(
        'template_id' => 0,
    ), $atts, 'brand_center_editor' );

    // Converte o template_id para um inteiro para segurança e validação.
    $template_id = intval( $atts['template_id'] );

    // Validação básica: se o template_id não for válido, retorna uma mensagem de erro.
    if ( ! $template_id ) {
        return '<p>' . __( 'Erro: ID do template não fornecido ou inválido no shortcode.', 'bcek' ) . '</p>';
    }

    // Busca os dados do template principal a partir do banco de dados usando o ID fornecido.
    $template = bcek_db_get_template_by_id( $template_id );

    if ( ! $template ) {
        return '<p>' . sprintf( __( 'Erro: Template com ID %d não encontrado.', 'bcek' ), $template_id ) . '</p>';
    }

    // Busca todos os campos associados a este template.
    $fields = bcek_db_get_fields_for_template( $template_id );

    // Enfileira os scripts e estilos necessários para a interface do editor.
    wp_enqueue_style( 'cropper-style', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css', array(), '1.5.12' );
    wp_enqueue_style( 'bcek-editor-style', BCEK_PLUGIN_URL . 'assets/css/bcek-editor-style.css', array( 'cropper-style' ), BCEK_VERSION );
    wp_enqueue_script( 'cropper-script', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js', array(), '1.5.12', true );
    wp_enqueue_script( 'bcek-editor-script', BCEK_PLUGIN_URL . 'assets/js/bcek-editor-script.js', array( 'jquery', 'cropper-script' ), BCEK_VERSION, true );

    // Dados para o JavaScript.
    $editor_data = array(
        'template' => $template,
        'fields'   => $fields,
        'nonce'    => wp_create_nonce( 'bcek_generate_image_nonce' )
    );
    wp_localize_script( 'bcek-editor-script', 'bcek_data', $editor_data );

    wp_localize_script( 'bcek-editor-script', 'bcek_editor_ajax', array(
        'ajax_url'  => admin_url( 'admin-ajax.php' ),
        'fonts_url' => BCEK_PLUGIN_URL . 'assets/fonts/'
    ));

    // Captura o HTML da interface do editor e o retorna.
    ob_start();
    include BCEK_PLUGIN_DIR . 'templates/editor-interface.php';
    return ob_get_clean();
}
?>
