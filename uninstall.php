<?php
/**
 * Arquivo de desinstalação para Brand Center Editor Kothe.
 * Este arquivo é chamado quando o usuário deleta o plugin da interface do WordPress.
 */

// Se não estiver desinstalando, sair.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Nomes das tabelas
$table_templates = $wpdb->prefix . 'bcek_templates';
$table_fields = $wpdb->prefix . 'bcek_fields';

// Deletar tabelas personalizadas
$wpdb->query( "DROP TABLE IF EXISTS {$table_fields}" );
$wpdb->query( "DROP TABLE IF EXISTS {$table_templates}" );

// Deletar options (se houver alguma global)
// delete_option( 'bcek_db_version' );
// delete_option( 'bcek_settings' ); // Exemplo

// Nota: Imagens salvas na biblioteca de mídia NÃO são removidas por padrão,
// pois podem estar sendo usadas em outros lugares. Isso é um comportamento comum.
// Se fosse necessário remover, seria uma lógica mais complexa para identificar
// e deletar apenas os anexos criados pelo plugin.
?>