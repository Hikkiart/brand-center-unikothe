<?php
/**
 * Classe responsável por toda a lógica de geração de imagem usando a biblioteca GD.
 */
if ( ! defined( 'WPINC' ) ) { die; }

class BCEK_Image_Generator {

    /**
     * VERSÃO SIMPLIFICADA: Gera a imagem final compondo a imagem de base com a sobreposição vinda do front-end.
     */
    public static function generate( $template, $fields, $user_inputs, $user_filename = '', $format = 'png' ) {
        if ( ! extension_loaded( 'gd' ) ) {
            return new WP_Error( 'gd_not_loaded', 'A biblioteca GD não está habilitada no servidor.' );
        }
        
        // 1. Carregar a Imagem Base
        $base_image_path = get_attached_file( $template->base_image_id );
        if ( !$base_image_path || !file_exists( $base_image_path ) ) {
            return new WP_Error( 'base_image_file_not_found', 'Arquivo da imagem base não encontrado.' );
        }
        $image_info = @getimagesize( $base_image_path );
        if (!$image_info) return new WP_Error('image_load_info_error', 'Não foi possível ler informações da imagem base.');

        $final_image_width = $image_info[0];
        $final_image_height = $image_info[1];
        
        $final_image = self::create_image_resource_from_path($base_image_path, $image_info['mime']);
        if (is_wp_error($final_image)) return $final_image;

        // 2. Sobrepor a imagem do canvas (que já contém texto e imagens do utilizador)
        if ( !empty($_POST['text_overlay_data_url']) ) {
            $overlay_resource = self::create_image_resource_from_data_url($_POST['text_overlay_data_url']);
            
            if ($overlay_resource) {
                // Redimensiona o overlay para corresponder exatamente às dimensões da imagem base
                imagecopyresampled(
                    $final_image,           // Imagem de destino
                    $overlay_resource,      // Imagem de origem (o canvas do utilizador)
                    0, 0,                   // Coordenadas de destino (x, y)
                    0, 0,                   // Coordenadas de origem (x, y)
                    $final_image_width,     // Largura de destino
                    $final_image_height,    // Altura de destino
                    imagesx($overlay_resource), // Largura de origem
                    imagesy($overlay_resource)  // Altura de origem
                );
                imagedestroy($overlay_resource);
            }
        }
        
        // 3. Salvar o ficheiro
        return self::save_final_image($final_image, $user_filename, $format);
    }
    
    // --- FUNÇÕES AUXILIARES (permanecem quase iguais) ---

    private static function create_image_resource_from_path($path, $mime) {
        $resource = null;
        switch ($mime) {
            case 'image/png': 
                $resource = @imagecreatefrompng($path); 
                break;
            case 'image/jpeg': 
                $resource = @imagecreatefromjpeg($path); 
                break;
            case 'image/gif': 
                $resource = @imagecreatefromgif($path); 
                break;
            // --- ADICIONE ESTE BLOCO DE CÓDIGO ---
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $resource = @imagecreatefromwebp($path);
                } else {
                    return new WP_Error('webp_not_supported', 'O formato de imagem WebP não é suportado pelo seu servidor. Por favor, use PNG ou JPG para a imagem base.');
                }
                break;
            // --- FIM DO BLOCO ADICIONADO ---
            default: 
                return new WP_Error('unsupported_image_type', 'Tipo de imagem base não suportado: ' . esc_html($mime));
        }
        
        if (!$resource) return new WP_Error('gd_create_from_file_failed', 'Falha ao criar imagem a partir do arquivo.');
        
        // Garante que a imagem está em true color para melhor compatibilidade
        if (!imageistruecolor($resource)) {
            $trueColorImage = imagecreatetruecolor(imagesx($resource), imagesy($resource));
            imagecopy($trueColorImage, $resource, 0, 0, 0, 0, imagesx($resource), imagesy($resource));
            imagedestroy($resource);
            $resource = $trueColorImage;
        }
        return $resource;
    }

    private static function create_image_resource_from_data_url($data_url) {
        if (strpos($data_url, 'base64,') === false) return false;
        $base64_data = substr($data_url, strpos($data_url, ',') + 1);
        $decoded_image_data = base64_decode($base64_data);
        if ($decoded_image_data === false) return false;
        return @imagecreatefromstring($decoded_image_data);
    }
    
    private static function save_final_image($image_resource, $user_filename, $format) {
        $upload_dir = wp_upload_dir();
        $base_filename = !empty($user_filename) ? sanitize_file_name($user_filename) : 'bcek-image';
        $filename_hash = substr(md5(uniqid(rand(), true)), 0, 6);
        
        $extension = ($format === 'bmp') ? 'bmp' : 'png';
        $mime_type = ($format === 'bmp') ? 'image/bmp' : 'png';
        
        $final_filename = "{$base_filename}_{$filename_hash}.{$extension}";
        $filepath = "{$upload_dir['path']}/{$final_filename}";
        $fileurl = "{$upload_dir['url']}/{$final_filename}";

        $save_success = false;
        if ($format === 'bmp' && function_exists('bcek_convert_png_to_bmp')) {
            $temp_png_path = $filepath . '.tmp.png';
            imagesavealpha($image_resource, true);
            if (imagepng($image_resource, $temp_png_path, 9)) {
                $save_success = bcek_convert_png_to_bmp($temp_png_path, $filepath);
                @unlink($temp_png_path);
            }
        } else {
            imagesavealpha($image_resource, true);
            $save_success = imagepng($image_resource, $filepath, 9);
        }

        imagedestroy($image_resource);

        if (!$save_success) return new WP_Error('image_save_error_to_disk', 'Falha ao salvar o arquivo final.');

        $attach_id = wp_insert_attachment(['guid' => $fileurl, 'post_mime_type' => $mime_type, 'post_title' => $base_filename, 'post_content' => '', 'post_status' => 'inherit'], $filepath);
        if (is_wp_error($attach_id)) return $attach_id;

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $filepath));
        wp_schedule_single_event(time() + 1800, 'bcek_delete_attachment_cron', array('attachment_id' => $attach_id));

        return ['url' => $fileurl, 'file_id' => $attach_id, 'filename' => $final_filename, 'deleted_in' => sprintf(__('Imagem será excluída em %s.', 'bcek'), human_time_diff(time() + 1800))];
    }
}