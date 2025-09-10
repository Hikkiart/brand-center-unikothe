<?php
/**
 * Classe para gerir o painel de administração do plugin.
 */

// Medida de segurança
if ( ! defined( 'WPINC' ) ) {
    die;
}

class BCEK_Admin {

    /**
     * Construtor da classe. Adiciona os hooks do painel de administração.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
    }

    /**
     * Adiciona as páginas de administração do plugin ao menu lateral do WordPress.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Brand Center Kothe', 'bcek' ),
            __( 'Brand Center Kothe', 'bcek' ),
            'manage_options',
            'brand-center-kothe-dashboard',
            array( $this, 'render_dashboard_page' ), // Aponta para o método da classe
            'dashicons-art',
            25
        );

        add_submenu_page(
            'brand-center-kothe-dashboard',
            __( 'Templates', 'bcek' ),
            __( 'Templates', 'bcek' ),
            'manage_options',
            'brand-center-kothe-templates',
            array( $this, 'render_templates_page' ) // Aponta para o método da classe
        );

        add_submenu_page(
            'brand-center-kothe-dashboard',
            __( 'Adicionar Novo Template', 'bcek' ),
            __( 'Adicionar Novo', 'bcek' ),
            'manage_options',
            'brand-center-kothe-add-new',
            array( $this, 'render_add_edit_template_page' ) // Aponta para o método da classe
        );
    }

    /**
     * Enfileira (carrega) scripts e estilos CSS para o painel de administração.
     * @param string $hook_suffix O sufixo da página de admin atual.
     */
    public function enqueue_scripts_styles( $hook_suffix ) {
        $is_bcek_page = false;
        if ( is_string( $hook_suffix ) && strpos( $hook_suffix, 'brand-center-kothe' ) !== false ) {
            $is_bcek_page = true;
        }
        if ( isset( $_GET['page'] ) && in_array( $_GET['page'], ['brand-center-kothe-dashboard', 'brand-center-kothe-templates', 'brand-center-kothe-add-new'] ) ) {
            $is_bcek_page = true;
        }

        if ( ! $is_bcek_page ) {
            return;
        }

        // Carrega os novos estilos e scripts para a interface de front-end
        wp_enqueue_style( 'bcek-tailwind', 'https://cdn.tailwindcss.com', array(), null );
        wp_enqueue_style( 'bcek-google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;900&display=swap', array(), null );
        wp_enqueue_style( 'bcek-admin-frontend-style', BCEK_PLUGIN_URL . 'assets/css/bcek-admin-style.css', array(), BCEK_VERSION );
        
        wp_enqueue_script( 'interactjs', 'https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js', array(), null, true );
        
        // Carrega os novos módulos JS
        wp_enqueue_script('bcek-admin-state', BCEK_PLUGIN_URL . 'assets/js/admin/state.js', array('jquery'), BCEK_VERSION, true);
        wp_enqueue_script('bcek-admin-ui', BCEK_PLUGIN_URL . 'assets/js/admin/ui.js', array('bcek-admin-state'), BCEK_VERSION, true);
        wp_enqueue_script('bcek-admin-events', BCEK_PLUGIN_URL . 'assets/js/admin/events.js', array('bcek-admin-ui'), BCEK_VERSION, true);
        wp_enqueue_script('bcek-admin-interact', BCEK_PLUGIN_URL . 'assets/js/admin/interact.js', array('bcek-admin-events', 'interactjs'), BCEK_VERSION, true);
        wp_enqueue_script('bcek-admin-main', BCEK_PLUGIN_URL . 'assets/js/admin/main.js', array('bcek-admin-interact'), BCEK_VERSION, true);
    }
    
    /**
     * Renderiza a página do Dashboard.
     * (Antiga função bcek_render_admin_dashboard_page)
     */
    public function render_dashboard_page() {
        require_once BCEK_PLUGIN_DIR . 'templates/admin/dashboard-page.php';
    }

    /**
     * Renderiza a página de listagem de Templates.
     * (Antiga função bcek_render_admin_templates_page)
     */
    public function render_templates_page() {
       require_once BCEK_PLUGIN_DIR . 'templates/admin/templates-list-page.php';
    }

    /**
     * Renderiza a página de Adicionar/Editar Template.
     * (Antiga função bcek_render_admin_add_edit_template_page)
     */
    public function render_add_edit_template_page() {
        require_once BCEK_PLUGIN_DIR . 'templates/admin/add-edit-template-page.php';
    }
}