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

define( 'BCEK_VERSION', '2.8.6' );
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
        require_once BCEK_PLUGIN_DIR . 'includes/bcek-debug.php';
        require_once BCEK_PLUGIN_DIR . 'includes/bcek-activation.php';
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

        // NOVO HOOK: agora chama a função para REGISTAR todos os scripts do front-end
        add_action( 'wp_enqueue_scripts', array( $this, 'register_all_scripts' ) );
    }
    
    public function add_admin_menu() {
        add_menu_page('Brand Center Kothe', 'Brand Center Kothe', 'manage_options', 'brand-center-kothe-dashboard', 'bcek_render_admin_dashboard_page', 'dashicons-art', 25);
        add_submenu_page('brand-center-kothe-dashboard', 'Templates', 'Templates', 'manage_options', 'brand-center-kothe-templates', 'bcek_render_admin_templates_page');
        add_submenu_page('brand-center-kothe-dashboard', 'Adicionar Novo Template', 'Adicionar Novo', 'manage_options', 'brand-center-kothe-add-edit-template_page');
    }

    /**
     * Carrega scripts apenas no back-end (wp-admin).
     */
    public function enqueue_backend_scripts( $hook_suffix ) {
        if ( strpos( $hook_suffix, 'brand-center-kothe' ) !== false ) {
            wp_enqueue_style( 'bcek-admin-style', BCEK_PLUGIN_URL . 'assets/css/bcek-admin-style.css', array(), BCEK_VERSION );
        }
    }

    public function register_all_scripts() {
        // --- ASSETS GERAIS ---
        wp_register_style( 'bcek-tailwind', 'https://cdn.tailwindcss.com', array(), null );
        wp_register_style( 'bcek-google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;900&display=swap', array(), null );
        wp_register_script( 'jquery-ui-sortable', '', array('jquery'), false, true );

        // --- ASSETS DO EDITOR DO UTILIZADOR ---
        wp_register_style( 'cropper-style', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css', array(), '1.5.12' );
        wp_register_style( 'bcek-user-style', BCEK_PLUGIN_URL . 'assets/css/user/style.css', array('bcek-tailwind', 'bcek-google-fonts', 'cropper-style'), BCEK_VERSION );
        wp_register_script( 'cropper-script', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js', array(), '1.5.12', true );

        $js_user_url = BCEK_PLUGIN_URL . 'assets/js/user/';
        $user_deps = ['jquery', 'cropper-script'];
        wp_register_script('bcek-user-state', $js_user_url . 'state.js', $user_deps, BCEK_VERSION, true);
        wp_register_script('bcek-user-ui', $js_user_url . 'ui.js', ['bcek-user-state'], BCEK_VERSION, true);
        wp_register_script('bcek-user-ajax', $js_user_url . 'ajax.js', ['bcek-user-state'], BCEK_VERSION, true);
        wp_register_script('bcek-user-events', $js_user_url . 'events.js', ['bcek-user-ui', 'bcek-user-ajax'], BCEK_VERSION, true);
        wp_register_script('bcek-user-main', $js_user_url . 'main.js', ['bcek-user-events'], BCEK_VERSION, true);

        // --- ASSETS DO EDITOR DE ADMINISTRAÇÃO (FRONT-END) ---
        wp_register_style( 'bcek-admin-style-frontend', BCEK_PLUGIN_URL . 'assets/css/bcek-admin-style.css', array('bcek-tailwind', 'bcek-google-fonts'), BCEK_VERSION );
        wp_register_script( 'interactjs', 'https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js', array(), null, true );
        wp_register_script('bcek-admin-list-script', BCEK_PLUGIN_URL . 'assets/js/admin/bcek-admin-list.js', array('jquery'), BCEK_VERSION, true);

        $js_admin_url = BCEK_PLUGIN_URL . 'assets/js/admin/';
        $admin_deps = ['jquery', 'interactjs'];
        wp_register_script('bcek-admin-state', $js_admin_url . 'state.js', $admin_deps, BCEK_VERSION, true);
        wp_register_script('bcek-admin-ui', $js_admin_url . 'ui.js', ['bcek-admin-state'], BCEK_VERSION, true);
        wp_register_script('bcek-admin-events', $js_admin_url . 'events.js', ['bcek-admin-ui'], BCEK_VERSION, true);
        wp_register_script('bcek-admin-interact', $js_admin_url . 'interact.js', ['bcek-admin-events', 'interactjs'], BCEK_VERSION, true);
        wp_register_script('bcek-admin-main', $js_admin_url . 'main.js', ['bcek-admin-interact'], BCEK_VERSION, true);
    }
}

Brand_Center_Editor_Kothe::get_instance();