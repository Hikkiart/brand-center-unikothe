<?php
/**
 * Funções de depuração para o plugin Brand Center Editor Kothe.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! function_exists( 'bcek_log_to_file' ) ) {
    /**
     * Escreve uma mensagem de depuração para um ficheiro de log dedicado dentro da pasta do plugin.
     *
     * @param mixed $message A mensagem ou dados a serem guardados no log. Pode ser uma string, array ou objeto.
     * @param string $title Um título opcional para a entrada do log.
     */
    function bcek_log_to_file( $message, $title = '' ) {
        // Define o caminho para o nosso ficheiro de log
        $log_file_path = BCEK_PLUGIN_DIR . 'debug.log';
        
        // Converte arrays ou objetos numa string legível
        if ( is_array( $message ) || is_object( $message ) ) {
            $message = print_r( $message, true );
        }

        // Formata a entrada do log com data, hora e título
        $log_entry = "[" . date("Y-m-d H:i:s") . "]";
        if ( ! empty( $title ) ) {
            $log_entry .= " --- " . $title . " ---";
        }
        $log_entry .= "\n" . $message . "\n\n";

        // Escreve a entrada no ficheiro
        // FILE_APPEND garante que as novas mensagens sejam adicionadas ao final do ficheiro
        // LOCK_EX previne que outras pessoas escrevam no ficheiro ao mesmo tempo
        @file_put_contents( $log_file_path, $log_entry, FILE_APPEND | LOCK_EX );
    }
}
?>