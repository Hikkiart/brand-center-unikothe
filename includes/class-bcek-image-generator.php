<?php
/**
 * Classe responsável por gerar a imagem final com base nos inputs do utilizador.
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

class BCEK_Image_Generator {

    private $template_data;
    private $fields_data;
    private $user_inputs;

    public function __construct( $template_id, $user_inputs = [] ) {
        $this->template_data = BCEK_Database::get_template_by_id( $template_id );
        $this->fields_data = BCEK_Database::get_fields_for_template( $template_id );
        $this->user_inputs = $user_inputs; // Guarda os inputs do utilizador
    }

    /**
     * Gera o recurso da imagem final.
     * @return resource|false O recurso da imagem GD ou false em caso de erro.
     */
    public function generate_image_resource() {
        if ( ! $this->template_data || empty( $this->template_data->base_image_id ) ) {
            bcek_log_to_file('Template data or base image not found.');
            return false;
        }

        $base_image_path = get_attached_file( $this->template_data->base_image_id );
        if ( ! $base_image_path || ! file_exists( $base_image_path ) ) {
            bcek_log_to_file('Base image file not found at path: ' . $base_image_path);
            return false;
        }
        
        // Carrega a imagem base
        $image_resource = imagecreatefromstring( file_get_contents( $base_image_path ) );
        if ( ! $image_resource ) return false;

        // Ativa a transparência para PNGs
        imagealphablending($image_resource, true);
        imagesavealpha($image_resource, true);

        // Desenha os campos
        foreach ( $this->fields_data as $field ) {
            if ( $field->field_type === 'text' && isset($this->user_inputs[$field->field_id]) ) {
                $this->draw_text_field( $image_resource, $field );
            } elseif ( $field->field_type === 'image' && isset($this->user_inputs[$field->field_id]) ) {
                $this->draw_image_field( $image_resource, $field );
            }
        }
        
        return $image_resource;
    }

    /**
     * Desenha um campo de texto na imagem.
     */
    private function draw_text_field( &$image_resource, $field ) {
        // ... (esta função permanece como a tínhamos, com a lógica de quebra de linha) ...
    }

    /**
     * --- NOVA FUNÇÃO ---
     * Desenha um campo de imagem (enviado pelo utilizador) na imagem base.
     */
    private function draw_image_field( &$image_resource, $field ) {
        $user_image_data = $this->user_inputs[$field->field_id]['image'] ?? null;
        if ( ! $user_image_data ) return;

        // O JavaScript envia a imagem como um data URL (Base64). Precisamos de a descodificar.
        if ( preg_match('/^data:image\/(\w+);base64,/', $user_image_data, $type) ) {
            $data = substr($user_image_data, strpos($user_image_data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            $data = base64_decode($data);
            if ($data === false) return; // Erro na descodificação

            $user_image_resource = imagecreatefromstring($data);
            if ($user_image_resource) {
                // Desenha a imagem do utilizador na imagem base
                imagecopyresampled(
                    $image_resource, // Destino
                    $user_image_resource, // Fonte
                    (int)$field->pos_x, (int)$field->pos_y, // Coordenadas de destino (X, Y)
                    0, 0, // Coordenadas da fonte (canto superior esquerdo)
                    (int)$field->width, (int)$field->height, // Largura e altura de destino
                    imagesx($user_image_resource), imagesy($user_image_resource) // Largura e altura da fonte
                );
                imagedestroy($user_image_resource);
            }
        }
    }
}