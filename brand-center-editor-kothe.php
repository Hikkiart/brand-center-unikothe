<?php
/**
 * Plugin Name:       Brand Center Editor Kothe
 * Plugin URI:        # Insira o link para a página do plugin, se houver
 * Description:       Editor de peças visuais para a intranet Kothe, permitindo a criação de templates e personalização de textos para geração de imagens PNG.
 * Version:           1.0.4 // Versão atualizada para refletir a implementação do cropper
 * Author:            Attila Martins
 * Author URI:        # Insira o link do autor, se houver
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bcek
 * Domain Path:       /languages
 */

// Medida de segurança: Se este arquivo for chamado diretamente, aborte a execução.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define constantes globais do plugin para fácil acesso e manutenção
define( 'BCEK_VERSION', '1.0.4' ); 
define( 'BCEK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); 
define( 'BCEK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );   
define( 'BCEK_PLUGIN_FILE', __FILE__ );                   

// Inclui os arquivos PHP modulares que contêm as funcionalidades do plugin
require_once BCEK_PLUGIN_DIR . 'includes/bcek-activation.php';       
require_once BCEK_PLUGIN_DIR . 'includes/bcek-database.php';        
require_once BCEK_PLUGIN_DIR . 'includes/bcek-admin-page.php';      
require_once BCEK_PLUGIN_DIR . 'includes/bcek-ajax-handlers.php';   
require_once BCEK_PLUGIN_DIR . 'includes/bcek-functions.php';       
require_once BCEK_PLUGIN_DIR . 'includes/bcek-image-generator.php'; 
require_once BCEK_PLUGIN_DIR . 'includes/bcek-shortcodes.php';      

// Registra a função a ser executada na ativação do plugin
register_activation_hook( BCEK_PLUGIN_FILE, 'bcek_activate_plugin' );
// O WordPress procura por uninstall.php automaticamente na pasta do plugin ao desinstalar.

/**
 * Enfileira (carrega) scripts e estilos CSS para o painel de administração do plugin.
 *
 * @param string $hook_suffix O sufixo da página de admin atual, usado para carregar assets condicionalmente.
 */
function bcek_admin_enqueue_scripts_styles( $hook_suffix ) {
    $is_bcek_page = false;
    // Verifica se a página atual do admin pertence a este plugin usando o hook_suffix
    if (is_string($hook_suffix) && strpos($hook_suffix, 'brand-center-kothe') !== false) {
        $is_bcek_page = true;
    }
    // Permite também para as slugs específicas das páginas do plugin (mais robusto)
    if (isset($_GET['page']) && 
        in_array($_GET['page'], ['brand-center-kothe-dashboard', 'brand-center-kothe-templates', 'brand-center-kothe-add-new'])) {
        $is_bcek_page = true;
    }

    if ( !$is_bcek_page ) {
        return; // Não carrega os assets se não for uma página do plugin
    }

    // Enfileira o arquivo CSS principal para o admin do plugin
    wp_enqueue_style( 
        'bcek-admin-style', 
        BCEK_PLUGIN_URL . 'assets/css/bcek-admin-style.css', 
        array(), // Dependências (nenhuma neste caso)
        BCEK_VERSION // Versão do arquivo (para controle de cache)
    );

    // Enfileira o estilo padrão do WordPress para o seletor de cores
    wp_enqueue_style( 'wp-color-picker' ); 
    
    // Enfileira o arquivo JavaScript principal para o admin do plugin
    wp_enqueue_script( 
        'bcek-admin-script', 
        BCEK_PLUGIN_URL . 'assets/js/bcek-admin-script.js', 
        array( 'jquery', 'wp-color-picker' ), // Dependências: jQuery e o script do seletor de cores
        BCEK_VERSION, 
        true // Carrega o script no rodapé da página
    );
    
    // Habilita o uploader de mídia do WordPress (usado para selecionar a imagem base do template)
    wp_enqueue_media(); 
}
add_action( 'admin_enqueue_scripts', 'bcek_admin_enqueue_scripts_styles' ); // Hook para enfileirar scripts no admin


// ***** FUNÇÃO REMOVIDA *****
// A função bcek_frontend_enqueue_scripts_styles foi removida daqui.
// Toda a lógica de enfileiramento para o frontend foi movida para dentro do
// shortcode handler em 'includes/bcek-shortcodes.php' para garantir que os scripts
// e as suas dependências (como o Cropper.js) sejam carregados apenas quando necessário e na ordem correta.


/**
 * Adiciona as páginas de administração do plugin ao menu lateral do WordPress.
 */
function bcek_add_admin_menu() {
    // Adiciona o item de menu principal
    add_menu_page(
        __( 'Brand Center Kothe', 'bcek' ), // Título da página
        __( 'Brand Center Kothe', 'bcek' ), // Título do menu
        'manage_options',                   // Capacidade necessária para ver este menu
        'brand-center-kothe-dashboard',     // Slug do menu
        'bcek_render_admin_dashboard_page', // Função que renderiza o conteúdo da página
        'dashicons-art',                    // Ícone do menu
        25                                  // Posição no menu
    );

    // Adiciona o submenu "Templates"
    add_submenu_page(
        'brand-center-kothe-dashboard',     // Slug da página pai
        __( 'Templates', 'bcek' ),          // Título da página
        __( 'Templates', 'bcek' ),          // Título do submenu
        'manage_options',                   // Capacidade
        'brand-center-kothe-templates',     // Slug deste submenu
        'bcek_render_admin_templates_page'  // Função de callback
    );

    // Adiciona o submenu "Adicionar Novo" (para templates)
     add_submenu_page(
        'brand-center-kothe-dashboard',     // Slug da página pai
        __( 'Adicionar Novo Template', 'bcek' ), // Título da página
        __( 'Adicionar Novo', 'bcek' ),     // Título do submenu
        'manage_options',                   // Capacidade
        'brand-center-kothe-add-new',       // Slug deste submenu
        'bcek_render_admin_add_edit_template_page' // Função de callback
    );
}
add_action( 'admin_menu', 'bcek_add_admin_menu' ); // Hook para adicionar itens ao menu de administração
?>