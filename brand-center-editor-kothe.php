<?php
/**
 * Plugin Name:       Brand Center Editor Kothe
 * Plugin URI:        #
 * Description:       Editor de peças visuais para a intranet Kothe.
 * Version:           2.3.3-dev
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

define( 'BCEK_VERSION', '2.3.3-dev' );
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
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-database.php';
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-shortcodes.php';
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-ajax.php';
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-image-generator.php';
        require_once BCEK_PLUGIN_DIR . 'includes/class-bcek-admin-ajax.php';
    }
    
    private function instantiate_classes() {
        new BCEK_Shortcodes();
        new BCEK_Ajax();
        new BCEK_Admin_Ajax();
    }

    private function add_hooks() {
        register_activation_hook( BCEK_PLUGIN_FILE, 'bcek_activate_plugin' );
        

        // NOVO HOOK: agora chama a função para REGISTAR todos os scripts do front-end
        add_action( 'wp_enqueue_scripts', array( $this, 'register_all_scripts' ) );
        add_action( 'after_setup_theme', array( $this, 'add_custom_image_size' ) );
    }
    

    public function register_all_scripts() {
        // --- ASSETS GERAIS ---
        wp_register_style( 'bcek-local-fonts', BCEK_PLUGIN_URL . 'assets/css/bcek-fonts.css', array(), BCEK_VERSION );

        // --- ASSETS DO EDITOR DO UTILIZADOR ---
        wp_register_style( 'bcek-user-style', BCEK_PLUGIN_URL . 'assets/css/user/style.css', array('bcek-local-fonts', 'cropper-style'), BCEK_VERSION );
        wp_register_style( 'cropper-style', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css', array(), '1.5.12' );
        wp_register_script( 'cropper-script', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js', array(), '1.5.12', true );

        $js_user_url = BCEK_PLUGIN_URL . 'assets/js/user/';
        $user_deps = ['jquery', 'cropper-script'];
        wp_register_script('bcek-user-state', $js_user_url . 'state.js', $user_deps, BCEK_VERSION, true);
        wp_register_script('bcek-user-ui', $js_user_url . 'ui.js', ['bcek-user-state'], BCEK_VERSION, true);
        wp_register_script('bcek-user-ajax', $js_user_url . 'ajax.js', ['bcek-user-state'], BCEK_VERSION, true);
        wp_register_script('bcek-user-events', $js_user_url . 'events.js', ['bcek-user-ui', 'bcek-user-ajax'], BCEK_VERSION, true);
        wp_register_script('bcek-user-main', $js_user_url . 'main.js', ['bcek-user-events'], BCEK_VERSION, true);
        wp_register_script('bcek-user-filter-script', $js_user_url . 'bcek-user-filter.js', [], BCEK_VERSION, true);

        // --- ASSETS DO EDITOR DE ADMINISTRAÇÃO (FRONT-END) ---
        wp_register_style( 'bcek-admin-style-frontend', BCEK_PLUGIN_URL . 'assets/css/bcek-admin-style.css', array('bcek-local-fonts'), BCEK_VERSION );
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
    
    public function add_custom_image_size() {
        // Adiciona um tamanho de imagem chamado 'bcek_template_thumb', 400x400 pixels, com corte rígido.
        add_image_size( 'bcek_template_thumb', 400, 400, true );
    }
}

Brand_Center_Editor_Kothe::get_instance();