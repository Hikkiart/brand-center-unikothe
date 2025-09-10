<?php
/**
 * Plugin Name:       Brand Center Editor Kothe
 * Plugin URI:        #
 * Description:       Editor de peças visuais para a intranet Kothe.
 * Version:           1.5.0
 * Author:            Attila Martins
 * Author URI:        #
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bcek
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'BCEK_VERSION', '2.5.9' );
define( 'BCEK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BCEK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BCEK_PLUGIN_FILE', __FILE__ );

final class Brand_Center_Editor_Kothe {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->instantiate_classes();
        $this->add_hooks();
    }

    private function load_dependencies() {
        // --- A CORREÇÃO ESTÁ AQUI ---
        // Garante que o ficheiro com a função de ativação seja carregado.
        require_once BCEK_PLUGIN_DIR . 'includes/bcek-activation.php';
        
        require_once BCEK_PLUGIN_DIR . 'includes/bcek-debug.php';
        require_once BCEK_PLUGIN_DIR . 'includes/bcek-functions.php';
        require_once BCEK_PLUGIN_DIR . 'includes/bcek-admin-page.php';
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-database.php';
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-shortcodes.php';
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-ajax.php';
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-admin.php';
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-image-generator.php';
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-admin-ajax.php';
    }
    
    private function instantiate_classes() {
        new BCEK_Shortcodes();
        new BCEK_Ajax();
        new BCEK_Admin();
        new BCEK_Admin_Ajax();
    }

    private function add_hooks() {
        register_activation_hook( BCEK_PLUGIN_FILE, 'bcek_activate_plugin' );
        
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
    }
    
    public function add_admin_menu() {
        add_menu_page('Brand Center Kothe', 'Brand Center Kothe', 'manage_options', 'brand-center-kothe-dashboard', 'bcek_render_admin_dashboard_page', 'dashicons-art', 25);
        add_submenu_page('brand-center-kothe-dashboard', 'Templates', 'Templates', 'manage_options', 'brand-center-kothe-templates', 'bcek_render_admin_templates_page');
        add_submenu_page('brand-center-kothe-dashboard', 'Adicionar Novo Template', 'Adicionar Novo', 'manage_options', 'brand-center-kothe-add-new', 'bcek_render_admin_add_edit_template_page');
    }

    /**
     * Carrega scripts apenas no back-end (wp-admin).
     */
    public function enqueue_backend_scripts( $hook_suffix ) {
        if ( strpos( $hook_suffix, 'brand-center-kothe' ) !== false ) {
            wp_enqueue_style( 'bcek-admin-style', BCEK_PLUGIN_URL . 'assets/css/bcek-admin-style.css', array(), BCEK_VERSION );
        }
    }

    /**
     * Carrega scripts no front-end do site.
     */
    public function enqueue_frontend_scripts() {
    global $post;

    // Garante que o $post é válido
    if ( ! is_a( $post, 'WP_Post' ) ) {
        return;
    }

    // --- LÓGICA PARA O EDITOR DO UTILIZADOR (quando ?template_id=... está na URL) ---
    // Verifica se um template específico está a ser editado pelo utilizador
    if ( isset( $_GET['template_id'] ) && is_numeric( $_GET['template_id'] ) && ! isset( $_GET['action'] ) ) {
        $template_id = intval( $_GET['template_id'] );

        // Vai buscar os dados do template e dos campos
        $template = BCEK_Database::get_template_by_id( $template_id );
        $fields = BCEK_Database::get_fields_for_template( $template_id );

        // Se o template existir, carrega os scripts e os dados
        if ( $template ) {
            // Estilos
            wp_enqueue_style( 'cropper-style', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css', array(), '1.5.12' );
            wp_enqueue_style( 'bcek-user-editor-style', BCEK_PLUGIN_URL . 'assets/css/bcek-editor-style.css', array( 'cropper-style' ), BCEK_VERSION );
            
            // Bibliotecas
            wp_enqueue_script( 'cropper-script', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js', array(), '1.5.12', true );
            
            // Scripts modulares do utilizador
            $js_dir = BCEK_PLUGIN_URL . 'assets/js/user/';
            $deps = ['jquery', 'cropper-script'];

            wp_enqueue_script('bcek-user-state', $js_dir . 'state.js', $deps, BCEK_VERSION, true);
            $deps[] = 'bcek-user-state';

            wp_enqueue_script('bcek-user-ui', $js_dir . 'ui.js', $deps, BCEK_VERSION, true);
            $deps[] = 'bcek-user-ui';
            
            wp_enqueue_script('bcek-user-events', $js_dir . 'events.js', $deps, BCEK_VERSION, true);
            $deps[] = 'bcek-user-events';

            wp_enqueue_script('bcek-user-main', $js_dir . 'main.js', $deps, BCEK_VERSION, true);
            
            // Passa os dados do PHP para o JavaScript (A PARTE CRÍTICA)
            $editor_data = [
                'template' => $template, 
                'fields'   => $fields, 
                'nonce'    => wp_create_nonce( 'bcek_generate_image_nonce' ),
                'ajax_url' => admin_url('admin-ajax.php'),
                'fonts_url' => BCEK_PLUGIN_URL . 'assets/fonts/'
            ];
            
            wp_localize_script( 'bcek-user-main', 'bcek_data', $editor_data );
        }
    }

    // --- LÓGICA PARA A LISTA DE TEMPLATES DO UTILIZADOR ---
    // Se a página contém o shortcode mas não há um template_id na URL
    if ( has_shortcode( $post->post_content, 'brand_center_editor' ) && ! isset( $_GET['template_id'] ) ) {
        wp_enqueue_style( 'bcek-user-list-style', BCEK_PLUGIN_URL . 'assets/css/bcek-user-style.css', array(), BCEK_VERSION );
    }

    // --- LÓGICA PARA O ADMIN NO FRONT-END (já existente, não mexer) ---
    if ( has_shortcode( $post->post_content, 'bcek_admin_interface' ) ) {
        // Deixa a própria classe de shortcodes tratar disto, pois já está a funcionar
    }
}
}


Brand_Center_Editor_Kothe::get_instance();
