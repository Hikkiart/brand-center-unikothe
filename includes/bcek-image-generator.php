<?php
/**
 * Funções para geração de imagem usando a biblioteca GD do PHP.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Função auxiliar para quebrar o texto, respeitando quebras manuais (\n) e automáticas,
 * para uso com imagettftext da biblioteca GD.
 */
function bcek_wrap_text_for_gd( $font_size, $angle, $font_file, $text, $max_width ) {
    if (empty(trim((string)$text))) return '';
    
    // Garante consistência nas quebras de linha (Unix \n)
    $text = str_replace("\r\n", "\n", (string)$text);
    // Divide o texto em parágrafos com base nas quebras de linha manuais
    $paragraphs = explode("\n", $text);
    
    $final_lines = array(); // Array para guardar todas as linhas finais

    foreach ($paragraphs as $paragraph) {
        // Se o parágrafo estiver vazio (resultado de dois "Enter" seguidos), adiciona uma linha vazia para manter o espaçamento
        if ($paragraph === '') {
            $final_lines[] = ''; 
            continue;
        }

        $words = explode(' ', $paragraph);
        $current_line = '';
        // Garante que a primeira palavra seja processada mesmo se o parágrafo tiver apenas uma palavra.
        if (count($words) > 0) {
            $current_line = array_shift($words); 
            if ($current_line === null) $current_line = ''; // Caso de parágrafo ser só espaços
        }

        foreach ($words as $word) {
            // Se a palavra for vazia (múltiplos espaços), pula para evitar processamento desnecessário
            if(empty($word) && $word !== '0') continue;
            
            // Testa adicionar a próxima palavra à linha atual
            $test_line = $current_line . (empty($current_line) ? '' : ' ') . $word;
            
            $bbox = @imagettfbbox($font_size, $angle, $font_file, $test_line);
            if ($bbox === false) { 
                error_log("BCEK wrap_text_gd: imagettfbbox falhou. Texto: '$test_line', Fonte: $font_file");
                if(!empty($current_line) && $current_line !== $word) $final_lines[] = rtrim($current_line);
                $current_line = $word;
                continue; 
            }
            $current_width = $bbox[2] - $bbox[0];

            if ($current_width > $max_width && !empty($current_line)) {
                $final_lines[] = rtrim($current_line); // Adiciona a linha anterior
                $current_line = $word;      // Começa uma nova linha
            } else {
                $current_line = $test_line; // Continua na mesma linha
            }
        }
        $final_lines[] = rtrim($current_line); // Adiciona a última linha do parágrafo
    }
    
    return implode("\n", $final_lines);
}

/**
 * Função auxiliar para desenhar a imagem do utilizador no canvas GD, aplicando object-fit: cover e máscara circular.
 */
function bcek_draw_user_image_on_gd_canvas($main_image_canvas, $field_obj, $user_inputs) {
    if (!$main_image_canvas || !is_object($field_obj) || !isset($field_obj->field_id) || !isset($user_inputs[$field_obj->field_id])) {
        error_log('BCEK Draw User Image: Parâmetros inválidos ou field_id não encontrado em user_inputs.');
        return false; // Ou retornar um WP_Error
    }

    $image_input_data = $user_inputs[$field_obj->field_id];
    if (!isset($image_input_data['type']) || $image_input_data['type'] !== 'image' || empty($image_input_data['imageDataUrl'])) {
        return true; // Não é um erro, apenas não há imagem para este campo.
    }

    $imageDataUrl = $image_input_data['imageDataUrl'];

    if (strpos($imageDataUrl, 'base64,') === false) {
        error_log('BCEK Draw User Image: imageDataUrl não parece ser uma string base64 válida.');
        return new WP_Error('invalid_base64_image_data', 'Os dados da imagem fornecida não são válidos.');
    }
    
    $base64_data = substr($imageDataUrl, strpos($imageDataUrl, ',') + 1);
    $decoded_image_data = base64_decode($base64_data);

    if ($decoded_image_data === false) {
        error_log('BCEK Draw User Image: Falha ao descodificar imagem base64 do utilizador.');
        return new WP_Error('base64_decode_failed', 'Falha ao descodificar os dados da imagem.');
    }
    $user_img_resource = @imagecreatefromstring($decoded_image_data);
    if (!$user_img_resource) {
        error_log('BCEK Draw User Image: Falha ao criar recurso de imagem GD a partir da string de dados do utilizador.');
        return new WP_Error('gd_image_from_string_failed', 'Falha ao criar imagem a partir dos dados fornecidos.');
    }

    // Garante que a imagem do utilizador seja truecolor e lide com transparência
    if (!imageistruecolor($user_img_resource)) {
        $temp_truecolor = @imagecreatetruecolor(imagesx($user_img_resource), imagesy($user_img_resource));
        if ($temp_truecolor) {
            $transparent_index = @imagecolortransparent($user_img_resource);
            if ($transparent_index >= 0 && $transparent_index < @imagecolorstotal($user_img_resource)) { 
                $transparent_color_gif = @imagecolorsforindex($user_img_resource, $transparent_index);
                if($transparent_color_gif){
                    $transparent_new = @imagecolorallocatealpha($temp_truecolor, $transparent_color_gif['red'], $transparent_color_gif['green'], $transparent_color_gif['blue'], 127);
                    @imagefill($temp_truecolor, 0, 0, $transparent_new);
                    @imagecolortransparent($temp_truecolor, $transparent_new);
                }
            }
            @imagecopy($temp_truecolor, $user_img_resource, 0, 0, 0, 0, imagesx($user_img_resource), imagesy($user_img_resource));
            @imagedestroy($user_img_resource);
            $user_img_resource = $temp_truecolor;
        } else {
            @imagedestroy($user_img_resource);
            return new WP_Error('image_truecolor_conversion_failed_user', 'Falha ao converter imagem do utilizador para truecolor.');
        }
    }
    @imagealphablending($user_img_resource, false);
    @imagesavealpha($user_img_resource, true);    

    $x = intval($field_obj->pos_x ?? 0);
    $y = intval($field_obj->pos_y ?? 0);
    $container_width = intval($field_obj->width ?? 100);
    $container_height = intval($field_obj->height ?? 50);
    $container_shape = $field_obj->container_shape ?? 'rectangle';

    if ($container_width <= 0 || $container_height <= 0) {
        @imagedestroy($user_img_resource);
        return new WP_Error('invalid_container_dimensions', 'Dimensões do contêiner de imagem inválidas.');
    }

    $img_original_width = imagesx($user_img_resource);
    $img_original_height = imagesy($user_img_resource);
    if ($img_original_width <= 0 || $img_original_height <= 0) {
        @imagedestroy($user_img_resource);
        return new WP_Error('invalid_user_image_dimensions', 'Dimensões da imagem do utilizador inválidas.');
    }

    $src_x = 0; $src_y = 0;
    $src_w = $img_original_width; $src_h = $img_original_height;
    $dst_x = $x; $dst_y = $y;
    $dst_w = $container_width; $dst_h = $container_height;

    $img_aspect_ratio = $img_original_width / $img_original_height;
    $container_aspect_ratio = $container_width / $container_height;

    if ($img_aspect_ratio > $container_aspect_ratio) { 
        $src_h = $img_original_height;
        $src_w = $img_original_height * $container_aspect_ratio;
        $src_x = ($img_original_width - $src_w) / 2;
    } else { 
        $src_w = $img_original_width;
        $src_h = $img_original_width / $container_aspect_ratio;
        $src_y = ($img_original_height - $src_h) / 2;
    }
    
    $temp_cropped_image = imagecreatetruecolor($container_width, $container_height);
    if (!$temp_cropped_image) { @imagedestroy($user_img_resource); return new WP_Error('temp_cropped_create_fail', 'Falha ao criar imagem temporária para corte.'); }
    
    imagealphablending($temp_cropped_image, false);
    imagesavealpha($temp_cropped_image, true);
    $transparent_temp = imagecolorallocatealpha($temp_cropped_image, 0,0,0,127);
    imagefill($temp_cropped_image, 0, 0, $transparent_temp);

    imagecopyresampled(
        $temp_cropped_image, $user_img_resource,
        0, 0, (int)$src_x, (int)$src_y,
        (int)$container_width, (int)$container_height, (int)$src_w, (int)$src_h
    );
    imagedestroy($user_img_resource);

    if ($container_shape === 'circle') {
        $mask = imagecreatetruecolor($container_width, $container_height);
        if (!$mask) { @imagedestroy($temp_cropped_image); return new WP_Error('mask_create_fail', 'Falha ao criar máscara circular.'); }
        
        $transparent_mask = imagecolorallocatealpha($mask, 255, 255, 255, 127);
        imagefill($mask, 0, 0, $transparent_mask);
        $opaque_mask = imagecolorallocate($mask, 0, 0, 0);
        imagefilledellipse($mask, $container_width / 2, $container_height / 2, $container_width -1 , $container_height -1, $opaque_mask);
        
        imagealphablending($temp_cropped_image, true);
        imagecopy($temp_cropped_image, $mask, 0, 0, 0, 0, $container_width, $container_height);
        
        imagealphablending($temp_cropped_image, false);
        imagesavealpha($temp_cropped_image, true);
        imagedestroy($mask);
    }
    
    imagecopy($main_image_canvas, $temp_cropped_image, (int)$dst_x, (int)$dst_y, 0, 0, (int)$container_width, (int)$container_height);
    imagedestroy($temp_cropped_image);

    return true;
}

/**
 * Gera a imagem final com os textos e imagens do utilizador sobrepostos.
 * Modificado para aceitar um formato de saída (png ou bmp).
 */
function bcek_generate_image_with_gd( $template, $fields, $user_inputs, $user_filename = '', $format = 'png' ) {
    if ( ! extension_loaded( 'gd' ) || ! function_exists( 'gd_info' ) ) {
        return new WP_Error( 'gd_not_loaded', 'A biblioteca GD não está habilitada no servidor.' );
    }

    $final_image_width = 800; 
    $final_image_height = 600; 

    $base_image_resource = null;
    $base_image_path = null;
    $base_image_mime = null;

    if ( $template && isset($template->base_image_id) && $template->base_image_id > 0 ) {
        $base_image_path = get_attached_file( $template->base_image_id );
        if ( !$base_image_path || !file_exists( $base_image_path ) ) {
            return new WP_Error( 'base_image_file_not_found', __('Arquivo da imagem base não encontrado no servidor para o ID: ', 'bcek') . $template->base_image_id . " Path: " . ($base_image_path ?: 'N/A') );
        }
        $image_info = @getimagesize( $base_image_path );
        if ( ! $image_info ) return new WP_Error( 'image_load_info_error', 'Não foi possível ler informações da imagem base. Path: ' . $base_image_path );
        
        $final_image_width = $image_info[0];
        $final_image_height = $image_info[1];
        $base_image_mime = $image_info['mime'];
        
        switch ( $base_image_mime ) {
            case 'image/png': $base_image_resource = @imagecreatefrompng( $base_image_path ); break;
            case 'image/jpeg': $base_image_resource = @imagecreatefromjpeg( $base_image_path ); break;
            case 'image/gif': $base_image_resource = @imagecreatefromgif( $base_image_path ); break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $base_image_resource = @imagecreatefromwebp($base_image_path);
                } else {
                    return new WP_Error('webp_not_supported', 'O formato de imagem WebP não é suportado pelo seu servidor. Por favor, use PNG ou JPG para a imagem base.');
                }
                break;
            default: return new WP_Error( 'unsupported_image_type', 'Tipo de imagem base não suportado: ' . esc_html($base_image_mime) );
        }
        if ( ! $base_image_resource ) return new WP_Error( 'image_create_from_file_error', 'Falha ao criar recurso de imagem GD da imagem base. Path: ' . $base_image_path );
        
        if (!imageistruecolor($base_image_resource)) {
            $trueColorImage = @imagecreatetruecolor(imagesx($base_image_resource), imagesy($base_image_resource));
            if (!$trueColorImage) { if($base_image_resource) @imagedestroy($base_image_resource); return new WP_Error('image_truecolor_fail_base', 'Falha ao criar truecolor para imagem base.');}
            @imagecopy($trueColorImage, $base_image_resource, 0, 0, 0, 0, imagesx($base_image_resource), imagesy($base_image_resource)); 
            @imagedestroy($base_image_resource); $base_image_resource = $trueColorImage;
        }
    } else {
        $max_w = 0; $max_h = 0;
        if (is_array($fields)) {
            foreach($fields as $field_obj_calc) {
                if (is_object($field_obj_calc) && isset($field_obj_calc->pos_x, $field_obj_calc->width, $field_obj_calc->pos_y, $field_obj_calc->height)) {
                    if (($field_obj_calc->pos_x + $field_obj_calc->width) > $max_w) $max_w = $field_obj_calc->pos_x + $field_obj_calc->width;
                    if (($field_obj_calc->pos_y + $field_obj_calc->height) > $max_h) $max_h = $field_obj_calc->pos_y + $field_obj_calc->height;
                }
            }
        }
        $final_image_width = $max_w > 0 ? $max_w + 40 : 800; 
        $final_image_height = $max_h > 0 ? $max_h + 40 : 600;
    }

    $image = @imagecreatetruecolor($final_image_width, $final_image_height);
    if (!$image) return new WP_Error('image_create_final_fail', 'Falha ao criar canvas principal.');
    
    if (!$base_image_resource || $base_image_mime === 'image/png' || $base_image_mime === 'image/webp') { 
        @imagealphablending($image, false); 
        @imagesavealpha($image, true);
        $transparent_color = @imagecolorallocatealpha($image, 0, 0, 0, 127);
        if ($transparent_color === false) { @imagedestroy($image); return new WP_Error('image_color_allocate_alpha_fail_final', 'Falha ao alocar cor transparente final.');}
        @imagefill($image, 0, 0, $transparent_color); 
    } else { 
        $white = @imagecolorallocate($image, 255, 255, 255);
        if ($white === false) { @imagedestroy($image); return new WP_Error('image_color_allocate_white_fail_final', 'Falha ao alocar cor branca final.');}
        @imagefill($image, 0, 0, $white);
    }
    @imagealphablending( $image, true ); 

    $fields_below_base = [];
    $fields_above_base = [];
    $text_fields = [];

    if (is_array($fields)) {
        foreach ($fields as $field_obj) {
            if (!is_object($field_obj) || !isset($field_obj->field_id)) continue;
            if (isset($field_obj->field_type) && $field_obj->field_type === 'image') {
                if (isset($field_obj->z_index_order) && intval($field_obj->z_index_order) === 0) {
                    $fields_below_base[] = $field_obj;
                } else { 
                    $fields_above_base[] = $field_obj;
                }
            } elseif (!isset($field_obj->field_type) || $field_obj->field_type === 'text') {
                $text_fields[] = $field_obj;
            }
        }
    }
    
    foreach ($fields_below_base as $field_obj) {
        $draw_result = bcek_draw_user_image_on_gd_canvas($image, $field_obj, $user_inputs);
        if (is_wp_error($draw_result)) return $draw_result;
    }

    if ($base_image_resource) {
        if(!@imagecopy($image, $base_image_resource, 0, 0, 0, 0, imagesx($base_image_resource), imagesy($base_image_resource))) {
            @imagedestroy($base_image_resource); @imagedestroy($image);
            return new WP_Error('base_image_copy_fail', 'Falha ao copiar imagem base para o canvas final.');
        }
        @imagedestroy($base_image_resource); 
    }

    foreach ($fields_above_base as $field_obj) {
        $draw_result = bcek_draw_user_image_on_gd_canvas($image, $field_obj, $user_inputs);
        if (is_wp_error($draw_result)) return $draw_result;
    }

    foreach ( $text_fields as $field_obj ) {
        $text_data = $user_inputs[ $field_obj->field_id ] ?? null;
        
        $user_provided_text = isset($text_data['text']) ? $text_data['text'] : null;
        $text = is_string($user_provided_text) ? $user_provided_text : ($field_obj->default_text ?? '');
        
        $font_size_from_user = $text_data['fontSize'] ?? null;
        $font_size_px = (is_numeric($font_size_from_user) && $font_size_from_user > 0) ? intval($font_size_from_user) : intval( $field_obj->font_size ?? 16 );
        $gd_font_size_correction_factor = 0.75; 
        $font_size_for_gd = round($font_size_px * $gd_font_size_correction_factor);
        if ($font_size_for_gd < 1) $font_size_for_gd = 1;
        
        $line_height_multiplier = isset($field_obj->line_height_multiplier) ? floatval($field_obj->line_height_multiplier) : 1.3;
        
        if ( empty( trim( $text ) ) && strpos($text, "\n") === false) {
             continue;
        }

        $font_file_name = ($field_obj->font_family ?? 'Montserrat-Regular') . '.ttf'; 
        $font_path = BCEK_PLUGIN_DIR . 'assets/fonts/' . $font_file_name;
        if ( ! file_exists( $font_path ) ) { @imagedestroy($image); return new WP_Error('font_file_missing_text', 'Arquivo de fonte para texto não encontrado: ' . esc_html($font_file_name)); }
        
        $color_parts = sscanf( $field_obj->font_color ?? '#000000', "#%02x%02x%02x" );
        if (count($color_parts) !== 3) $color_parts = array(0,0,0); list( $r, $g, $b ) = $color_parts;
        $text_color = @imagecolorallocatealpha( $image, $r, $g, $b, 0 ); 
        if ($text_color === false) { @imagedestroy($image); return new WP_Error('text_color_allocate_fail', 'Falha ao alocar cor para o texto.');}

        $pos_x = intval( $field_obj->pos_x ?? 0 ); $pos_y_block_top = intval( $field_obj->pos_y ?? 0 ); 
        $block_width = intval( $field_obj->width ?? 100 ); $block_height = intval( $field_obj->height ?? 50 );
        $text_padding = 3; $effective_block_width = $block_width - ($text_padding * 2);
        if ($effective_block_width <=0) $effective_block_width = 1;
        
        $wrapped_text = bcek_wrap_text_for_gd( $font_size_for_gd, 0, $font_path, $text, $effective_block_width );
        $lines = explode( "\n", $wrapped_text );
        $actual_line_height_pixels = round($font_size_px * $line_height_multiplier);
        if ($actual_line_height_pixels < 1) $actual_line_height_pixels = $font_size_px;
        $current_text_y_baseline = $pos_y_block_top + $font_size_for_gd + $text_padding;
        $num_lines = count($lines);

        foreach ( $lines as $line_index => $line ) {
            if ( ($current_text_y_baseline - $pos_y_block_top - $font_size_for_gd + $actual_line_height_pixels + $text_padding ) > $block_height && $line_index > 0 ) break; 
            
            if ($line === '') {
                $current_text_y_baseline += $actual_line_height_pixels;
                continue;
            }

            $alignment = $field_obj->alignment ?? 'left';
            $is_last_line_of_paragraph = ($line_index === $num_lines - 1) || (isset($lines[$line_index + 1]) && trim($lines[$line_index + 1]) === '');
            
            if ($alignment === 'justify' && !$is_last_line_of_paragraph && strpos($line, ' ') !== false) {
                $words_in_line = explode(' ', $line);
                $num_words_in_line = count($words_in_line);
                if ($num_words_in_line > 1) {
                    $text_without_spaces = str_replace(' ', '', $line);
                    $text_bbox_justify = @imagettfbbox($font_size_for_gd, 0, $font_path, $text_without_spaces);
                    if ($text_bbox_justify === false) { continue; }
                    $total_text_width_justify = $text_bbox_justify[2] - $text_bbox_justify[0];
                    $total_spacing_needed = $effective_block_width - $total_text_width_justify;
                    $space_per_gap = ($num_words_in_line > 1) ? $total_spacing_needed / ($num_words_in_line - 1) : 0;
                    $space_char_bbox = @imagettfbbox($font_size_for_gd, 0, $font_path, ' ');
                    $single_space_width = ($space_char_bbox === false) ? ($font_size_for_gd / 2) : ($space_char_bbox[2] - $space_char_bbox[0]);

                    if ($space_per_gap > ($single_space_width * 4) || $space_per_gap < ($single_space_width * -0.5) ) { 
                         $text_draw_x_justify = $pos_x + $text_padding; 
                         @imagettftext( $image, $font_size_for_gd, 0, (int)$text_draw_x_justify, (int)$current_text_y_baseline, $text_color, $font_path, $line );
                    } else {
                        $current_x_justify = $pos_x + $text_padding;
                        foreach ($words_in_line as $j_word => $word_item) {
                            @imagettftext( $image, $font_size_for_gd, 0, (int)$current_x_justify, (int)$current_text_y_baseline, $text_color, $font_path, $word_item );
                            $word_bbox_justify = @imagettfbbox($font_size_for_gd, 0, $font_path, $word_item);
                            $current_x_justify += ($word_bbox_justify === false ? $font_size_for_gd : ($word_bbox_justify[2] - $word_bbox_justify[0]));
                            if ($j_word < $num_words_in_line - 1) $current_x_justify += $space_per_gap;
                        }
                    }
                } else { 
                    $text_bbox_line_single_word = @imagettfbbox( $font_size_for_gd, 0, $font_path, $line );
                    if ($text_bbox_line_single_word !== false) {
                        $line_actual_width_single_word = $text_bbox_line_single_word[2] - $text_bbox_line_single_word[0]; 
                        $text_draw_x_single_word = $pos_x + $text_padding;
                        $final_alignment_single_word = ($alignment === 'justify') ? 'left' : $alignment; 
                        if ( $final_alignment_single_word === 'center' ) $text_draw_x_single_word = $pos_x + ( ( $block_width - $line_actual_width_single_word ) / 2 );
                        elseif ( $final_alignment_single_word === 'right' ) $text_draw_x_single_word = $pos_x + $block_width - $line_actual_width_single_word - $text_padding;
                        @imagettftext( $image, $font_size_for_gd, 0, (int)$text_draw_x_single_word, (int)$current_text_y_baseline, $text_color, $font_path, $line );
                    }
                }
            } else { 
                $text_bbox_line = @imagettfbbox( $font_size_for_gd, 0, $font_path, $line ); 
                if ($text_bbox_line === false) { continue; }
                $line_actual_width = $text_bbox_line[2] - $text_bbox_line[0]; 
                $text_draw_x = $pos_x + $text_padding;
                $final_alignment = ($alignment === 'justify') ? 'left' : $alignment; 
                if ( $final_alignment === 'center' ) $text_draw_x = $pos_x + ( ( $block_width - $line_actual_width ) / 2 );
                elseif ( $final_alignment === 'right' ) $text_draw_x = $pos_x + $block_width - $line_actual_width - $text_padding;
                @imagettftext( $image, $font_size_for_gd, 0, (int)$text_draw_x, (int)$current_text_y_baseline, $text_color, $font_path, $line );
            }
            $current_text_y_baseline += $actual_line_height_pixels; 
        }
    }

    $upload_dir = wp_upload_dir();
    $base_filename = 'bcek-image'; 
    if (!empty($user_filename)) $base_filename = sanitize_file_name($user_filename);
    $filename_hash = substr(md5(uniqid(rand(), true)), 0, 6); 
    
    $extension = ($format === 'bmp') ? 'bmp' : 'png';
    $mime_type = ($format === 'bmp') ? 'image/bmp' : 'image/png';
    
    $final_filename = $base_filename . '_' . $filename_hash . '.' . $extension;
    $filepath = $upload_dir['path'] . '/' . $final_filename; 
    $fileurl = $upload_dir['url'] . '/' . $final_filename;

    $save_success = false;
    
    if ($format === 'bmp') {
        if (function_exists('bcek_convert_png_to_bmp')) {
            $temp_png_filepath = $filepath . '.tmp.png';
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $png_saved_successfully = @imagepng($image, $temp_png_filepath, 9);

            if ($png_saved_successfully) {
                $save_success = bcek_convert_png_to_bmp($temp_png_filepath, $filepath);
                @unlink($temp_png_filepath);
            } else {
                 error_log("BCEK Image Gen: Falha ao salvar o ficheiro PNG temporário.");
                 $save_success = false;
            }
        } else {
            @imagedestroy( $image );
            return new WP_Error('bmp_function_missing', 'A função para converter para BMP não foi encontrada.');
        }
    } else { 
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $save_success = @imagepng( $image, $filepath, 9 ); 
    }

    if ( ! $save_success ) { 
        @imagedestroy( $image ); 
        return new WP_Error( 'image_save_error_to_disk', 'Falha ao salvar o arquivo ' . strtoupper($extension) . ' no servidor.' ); 
    }
    
    @imagedestroy( $image ); 

    $attachment_title = !empty($user_filename) ? sanitize_text_field(str_replace(['-', '_'], ' ', $user_filename)) : 'Brand Center Image';
    $attachment = array( 
        'guid'           => $fileurl, 
        'post_mime_type' => $mime_type, 
        'post_title'     => $attachment_title, 
        'post_content'   => '', 
        'post_status'    => 'inherit' 
    );
    $attach_id = wp_insert_attachment( $attachment, $filepath );
    if ( is_wp_error( $attach_id ) ) { 
        if(file_exists($filepath)) @unlink( $filepath ); 
        return new WP_Error( 'media_insert_error', 'Falha ao adicionar imagem à biblioteca de média: ' . $attach_id->get_error_message() ); 
    }

    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $filepath );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    $deletion_time = time() + 1800;
    wp_schedule_single_event( $deletion_time, 'bcek_delete_attachment_cron', array( 'attachment_id' => $attach_id ) );

    return array( 
        'url' => $fileurl, 
        'file_id' => $attach_id, 
        'filename' => $final_filename, 
        'deleted_in' => sprintf(__('Imagem será excluída automaticamente em aproximadamente %s.', 'bcek'), human_time_diff($deletion_time) ) 
    );
}
