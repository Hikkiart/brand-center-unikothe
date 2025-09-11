<?php
/**
 * Classe para gerir a criação e renderização de shortcodes do plugin.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class BCEK_Shortcodes {

    public function __construct() {
        add_action( 'init', array( $this, 'register_shortcodes' ) );
    }

    public function register_shortcodes() {
        add_shortcode( 'brand_center_editor', array( $this, 'render_editor_shortcode' ) );
        add_shortcode( 'bcek_admin_interface', array( $this, 'render_admin_interface_shortcode' ) );
    }

    /**
     * Renderiza a interface do utilizador final e CARREGA os scripts necessários.
     */
    /**
     * Renderiza a interface do utilizador final e CARREGA os scripts necessários.
     */
    public function render_editor_shortcode( $atts ) {
        $template_id = isset( $_GET['template_id'] ) ? intval( $_GET['template_id'] ) : 0;

        if ( $template_id > 0 ) {
            // --- INÍCIO DO CARREGAMENTO DE SCRIPTS E ESTILOS ---
            wp_enqueue_style('bcek-user-style');
            wp_enqueue_script('bcek-user-main'); // O main puxa as suas dependências

            // Lógica para carregar dinamicamente as Google Fonts
            $fields = BCEK_Database::get_fields_for_template($template_id);
            $font_families_to_load = [];
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    if ($field->field_type === 'text' && !empty($field->font_family)) {
                        $font_name = explode('-', $field->font_family)[0];
                        if (!in_array($font_name, $font_families_to_load)) {
                            $font_families_to_load[] = $font_name;
                        }
                    }
                }
            }
            
            if (!empty($font_families_to_load)) {
                $font_url = 'https://fonts.googleapis.com/css2?';
                foreach ($font_families_to_load as $family) {
                    $font_url .= 'family=' . urlencode($family) . ':ital,wght@0,400;0,700;0,900;1,400;1,700;0,900&';
                }
                $font_url .= 'display=swap';
                wp_enqueue_style( 'bcek-dynamic-google-fonts', $font_url, array(), null );
            }
            // --- FIM DO CARREGAMENTO DE SCRIPTS E ESTILOS ---

            $template = BCEK_Database::get_template_by_id( $template_id );
            if ( ! $template ) {
                return '<p>' . sprintf( __( 'Erro: Template com ID %d não encontrado.', 'bcek' ), $template_id ) . '</p>';
            }
            
            // Passa os dados para o JavaScript, incluindo as fontes a serem aguardadas
            $editor_data = array(
                'template' => $template, 
                'fields'   => $fields,
                'font_families' => $font_families_to_load, // INFORMAÇÃO EXTRA
                'nonce'    => wp_create_nonce( 'bcek_generate_image_nonce' ), 
                'ajax_url' => admin_url( 'admin-ajax.php' ), 
                'fonts_url' => BCEK_PLUGIN_URL . 'assets/fonts/'
            );
            wp_localize_script( 'bcek-user-state', 'bcek_data', $editor_data );

            ob_start();
            include BCEK_PLUGIN_DIR . 'templates/user/editor.php';
            return ob_get_clean();

        } else {
            // Carrega os assets da LISTA do utilizador
            wp_enqueue_style('bcek-user-style');
            
            $templates = BCEK_Database::get_all_templates();
            
            ob_start();
            include BCEK_PLUGIN_DIR . 'templates/user/list-templates.php';
            return ob_get_clean();
        }
    }

    /**
     * Renderiza a interface de administração e CARREGA os scripts necessários.
     */
    public function render_admin_interface_shortcode() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return '<p>' . __( 'Acesso restrito.', 'bcek' ) . '</p>';
        }
        
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        
        wp_enqueue_style('bcek-admin-style-frontend');

        ob_start();
        if ( $action === 'edit' || $action === 'add_new' ) {
            // Carrega assets do EDITOR de administração
            wp_enqueue_script('bcek-admin-main');

            $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
            $template_data = $template_id ? BCEK_Database::get_template_by_id($template_id) : null;
            $fields_data = $template_id ? BCEK_Database::get_fields_for_template($template_id) : [];
            
            wp_localize_script('bcek-admin-state', 'bcek_admin_data', [
                'template' => $template_data,
                'fields' => $fields_data,
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bcek_save_template_nonce'),
                'list_page_url' => remove_query_arg( array('action', 'template_id') )
            ]);

            include BCEK_PLUGIN_DIR . 'templates/admin/frontend-editor-page.php';
        } else {
            // Carrega assets da LISTA de administração
            wp_enqueue_script('bcek-admin-list-script');
            wp_localize_script('bcek-admin-list-script', 'bcek_list_params', array( 'ajax_url' => admin_url('admin-ajax.php') ));

            include BCEK_PLUGIN_DIR . 'templates/admin/templates-list-page.php';
        }
        return ob_get_clean();
    }
}