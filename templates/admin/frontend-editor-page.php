<?php
/**
 * Template para o editor de templates no front-end.
 */
if ( ! defined( 'WPINC' ) ) { die; }

$current_page_url = remove_query_arg( array('action', 'template_id', '_wpnonce') );
$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
$template_data = $template_id > 0 ? BCEK_Database::get_template_by_id($template_id) : null;

// **CORREÇÃO 1:** Define um placeholder se não houver imagem base.
$base_image_src = $template_data->base_image_url ?? 'https://placehold.co/800x600/E2E8F0/A0AEC0?text=Carregue+uma+Imagem';
?>
<div id="template-editor">
    <div class="flex justify-between items-center mb-6">
        <a href="<?php echo esc_url($current_page_url); ?>" id="back-to-list-btn" class="text-gray-600 font-semibold hover:text-gray-900 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
            Voltar
        </a>
        <button id="save-template-btn" class="bcek-button-primary bg-blue-500 text-white font-semibold py-2 px-5 rounded-full transition-colors flex items-center gap-2">
            Salvar Template
        </button>
    </div>

    <div class="flex flex-col md:flex-row gap-6 md:gap-8">
        <div id="editor-preview" class="w-full md:w-2/3 lg:w-3/4 bg-gray-200 rounded-2xl p-4 flex items-center justify-center relative">
            
            <div id="image-upload-loader" style="display: none;">
                <div class="loader-overlay"></div>
                <div class="loader-spinner"></div>
            </div>
            <div id="editor-preview-container" class="relative">
                <img id="base-image-preview" src="<?php echo esc_attr($base_image_src); ?>" alt="Pré-visualização da Imagem Base" class="max-w-full max-h-full rounded-lg shadow-md">
            </div>
        </div>

        <div id="editor-controls" class="w-full md:w-1/3 lg:w-1/4 p-4 md:p-6 bg-gray-50 rounded-2xl overflow-y-auto relative">
            <div class="space-y-6">
                <div>
                    <label for="template-name" class="block text-lg font-bold text-gray-800 mb-2">Nome do Template</label>
                    <input type="text" id="template-name" value="<?php echo esc_attr($template_data->name ?? 'Novo Template'); ?>" class="w-full bg-white rounded-lg p-3 border border-gray-300 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="space-y-2">
                    <label class="block text-lg font-bold text-gray-800">Imagem Base</label>
                    <button id="base-image-upload" class="w-full bg-white border border-gray-300 text-gray-700 font-semibold py-2 px-5 rounded-lg hover:bg-blue-600 transition-colors">
                        Carregar Imagem
                    </button>
                    <input type="hidden" id="base-image-id" value="<?php echo esc_attr($template_data->base_image_id ?? 0); ?>">
                    <input type="hidden" id="base-image-url" value="<?php echo esc_attr($template_data->base_image_url ?? ''); ?>">
                </div>
                
                <div id="fields-list" style="display: none;"></div>
            </div>
            
            <div id="upload-prompt" class="text-center p-4 border-2 border-dashed rounded-lg mt-6">
                <p class="text-gray-500">Por favor, carregue uma imagem base para começar a adicionar campos.</p>
            </div>

            <button id="add-field-btn" class="mt-6 w-full bg-gray-100 text-gray-700 font-semibold py-2 px-5 rounded-lg hover:bg-blue-600 transition-colors flex items-center justify-center gap-2" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                Adicionar Campo
            </button>
        </div>
    </div>
</div>