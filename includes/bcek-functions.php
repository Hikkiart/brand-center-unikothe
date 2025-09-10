<?php
/**
 * Funções utilitárias gerais e callbacks de cron para o plugin Brand Center Editor Kothe.
 */

// Medida de segurança: impede o acesso direto ao ficheiro.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Função de callback para o evento cron 'bcek_delete_attachment_cron'.
 * Esta função é agendada para ser executada aproximadamente 30 minutos após a geração de uma imagem.
 * A sua tarefa é deletar o anexo (imagem) da biblioteca de multimédia do WordPress e o seu ficheiro físico do servidor.
 *
 * @param int $attachment_id O ID do anexo (imagem) a ser deletado.
 */
function bcek_do_delete_attachment( $attachment_id ) {
    // Verifica se o ID fornecido é realmente de um anexo válido antes de tentar deletar.
    if ( get_post_type( $attachment_id ) === 'attachment' ) {
        // Deleta o anexo. O segundo parâmetro 'true' força a exclusão do ficheiro físico do servidor.
        wp_delete_attachment( $attachment_id, true ); 
    }
}
// Adiciona a ação para o hook do WP Cron. Quando 'bcek_delete_attachment_cron' for disparado,
// a função 'bcek_do_delete_attachment' será chamada.
add_action( 'bcek_delete_attachment_cron', 'bcek_do_delete_attachment', 10, 1 );


/**
 * CORRIGIDO: Converte um ficheiro PNG existente para o formato BMP v3 de 24 bits standard.
 * Esta versão primeiro "achata" a imagem sobre um fundo branco para remover a transparência.
 *
 * @param string $source_png_path O caminho para o ficheiro PNG de origem.
 * @param string $destination_bmp_path O caminho completo para salvar o ficheiro BMP final.
 * @return bool True em sucesso, false em falha.
 */
if (!function_exists('bcek_convert_png_to_bmp')) {
    function bcek_convert_png_to_bmp( $source_png_path, $destination_bmp_path ) {
        if (!file_exists($source_png_path)) {
            error_log("BCEK BMP Error: Ficheiro PNG de origem não encontrado em $source_png_path");
            return false;
        }

        // Lê o ficheiro PNG para um recurso GD.
        $png_im = @imagecreatefrompng($source_png_path);
        if (!$png_im) {
            error_log("BCEK BMP Error: Falha ao ler o ficheiro PNG de origem ($source_png_path) para um recurso GD.");
            return false;
        }

        $width = imagesx($png_im);
        $height = imagesy($png_im);

        // Cria uma nova imagem truecolor com a mesma dimensão e fundo branco.
        $im = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $white);

        // Copia a imagem PNG (com a sua transparência) sobre o fundo branco, efetivamente "achatando-a".
        imagecopy($im, $png_im, 0, 0, 0, 0, $width, $height);
        imagedestroy($png_im); // Liberta a memória da imagem PNG original.

        $file = @fopen($destination_bmp_path, "wb");
        if (!$file) {
            error_log("BCEK BMP Error: Não foi possível abrir o ficheiro BMP para escrita em $destination_bmp_path");
            @imagedestroy($im);
            return false;
        }

        // Calcula o padding. Cada linha num BMP de 24 bits deve ter um tamanho em bytes que seja múltiplo de 4.
        $row_size_unpadded = $width * 3; // 3 bytes por píxel (R, G, B)
        $padding = (4 - ($row_size_unpadded % 4)) % 4;
        
        $pixel_data_size = ($row_size_unpadded + $padding) * $height;
        $file_size = 54 + $pixel_data_size; // 14 (File Header) + 40 (Info Header)

        // Cabeçalho do Ficheiro (14 bytes)
        fwrite($file, 'BM');
        fwrite($file, pack('V', $file_size));
        fwrite($file, pack('v', 0));
        fwrite($file, pack('v', 0));
        fwrite($file, pack('V', 54));

        // Cabeçalho de Informação (40 bytes) - BITMAPINFOHEADER (v3)
        fwrite($file, pack('V', 40));
        fwrite($file, pack('V', $width));
        fwrite($file, pack('V', $height));
        fwrite($file, pack('v', 1));
        fwrite($file, pack('v', 24)); // <-- Profundidade de bits definida para 24
        fwrite($file, pack('V', 0)); // BI_RGB (sem compressão)
        fwrite($file, pack('V', $pixel_data_size));
        fwrite($file, pack('V', 2835)); // Resolução horizontal em píxeis/metro (~72 DPI)
        fwrite($file, pack('V', 2835)); // Resolução vertical em píxeis/metro (~72 DPI)
        fwrite($file, pack('V', 0));
        fwrite($file, pack('V', 0));

        // Dados dos Píxeis (de baixo para cima)
        for ($y = $height - 1; $y >= 0; $y--) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($im, $x, $y);
                $b = ($rgb >> 0) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $r = ($rgb >> 16) & 0xFF;
                // Formato de 24 bits: escreve apenas os 3 bytes de cor na ordem BGR.
                fwrite($file, pack('C3', $b, $g, $r));
            }
            // Adiciona o padding no final de cada linha.
            if ($padding > 0) {
                fwrite($file, str_repeat("\0", $padding));
            }
        }
        
        fclose($file);
        @imagedestroy($im);

        return true;
    }
}