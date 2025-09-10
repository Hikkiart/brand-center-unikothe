<?php
/**
 * Classe para gerir a criação e renderização de shortcodes do plugin.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class BCEK_Shortcodes {

    /**
     * Construtor da classe. Adiciona o hook para registar os shortcodes.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_shortcodes' ) );
    }

    /**
     * Regista todos os shortcodes do plugin.
     */
    public function register_shortcodes() {
        // Shortcode para o editor do utilizador final (já existente)
        add_shortcode( 'brand_center_editor', array( $this, 'render_editor_shortcode' ) );

        // Shortcode para a nova interface de administração no front-end
        add_shortcode( 'bcek_admin_interface', array( $this, 'render_admin_interface_shortcode' ) );
    }

    /**
     * Renderiza a nova interface de administração unificada no front-end.
     * @return string O HTML da interface.
     */
    public function render_admin_interface_shortcode() {
        // 1. Apenas administradores podem ver esta página.
        if ( ! current_user_can( 'manage_options' ) ) {
            return '<p>' . __( 'Acesso restrito. Apenas administradores podem ver esta interface.', 'bcek' ) . '</p>';
        }

        // 2. Carrega os scripts e estilos para a nova interface.
        $this->enqueue_admin_interface_assets();
        
        // 3. Decide qual "tela" mostrar: a lista ou o editor.
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        
        ob_start();

        if ( $action === 'edit' || $action === 'add_new' ) {
            // Se a ação for 'edit' ou 'add_new', mostra o editor de templates.
            include BCEK_PLUGIN_DIR . 'templates/admin/frontend-editor-page.php';
        } else {
            // Por padrão, mostra a lista de templates.
            include BCEK_PLUGIN_DIR . 'templates/admin/templates-list-page.php';
        }

        $output = ob_get_clean();
        wp_reset_postdata(); // Previne conflitos com a página.
        return $output;
    }
    
    /**
     * Carrega os scripts e estilos necessários para a interface de administração no front-end.
     */
    private function enqueue_admin_interface_assets() {
        // Carrega as dependências da nova interface (Tailwind, Fontes, etc.)
        wp_enqueue_style( 'bcek-tailwind', 'https://cdn.tailwindcss.com', array(), null );
        wp_enqueue_style( 'bcek-google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;900&display=swap', array(), null );
        wp_enqueue_style( 'bcek-admin-style-frontend', BCEK_PLUGIN_URL . 'assets/css/bcek-admin-style.css', array(), BCEK_VERSION );
        
        wp_enqueue_script( 'interactjs', 'https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js', array(), null, true );
        
        // Carrega os novos scripts modulares
        $this->enqueue_modular_scripts();
        
        // Passa dados do PHP para o nosso JavaScript
        $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
        $template_data = $template_id ? BCEK_Database::get_template_by_id($template_id) : null;
        $fields_data = $template_id ? BCEK_Database::get_fields_for_template($template_id) : [];
        
        // --- PONTO DE DEBUG NO PHP (INICIALIZAÇÃO) ---
        bcek_log_to_file($template_data, 'DADOS DO TEMPLATE ENVIADOS PARA O JAVASCRIPT');
        bcek_log_to_file($fields_data, 'DADOS DOS CAMPOS ENVIADOS PARA O JAVASCRIPT');
        // --- FIM DO PONTO DE DEBUG ---

        // CORREÇÃO: Associamos os dados ao 'bcek-admin-state', o primeiro script a ser carregado.
        wp_localize_script('bcek-admin-state', 'bcek_admin_data', [
            'template' => $template_data,
            'fields' => $fields_data,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bcek_save_template_nonce'),
            'list_page_url' => remove_query_arg( array('action', 'template_id') ) // Adicione esta linha
        ]);
    }
    
    /**
     * Carrega os arquivos JavaScript modulares na ordem correta.
     */
    private function enqueue_modular_scripts() {
        $base_url = BCEK_PLUGIN_URL . 'assets/js/admin/';
        $deps = ['jquery', 'interactjs'];

        wp_enqueue_script('bcek-admin-state', $base_url . 'state.js', $deps, BCEK_VERSION, true);
        $deps[] = 'bcek-admin-state';

        wp_enqueue_script('bcek-admin-ui', $base_url . 'ui.js', $deps, BCEK_VERSION, true);
        $deps[] = 'bcek-admin-ui';

        wp_enqueue_script('bcek-admin-events', $base_url . 'events.js', $deps, BCEK_VERSION, true);
        $deps[] = 'bcek-admin-events';

        wp_enqueue_script('bcek-admin-interact', $base_url . 'interact.js', $deps, BCEK_VERSION, true);
        $deps[] = 'bcek-admin-interact';

        wp_enqueue_script('bcek-admin-main', $base_url . 'main.js', $deps, BCEK_VERSION, true);
    }

    /**
     * Função de callback para o shortcode [brand_center_editor].
     * (Este método permanece inalterado)
     */
    public function render_editor_shortcode( $atts ) {
        $atts = shortcode_atts( array('template_id' => 0,), $atts, 'brand_center_editor' );
        $template_id = intval( $atts['template_id'] );
        if ( ! $template_id ) { return '<p>' . __( 'Erro: ID do template não fornecido ou inválido no shortcode.', 'bcek' ) . '</p>'; }
        $template = BCEK_Database::get_template_by_id( $template_id );
        if ( ! $template ) { return '<p>' . sprintf( __( 'Erro: Template com ID %d não encontrado.', 'bcek' ), $template_id ) . '</p>'; }
        $fields = BCEK_Database::get_fields_for_template( $template_id );
        wp_enqueue_style( 'cropper-style', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css', array(), '1.5.12' );
        wp_enqueue_style( 'bcek-editor-style', BCEK_PLUGIN_URL . 'assets/css/bcek-editor-style.css', array( 'cropper-style' ), BCEK_VERSION );
        wp_enqueue_script( 'cropper-script', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js', array(), '1.5.12', true );
        wp_enqueue_script( 'bcek-editor-script', BCEK_PLUGIN_URL . 'assets/js/bcek-editor-script.js', array( 'jquery', 'cropper-script' ), BCEK_VERSION, true );
        $editor_data = array('template' => $template, 'fields'   => $fields, 'nonce'    => wp_create_nonce( 'bcek_generate_image_nonce' ));
        wp_localize_script( 'bcek-editor-script', 'bcek_data', $editor_data );
        wp_localize_script( 'bcek-editor-script', 'bcek_editor_ajax', array('ajax_url'  => admin_url( 'admin-ajax.php' ), 'fonts_url' => BCEK_PLUGIN_URL . 'assets/fonts/'));
        ob_start();
        include BCEK_PLUGIN_DIR . 'templates/editor-interface.php';
        return ob_get_clean();
    }
}