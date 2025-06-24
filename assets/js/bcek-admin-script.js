// assets/js/bcek-admin-script.js
jQuery(document).ready(function($) {
    'use strict'; // Ativa o modo estrito do JavaScript.

    /**
     * Inicializa o seletor de cores do WordPress (wpColorPicker) para elementos com a classe 'bcek-color-picker'.
     * @param {jQuery|Element} context O contexto DOM onde procurar por seletores de cores.
     */
    function initColorPickers(context) {
        $('.bcek-color-picker', context).wpColorPicker({
            change: debounce(function() { if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview(); }, 300),
            clear: debounce(function() { if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview(); }, 300)
        });
    }
    initColorPickers(document); // Inicializa para campos de cor existentes na carga da página.

    // Lógica para o botão de upload/seleção da imagem base do template.
    var frame; // Variável para guardar a instância do uploader de média do WordPress.
    $('#bcek_base_image_button').on('click', function(e) {
        e.preventDefault(); // Previne a ação padrão do botão.
        if (frame) { // Se a instância do uploader já existe, apenas a abre.
            frame.open();
            return;
        }
        // Cria uma nova instância do uploader de média.
        frame = wp.media({
            title: 'Selecionar Imagem Base',    // Título da janela do uploader.
            button: { text: 'Usar esta imagem' }, // Texto do botão de confirmação.
            multiple: false                     // Permite selecionar apenas uma imagem.
        });

        // Callback para quando uma imagem é selecionada no uploader.
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON(); // Obtém os dados da imagem selecionada.
            $('#bcek_base_image_id').val(attachment.id);     // Guarda o ID do anexo no campo hidden.
            $('#bcek_base_image_url').val(attachment.url);   // Guarda a URL da imagem no campo hidden.
            
            // Atualiza o src da tag <img>. O evento 'load' desta imagem irá acionar setupAdminCanvas.
            $('#bcek_admin_base_image_preview_img').attr('src', attachment.url).show();
            $('#bcek_remove_base_image_button').show(); // Mostra o botão "Remover Imagem".
        });
        frame.open(); // Abre a janela do uploader de média.
    });

    // Lógica para o botão de remover a imagem base selecionada.
    $('#bcek_remove_base_image_button').on('click', function(e) {
        e.preventDefault();
        $('#bcek_base_image_id').val('');   // Limpa o valor do ID da imagem.
        $('#bcek_base_image_url').val('');  // Limpa o valor da URL da imagem.
        // Ao remover, atualiza o src para vazio, o que também acionará a lógica de limpeza no setupAdminCanvas
        $('#bcek_admin_base_image_preview_img').attr('src', '').hide(); 
        if (typeof window.setupAdminCanvas === 'function') {
            window.setupAdminCanvas(); // Limpa e reconfigura o canvas do preview.
        }
        $(this).hide(); // Esconde o botão "Remover Imagem".
    });


    // Gestão dinâmica de campos (adicionar/remover).
    var fieldNextIndex = $('#bcek_fields_container .bcek-field-item').length; // Contador para o índice de novos campos.
    var removedFieldIds = []; // Array para guardar os IDs dos campos que foram removidos.

    // Lógica para o botão "Adicionar Novo Campo".
    $('#bcek_add_field_button').on('click', function() {
        var fieldTemplateHtml = $('#bcek-field-template').html(); 
        var newFieldHtml = fieldTemplateHtml.replace(/{{FIELD_INDEX}}/g, fieldNextIndex)
                                            .replace(/{{FIELD_INDEX_DISPLAY}}/g, fieldNextIndex + 1);
        var $newField = $(newFieldHtml); 
        $('#bcek_fields_container').append($newField); 
        initColorPickers($newField); 
        $newField.find('.bcek-preview-input').on('input change', debounce(window.drawAdminFieldsPreview, 250));
        $newField.find('.bcek-field-type-selector').on('change', handleFieldTypeChange).trigger('change'); 
        fieldNextIndex++; 
        if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview();
    });

    // Lógica para o botão "Remover" de um campo.
    $('#bcek_fields_container').on('click', '.bcek-remove-field-button', function() {
        var $fieldItem = $(this).closest('.bcek-field-item'); 
        var fieldId = $fieldItem.data('field-id'); 
        if (fieldId && fieldId > 0) { 
            if (!removedFieldIds.includes(fieldId)) removedFieldIds.push(fieldId);
            $('#bcek_removed_fields_input').val(removedFieldIds.join(',')); 
        }
        $fieldItem.remove(); 
        if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview();
    });
    
    // Listener para o seletor de tipo de campo.
    $('#bcek_fields_container').on('change', '.bcek-field-type-selector', handleFieldTypeChange);
    // Garante que a visibilidade dos campos seja definida corretamente na carga da página para campos existentes.
    $('.bcek-field-item').each(function() { 
        $(this).find('.bcek-field-type-selector').trigger('change');
    });

    /**
     * Manipula a mudança do tipo de campo (Texto/Imagem) mostrando/escondendo as opções relevantes.
     */
    function handleFieldTypeChange() {
        var $selector = $(this);
        var selectedType = $selector.val();
        var $fieldItem = $selector.closest('.bcek-field-item');
        $fieldItem.data('field-type', selectedType); 
        
        $fieldItem.find('.bcek-text-fields').toggle(selectedType === 'text');
        $fieldItem.find('.bcek-image-fields').toggle(selectedType === 'image');
        
        if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview();
    }

    // CORREÇÃO: Garante que os IDs dos campos removidos sejam enviados ao submeter o formulário do admin.
    // Usar um seletor mais específico ('form#post') para o formulário do WordPress.
    $('form#post').on('submit', function(event) {
        // Verifica se o nosso campo nonce está presente para garantir que é o formulário correto.
        if ($(this).find('input[name="bcek_save_template_nonce"]').length > 0) {
            $('#bcek_removed_fields_input').val(removedFieldIds.join(','));
        }
    });

    // --- LÓGICA DO PREVIEW EM TEMPO REAL NO ADMIN ---
    const $adminPreviewArea = $('#bcek-admin-preview-area');
    if ($adminPreviewArea.length) { 
        const $adminPreviewContainer = $('#bcek-admin-canvas-container');
        const $adminBaseImage = $('#bcek_admin_base_image_preview_img');
        const $adminCanvas = $('#bcek_admin_fields_preview_canvas');
        let adminCtx = null;
        let adminBaseImageNaturalWidth = 0;
        let adminBaseImageNaturalHeight = 0;

        if ($adminCanvas.length) adminCtx = $adminCanvas[0].getContext('2d');

        const wrapTextForAdminCanvas = function(context, text, maxWidth, fontSize, fontFamily, fontWeight) {
            if (text === undefined || text === null) text = ''; 
            const paragraphs = String(text).replace(/\r\n/g, '\n').split('\n');
            const finalLines = [];
            context.font = `${fontWeight} ${fontSize}px ${fontFamily}`;
            paragraphs.forEach(paragraph => {
                if (paragraph === '') {
                    finalLines.push('');
                    return;
                }
                let words = paragraph.split(' ');
                let currentLine = '';
                if (words.length > 0) currentLine = words.shift() || '';
                
                for (const word of words) {
                    let testLine = currentLine + (currentLine === '' ? '' : ' ') + word;
                    if (context.measureText(testLine).width > maxWidth && currentLine !== '') {
                        finalLines.push(currentLine);
                        currentLine = word;
                    } else {
                        currentLine = testLine;
                    }
                }
                finalLines.push(currentLine);
            });
            return finalLines;
        };
        
        // CORREÇÃO: Lógica de setup do canvas simplificada para responsividade
        window.setupAdminCanvas = function() { 
            if (!$adminBaseImage.length || !$adminBaseImage.attr('src') || $adminBaseImage.attr('src') === '') {
                if ($adminCanvas.length) $adminCanvas.hide();
                adminBaseImageNaturalWidth = 0; adminBaseImageNaturalHeight = 0;
                if (adminCtx && $adminCanvas.length) adminCtx.clearRect(0, 0, $adminCanvas[0].width, $adminCanvas[0].height);
                if ($adminBaseImage.length) $adminBaseImage.attr('src', '').hide();
                return;
            }
            if ($adminCanvas.length) $adminCanvas.show();
            if ($adminBaseImage.length) $adminBaseImage.show();

            adminBaseImageNaturalWidth = $adminBaseImage[0].naturalWidth;
            adminBaseImageNaturalHeight = $adminBaseImage[0].naturalHeight;
            
            // Define a resolução do canvas para corresponder à da imagem original
            if ($adminCanvas.length) {
                $adminCanvas[0].width = adminBaseImageNaturalWidth;
                $adminCanvas[0].height = adminBaseImageNaturalHeight;
            }
            
            // Deixa o CSS controlar o tamanho da exibição para manter a proporção
            $adminBaseImage.css({ width: '100%', height: 'auto' });
            $adminCanvas.css({ width: '100%', height: 'auto' });
            
            if (typeof window.drawAdminFieldsPreview === 'function') {
                window.drawAdminFieldsPreview();
            }
        };

        window.drawAdminFieldsPreview = function() { 
            if (!adminCtx) return; 
            if ($adminCanvas.length) adminCtx.clearRect(0, 0, $adminCanvas[0].width, $adminCanvas[0].height);
            if (adminBaseImageNaturalWidth === 0) return;

            $('#bcek_fields_container .bcek-field-item').each(function() {
                const $fieldItem = $(this);
                const fieldType = $fieldItem.find('.bcek-field-type-selector').val(); 
                const posX = parseFloat($fieldItem.find('input[name*="[pos_x]"]').val()) || 0;
                const posY = parseFloat($fieldItem.find('input[name*="[pos_y]"]').val()) || 0;
                const blockWidth = parseFloat($fieldItem.find('input[name*="[width]"]').val()) || 100;
                const blockHeight = parseFloat($fieldItem.find('input[name*="[height]"]').val()) || 50;
                
                if (fieldType === 'image') {
                    const shape = $fieldItem.find('select[name*="[container_shape]"]').val();
                    adminCtx.strokeStyle = '#007cba'; adminCtx.lineWidth = 1; adminCtx.setLineDash([6, 3]);
                    const centerX = posX + blockWidth / 2; const centerY = posY + blockHeight / 2;
                    if (shape === 'circle') { adminCtx.beginPath(); adminCtx.arc(centerX, centerY, Math.min(blockWidth, blockHeight) / 2 - adminCtx.lineWidth, 0, 2 * Math.PI); adminCtx.stroke(); } 
                    else { adminCtx.strokeRect(posX, posY, blockWidth, blockHeight); }
                    adminCtx.setLineDash([]); adminCtx.font = '12px sans-serif'; adminCtx.fillStyle = '#007cba';
                    adminCtx.textAlign = 'center'; adminCtx.textBaseline = 'middle';
                    adminCtx.fillText('IMG', centerX, centerY);
                } else if (fieldType === 'text') {
                    const fontColor = $fieldItem.find('input[name*="[font_color]"]').val() || '#000000';
                    const textToRender = $fieldItem.find('textarea[name*="[default_text]"]').val(); 
                    const fontSize = parseFloat($fieldItem.find('input[name*="[font_size]"]').val()) || 16;
                    const fontFamilyVal = $fieldItem.find('select[name*="[font_family]"]').val() || 'Montserrat-Regular';
                    const alignment = $fieldItem.find('select[name*="[alignment]"]').val() || 'left';
                    const lineHeightMultiplier = parseFloat($fieldItem.find('input[name*="[line_height_multiplier]"]').val()) || 1.3;
                    const textPadding = 3; 
                    const effectiveBlockWidth = blockWidth - (textPadding * 2);
                    const fontFamilyParts = fontFamilyVal.split('-');
                    const fontFamily = fontFamilyParts[0] || 'Montserrat';
                    let fontWeight = '400'; 
                    if (fontFamilyParts.length > 1) {
                        const weightPart = fontFamilyParts[1].toLowerCase();
                        if (weightPart === 'bold') fontWeight = '700';
                        else if (weightPart === 'black') fontWeight = '900';
                    }

                    adminCtx.strokeStyle = fontColor; adminCtx.lineWidth = 1; adminCtx.setLineDash([4, 2]); 
                    adminCtx.strokeRect(posX, posY, blockWidth, blockHeight);
                    adminCtx.setLineDash([]); 

                    if (textToRender !== undefined) { 
                        adminCtx.fillStyle = fontColor;
                        adminCtx.font = `${fontWeight} ${fontSize}px ${fontFamily}`; 
                        adminCtx.textBaseline = 'alphabetic';

                        const lines = wrapTextForAdminCanvas(adminCtx, textToRender, effectiveBlockWidth, fontSize, fontFamily, fontWeight);
                        const actualLineHeight = fontSize * lineHeightMultiplier; 
                        let currentTextY = posY + fontSize + textPadding;

                        for (const line of lines) {
                            if ( (currentTextY - posY - fontSize + textPadding + actualLineHeight ) > blockHeight ) break; 
                            
                            if (line === '') { 
                                currentTextY += actualLineHeight;
                                continue;
                            }
                            
                            const isLastLineOfParagraph = (lines.indexOf(line) === lines.length - 1) || (lines[lines.indexOf(line) + 1] === '');
                            
                            if (alignment === 'justify' && !isLastLineOfParagraph && line.trim() !== '' && line.includes(' ')) {
                                adminCtx.textAlign = 'left';
                                let wordsInLine = line.split(' '); 
                                wordsInLine = wordsInLine.filter(w => w.length > 0 || wordsInLine.length === 1); 
                                if (wordsInLine.length === 0) { currentTextY += actualLineHeight; continue; }
                                let lineWithoutSpaces = wordsInLine.join('');
                                let totalWordsWidth = adminCtx.measureText(lineWithoutSpaces).width;
                                let totalSpaces = wordsInLine.length - 1;
                                if (totalSpaces > 0) {
                                    let spacePerGap = (effectiveBlockWidth - totalWordsWidth) / totalSpaces;
                                    let singleSpaceApproxWidth = adminCtx.measureText(" ").width;
                                    if (spacePerGap < 0 || spacePerGap > (singleSpaceApproxWidth * 4) ) {
                                         adminCtx.textAlign = 'left';
                                         adminCtx.fillText(line, posX + textPadding, currentTextY);
                                    } else {
                                        let currentX = posX + textPadding;
                                        wordsInLine.forEach((word, index) => {
                                            adminCtx.fillText(word, currentX, currentTextY);
                                            currentX += adminCtx.measureText(word).width;
                                            if (index < totalSpaces) currentX += spacePerGap;
                                        });
                                    }
                                } else { 
                                    adminCtx.textAlign = 'left';
                                    adminCtx.fillText(line, posX + textPadding, currentTextY); 
                                }
                            } else { 
                                let finalAlignment = (alignment === 'justify') ? 'left' : alignment;
                                adminCtx.textAlign = finalAlignment;
                                let textX;
                                if (finalAlignment === 'left') textX = posX + textPadding;
                                else if (finalAlignment === 'center') textX = posX + blockWidth / 2;
                                else textX = posX + blockWidth - textPadding;
                                adminCtx.fillText(line, textX, currentTextY);
                            }
                            currentTextY += actualLineHeight;
                        }
                    }
                }
            });
        };
        
        // CORREÇÃO: Listener de 'load' para a tag de imagem de preview, anexado uma única vez
        $adminBaseImage.off('load.bcekAdminPreview').on('load.bcekAdminPreview', function() {
            window.setupAdminCanvas();
        });

        // CORREÇÃO: Listener de eventos para TODOS os inputs que afetam o preview
        $('#bcek-admin-form-fields').on('input change', '.bcek-preview-input', debounce(window.drawAdminFieldsPreview, 300));
        
        setTimeout(function(){
            if ($adminBaseImage.length > 0 && $adminBaseImage[0].complete && $adminBaseImage[0].naturalWidth > 0) {
                 $adminBaseImage.trigger('load.bcekAdminPreview');
            } else {
                window.setupAdminCanvas();
            }
        }, 50);

        var resizeTimerAdmin;
        $(window).on('resize', function() { clearTimeout(resizeTimerAdmin); resizeTimerAdmin = setTimeout(function() { if ($adminPreviewArea.is(':visible')) window.setupAdminCanvas(); }, 250); });
    }

    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() { timeout = null; if (!immediate) func.apply(context, args); };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
});
