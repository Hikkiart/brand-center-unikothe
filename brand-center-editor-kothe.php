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
        if ( ! is_a( $post, 'WP_Post' ) ) return;

        // --- CARREGA SCRIPTS PARA O EDITOR DE TEMPLATES ---
        if ( isset($_GET['action']) && in_array($_GET['action'], ['edit', 'add_new']) ) {
            // ... (toda a sua lógica para o editor, que está a funcionar, permanece igual)
            wp_enqueue_media();
            wp_enqueue_style( 'bcek-editor-style', BCEK_PLUGIN_URL . 'assets/css/bcek-editor-style.css', array(), BCEK_VERSION );
            wp_enqueue_script( 'interactjs', 'https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js', array(), '1.10.17', true );
            $js_dir = BCEK_PLUGIN_URL . 'assets/js/admin/';
            $js_deps = array( 'jquery', 'interactjs' ); 
            wp_enqueue_script( 'bcek-admin-state', $js_dir . 'state.js', $js_deps, BCEK_VERSION, true );
            wp_enqueue_script( 'bcek-admin-ui', $js_dir . 'ui.js', array('bcek-admin-state'), BCEK_VERSION, true );
            wp_enqueue_script( 'bcek-admin-interact', $js_dir . 'interact.js', array('bcek-admin-state', 'bcek-admin-ui'), BCEK_VERSION, true );
            wp_enqueue_script( 'bcek-admin-events', $js_dir . 'events.js', array('bcek-admin-state', 'bcek-admin-ui'), BCEK_VERSION, true );
            wp_enqueue_script( 'bcek-admin-main', $js_dir . 'main.js', array('bcek-admin-events', 'bcek-admin-interact'), BCEK_VERSION, true);
            // ... (resto da sua lógica de wp_localize_script para o editor)
            $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
            $template_data = $template_id > 0 ? BCEK_Database::get_template_by_id($template_id) : null;
            $fields_data = $template_id > 0 ? BCEK_Database::get_fields_for_template($template_id) : array();
            wp_localize_script('bcek-admin-main', 'bcek_admin_data', array(
                'template' => $template_data,
                'fields'   => $fields_data,
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('bcek_save_template_nonce')
            ));
        }

        // --- DEBUG: VAMOS CARREGAR O SCRIPT DE EXCLUSÃO INCONDICIONALMENTE ---
        error_log("BCEK DEBUG: A função enqueue_frontend_scripts foi executada. A tentar carregar bcek-admin-list.js...");

        wp_enqueue_script(
            'bcek-admin-list-script',
            BCEK_PLUGIN_URL . 'assets/js/admin/bcek-admin-list.js',
            array('jquery'),
            BCEK_VERSION,
            true
        );
        
        // Fornece a URL do AJAX ao nosso script, essencial para o front-end
        wp_localize_script(
            'bcek-admin-list-script',
            'bcek_list_params',
            array( 'ajax_url' => admin_url('admin-ajax.php') )
        );
    }
}

Brand_Center_Editor_Kothe::get_instance();