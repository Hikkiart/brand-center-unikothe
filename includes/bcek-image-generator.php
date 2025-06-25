<?php
/**
 * Funções para geração de imagem usando a biblioteca GD do PHP.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

function bcek_wrap_text_for_gd( $font_size, $angle, $font_file, $text, $max_width ) {
    if (empty(trim((string)$text))) return [];
    $text = str_replace(["\r\n", "\r"], "\n", (string)$text);
    $paragraphs = explode("\n", $text);
    $final_lines = [];
    foreach ($paragraphs as $paragraph) {
        if (trim($paragraph) === '' && strpos($text, "\n") !== false) {
            $final_lines[] = ''; 
            continue;
        }
        $words = explode(' ', $paragraph);
        $current_line = '';
        if (count($words) > 0) {
            $current_line = array_shift($words) ?? '';
        }
        foreach ($words as $word) {
            if(empty($word) && $word !== '0') continue;
            $test_line = $current_line . (empty($current_line) ? '' : ' ') . $word;
            $bbox = @imagettfbbox($font_size, $angle, $font_file, $test_line);
            if ($bbox === false) continue;
            $current_width = $bbox[2] - $bbox[0];
            if ($current_width > $max_width && !empty($current_line)) {
                $final_lines[] = rtrim($current_line); 
                $current_line = $word;      
            } else {
                $current_line = $test_line;     
            }
        }
        $final_lines[] = rtrim($current_line);
    }
    return $final_lines;
}

function bcek_draw_user_image_on_gd_canvas($main_image_canvas, $field_obj, $user_inputs) {
    if (!$main_image_canvas || !is_object($field_obj) || !isset($user_inputs[$field_obj->field_id])) return false; 
    $image_input_data = $user_inputs[$field_obj->field_id];
    if (!isset($image_input_data['type']) || $image_input_data['type'] !== 'image' || empty($image_input_data['imageDataUrl'])) return true; 

    $imageDataUrl = $image_input_data['imageDataUrl'];
    if (strpos($imageDataUrl, 'base64,') === false) return new WP_Error('invalid_base64_image_data', 'Dados da imagem inválidos.');
    $base64_data = substr($imageDataUrl, strpos($imageDataUrl, ',') + 1);
    $decoded_image_data = base64_decode($base64_data);
    if ($decoded_image_data === false) return new WP_Error('base64_decode_failed', 'Falha ao descodificar imagem.');
    
    $user_img_resource = @imagecreatefromstring($decoded_image_data);
    if (!$user_img_resource) return new WP_Error('gd_image_from_string_failed', 'Falha ao criar imagem.');

    $x = intval($field_obj->pos_x ?? 0);
    $y = intval($field_obj->pos_y ?? 0);
    
    imagecopy($main_image_canvas, $user_img_resource, $x, $y, 0, 0, imagesx($user_img_resource), imagesy($user_img_resource));
    imagedestroy($user_img_resource);
    return true;
}

function bcek_generate_image_with_gd( $template, $fields, $user_inputs, $user_filename = '', $format = 'png' ) {
    if (!extension_loaded('gd')) return new WP_Error('gd_not_loaded', 'A biblioteca GD não está habilitada.');

    $base_image_resource = null;
    $base_image_path = get_attached_file( $template->base_image_id ?? 0 );
    
    if ($base_image_path && file_exists($base_image_path)) {
        $image_info = @getimagesize($base_image_path);
        if (!$image_info) return new WP_Error('image_load_info_error', 'Não foi possível ler a imagem base.');
        $final_image_width = $image_info[0];
        $final_image_height = $image_info[1];
        $base_image_mime = $image_info['mime'];
    } else {
        $max_w = 0; $max_h = 0;
        if (is_array($fields)) {
            foreach($fields as $field) {
                if (is_object($field)) {
                    $right_edge = intval($field->pos_x ?? 0) + intval($field->width ?? 0);
                    $bottom_edge = intval($field->pos_y ?? 0) + intval($field->height ?? 0);
                    if ($right_edge > $max_w) $max_w = $right_edge;
                    if ($bottom_edge > $max_h) $max_h = $bottom_edge;
                }
            }
        }
        $final_image_width = $max_w > 0 ? $max_w + 20 : 1200;
        $final_image_height = $max_h > 0 ? $max_h + 20 : 800;
        $base_image_mime = 'image/png';
    }

    $image = imagecreatetruecolor($final_image_width, $final_image_height);
    if (!$image) return new WP_Error('image_create_final_fail', 'Falha ao criar canvas.');
    
    $is_png = ($base_image_mime ?? 'image/png') === 'image/png';
    if ($is_png) { 
        imagealphablending($image, false); imagesavealpha($image, true);
        $transparent_color = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefill($image, 0, 0, $transparent_color);
    } else { 
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
    }
    imagealphablending($image, true); 

    if ($base_image_path && file_exists($base_image_path)) {
        switch ($base_image_mime) {
            case 'image/png': $base_image_resource = @imagecreatefrompng($base_image_path); break;
            case 'image/jpeg': $base_image_resource = @imagecreatefromjpeg($base_image_path); break;
        }
        if ($base_image_resource) {
            imagecopy($image, $base_image_resource, 0, 0, 0, 0, $final_image_width, $final_image_height);
            imagedestroy($base_image_resource);
        }
    }

    $fields_below = array_filter($fields, fn($f) => is_object($f) && ($f->field_type ?? '') === 'image' && intval($f->z_index_order ?? 1) === 0);
    $fields_above = array_filter($fields, fn($f) => is_object($f) && ($f->field_type ?? '') === 'image' && intval($f->z_index_order ?? 1) === 1);
    $text_fields = array_filter($fields, fn($f) => is_object($f) && ($f->field_type ?? 'text') === 'text');
    
    foreach ($fields_below as $field) bcek_draw_user_image_on_gd_canvas($image, $field, $user_inputs);
    foreach ($fields_above as $field) bcek_draw_user_image_on_gd_canvas($image, $field, $user_inputs);

    foreach ( $text_fields as $field ) {
        $text_data = $user_inputs[$field->field_id] ?? null;
        $text = $text_data['text'] ?? ($field->default_text ?? '');
        if (empty(trim($text))) continue;

        $font_size_px = intval($text_data['fontSize'] ?? $field->font_size ?? 16);
        $font_size_for_gd = round($font_size_px * 0.75); // Restaura o fator de correção
        
        $font_path = BCEK_PLUGIN_DIR . 'assets/fonts/' . ($field->font_family ?? 'Montserrat-Regular') . '.ttf';
        if (!file_exists($font_path)) continue;
        
        $color_parts = sscanf($field->font_color ?? '#000000', "#%02x%02x%02x");
        $text_color = imagecolorallocate($image, ...$color_parts);

        $pos_x = intval($field->pos_x ?? 0);
        $pos_y_block_top = intval($field->pos_y ?? 0);
        $block_width = intval($field->width ?? 100);
        $block_height = intval($field->height ?? 50);
        $text_padding = 3;
        $effective_block_width = $block_width - ($text_padding * 2);
        
        $lines = bcek_wrap_text_for_gd( $font_size_for_gd, 0, $font_path, $text, $effective_block_width );
        $line_height_multiplier = floatval($field->line_height_multiplier ?? 1.3);
        $actual_line_height_pixels = round($font_size_px * $line_height_multiplier);
        
        // Restaura o cálculo de Y original que funcionava
        $current_y_baseline = $pos_y_block_top + $font_size_for_gd + $text_padding;

        foreach ($lines as $line) {
            $alignment = $field->alignment ?? 'left';
            $line_bbox = imagettfbbox($font_size_for_gd, 0, $font_path, $line);
            $line_width = $line_bbox[2] - $line_bbox[0];
            $draw_x = $pos_x + $text_padding;
            if ($alignment === 'center') {
                $draw_x = $pos_x + (($block_width - $line_width) / 2);
            } elseif ($alignment === 'right') {
                $draw_x = $pos_x + $block_width - $line_width - $text_padding;
            }
            
            imagettftext($image, $font_size_for_gd, 0, (int)$draw_x, (int)$current_y_baseline, $text_color, $font_path, $line);
            $current_y_baseline += $actual_line_height_pixels;
        }
    }

    $upload_dir = wp_upload_dir();
    $base_filename = sanitize_file_name($user_filename ?: 'bcek-image');
    $filename_hash = substr(md5(uniqid(rand(), true)), 0, 6);
    $extension = ($format === 'bmp') ? 'bmp' : 'png';
    $final_filename = "{$base_filename}_{$filename_hash}.{$extension}";
    $filepath = "{$upload_dir['path']}/{$final_filename}";
    $fileurl = "{$upload_dir['url']}/{$final_filename}";

    $save_success = false;
    if ($format === 'bmp') {
        if (function_exists('imagebmp')) $save_success = imagebmp($image, $filepath);
    } else {
        $save_success = imagepng($image, $filepath, 9);
    }
    imagedestroy($image);
    if (!$save_success) return new WP_Error('image_save_error', 'Falha ao salvar a imagem.');

    $attach_id = wp_insert_attachment(['guid' => $fileurl, 'post_mime_type' => "image/{$extension}", 'post_title' => $base_filename, 'post_status' => 'inherit'], $filepath);
    if (is_wp_error($attach_id)) return new WP_Error('media_insert_error', $attach_id->get_error_message());
    
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $filepath));
    wp_schedule_single_event(time() + 1800, 'bcek_delete_attachment_cron', ['attachment_id' => $attach_id]);

    return ['url' => $fileurl, 'file_id' => $attach_id, 'filename' => $final_filename, 'deleted_in' => sprintf(__('Imagem será excluída em %s.', 'bcek'), human_time_diff(time() + 1800))];
}
?>
