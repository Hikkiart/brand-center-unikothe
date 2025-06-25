jQuery(document).ready(function($) {
    'use strict';

    // --- Lógica de ordenação dos campos (SortableJS) ---
    if (typeof Sortable !== 'undefined') {
        var container = document.getElementById('bcek_fields_container');
        var hiddenOrderInput = document.getElementById('bcek_fields_order');
        if (container && hiddenOrderInput) {
            new Sortable(container, {
                animation: 150, handle: '.bcek-field-item', draggable: '.bcek-field-item',
                onSort: function () {
                    var order = [];
                    container.querySelectorAll('.bcek-field-item').forEach(function (item) {
                        var id = item.getAttribute('data-field-id');
                        if (id) order.push(id);
                    });
                    hiddenOrderInput.value = order.join(',');
                }
            });
            var initialOrder = [];
            container.querySelectorAll('.bcek-field-item').forEach(function (item) {
                var id = item.getAttribute('data-field-id');
                if (id) initialOrder.push(id);
            });
            hiddenOrderInput.value = initialOrder.join(',');
        }
    }

    // --- Lógica de inicialização do Color Picker do WordPress ---
    function initColorPickers(context) {
        $('.bcek-color-picker', context).wpColorPicker({
            change: debounce(function() { if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview(); }, 300),
            clear: debounce(function() { if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview(); }, 300)
        });
    }
    initColorPickers(document);

    // --- Lógica de Upload da Imagem Base ---
    var frame;
    $('#bcek_base_image_button').on('click', function(e) {
        e.preventDefault();
        if (frame) { frame.open(); return; }
        frame = wp.media({ title: 'Selecionar Imagem Base', button: { text: 'Usar esta imagem' }, multiple: false });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#bcek_base_image_id').val(attachment.id); $('#bcek_base_image_url').val(attachment.url);
            // Dispara o evento 'load' na imagem para re-inicializar os previews
            $('#bcek_admin_base_image_preview_img').attr('src', attachment.url).show().trigger('load.bcekAdminPreview');
            $('#bcek_remove_base_image_button').show();
        });
        frame.open();
    });
    $('#bcek_remove_base_image_button').on('click', function(e) {
        e.preventDefault();
        $('#bcek_base_image_id').val(''); $('#bcek_base_image_url').val('');
        $('#bcek_admin_base_image_preview_img').attr('src', '').hide();
        if (typeof window.setupAdminCanvas === 'function') window.setupAdminCanvas();
        $(this).hide();
    });

    // --- Lógica de Gestão Dinâmica dos Campos ---
    var removedFieldIds = [];
    $('#bcek_add_field_button').on('click', function() {
        var fieldNextIndex = Date.now(); // Usa timestamp para um índice único
        var fieldTemplateHtml = $('#bcek-field-template').html();
        var newFieldHtml = fieldTemplateHtml.replace(/{{FIELD_INDEX}}/g, fieldNextIndex).replace(/{{FIELD_INDEX_DISPLAY}}/g, $('#bcek_fields_container .bcek-field-item').length + 1);
        var $newField = $(newFieldHtml);
        $('#bcek_fields_container').append($newField);
        initColorPickers($newField);
        $newField.find('.bcek-field-type-selector').on('change', handleFieldTypeChange).trigger('change');
        
        // Atualiza os previews
        if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview();
        if (typeof window.createOrUpdateFieldHandles === 'function') window.createOrUpdateFieldHandles();
    });

    $('#bcek_fields_container').on('click', '.bcek-remove-field-button', function() {
        var $fieldItem = $(this).closest('.bcek-field-item');
        var fieldId = $fieldItem.data('field-id');
        var fieldIndex = $fieldItem.data('index');

        if (fieldId && fieldId > 0) {
            if (!removedFieldIds.includes(fieldId)) removedFieldIds.push(fieldId);
            $('#bcek_removed_fields_input').val(removedFieldIds.join(','));
        }
        $fieldItem.remove();
        
        $('#bcek-admin-interactive-overlay .bcek-interactive-handle[data-field-index="' + fieldIndex + '"]').remove();
        if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview();
    });

    $('#bcek_fields_container').on('change', '.bcek-field-type-selector', handleFieldTypeChange);
    $('.bcek-field-item').each(function() {
        $(this).find('.bcek-field-type-selector').trigger('change');
    });

    function handleFieldTypeChange() {
        var $selector = $(this);
        var selectedType = $selector.val();
        var $fieldItem = $selector.closest('.bcek-field-item');
        $fieldItem.find('.bcek-text-fields').toggle(selectedType === 'text');
        $fieldItem.find('.bcek-image-fields').toggle(selectedType === 'image');
        
        var fieldIndex = $fieldItem.data('index');
        $('#bcek-admin-interactive-overlay .bcek-interactive-handle[data-field-index="' + fieldIndex + '"]')
            .attr('data-field-type', selectedType);

        if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview();
    }

    $('form:has(input[name="bcek_save_template_nonce"])').on('submit', function(event) {
        $('#bcek_removed_fields_input').val(removedFieldIds.join(','));
    });

    // --- LÓGICA DO PREVIEW E DRAG-AND-DROP ---
    const $adminPreviewArea = $('#bcek-admin-preview-area');
    if ($adminPreviewArea.length && typeof interact !== 'undefined') {
        const $adminPreviewContainer = $('#bcek-admin-canvas-container');
        const $adminBaseImage = $('#bcek_admin_base_image_preview_img');
        const $adminCanvas = $('#bcek_admin_fields_preview_canvas');
        const $interactiveOverlay = $('#bcek-admin-interactive-overlay');
        let adminCtx = null;
        let adminBaseImageNaturalWidth = 0;
        let adminBaseImageNaturalHeight = 0;
        let displayScale = 1;

        if ($adminCanvas.length) adminCtx = $adminCanvas[0].getContext('2d');
        
        const wrapTextForAdminCanvas = function(context, text, maxWidth, fontSize, fontFamily, fontWeight) {
           if (!text) return [];
           const paragraphs = String(text).split('\n');
           const finalLines = [];
           context.font = `${fontWeight} ${fontSize}px ${fontFamily}`;
           paragraphs.forEach(p => {
               if(p === '') { finalLines.push(''); return; }
               let words = p.split(' ');
               let currentLine = words.shift() || '';
               for (const word of words) {
                   let testLine = currentLine ? `${currentLine} ${word}` : word;
                   if (context.measureText(testLine).width > maxWidth && currentLine !== '') {
                       finalLines.push(currentLine);
                       currentLine = word;
                   } else { currentLine = testLine; }
               }
               finalLines.push(currentLine);
           });
           return finalLines;
        };

        window.setupAdminCanvas = function() {
            if (!$adminBaseImage.length || !$adminBaseImage.attr('src')) {
                if ($adminCanvas.length) $adminCanvas.hide();
                $interactiveOverlay.hide();
                return;
            }
            if ($adminCanvas.length) $adminCanvas.show();
            $interactiveOverlay.show();

            adminBaseImageNaturalWidth = $adminBaseImage[0].naturalWidth;
            adminBaseImageNaturalHeight = $adminBaseImage[0].naturalHeight;
            
            const containerWidth = $adminPreviewContainer.width();
            displayScale = adminBaseImageNaturalWidth > 0 ? containerWidth / adminBaseImageNaturalWidth : 1;
            
            if ($adminCanvas.length) {
                $adminCanvas[0].width = adminBaseImageNaturalWidth;
                $adminCanvas[0].height = adminBaseImageNaturalHeight;
            }
            
            const cssWidth = adminBaseImageNaturalWidth * displayScale;
            const cssHeight = adminBaseImageNaturalHeight * displayScale;

            $adminBaseImage.css({ width: cssWidth, height: cssHeight });
            $adminCanvas.css({ width: cssWidth, height: cssHeight });
            $interactiveOverlay.css({ width: cssWidth, height: cssHeight });

            if (typeof window.drawAdminFieldsPreview === 'function') window.drawAdminFieldsPreview();
            if (typeof window.createOrUpdateFieldHandles === 'function') window.createOrUpdateFieldHandles();
        };

        window.drawAdminFieldsPreview = function() {
            if (!adminCtx || adminBaseImageNaturalWidth === 0) return;
            adminCtx.clearRect(0, 0, adminBaseImageNaturalWidth, adminBaseImageNaturalHeight);
            
            $('.bcek-field-item').each(function() {
                const $fieldItem = $(this);
                if ($fieldItem.find('.bcek-field-type-selector').val() !== 'text') return;

                const posX = parseFloat($fieldItem.find('input[name*="[pos_x]"]').val()) || 0;
                const posY = parseFloat($fieldItem.find('input[name*="[pos_y]"]').val()) || 0;
                const blockWidth = parseFloat($fieldItem.find('input[name*="[width]"]').val()) || 100;
                const blockHeight = parseFloat($fieldItem.find('input[name*="[height]"]').val()) || 50;
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
                    if (weightPart === 'bold') fontWeight = '700'; else if (weightPart === 'black') fontWeight = '900';
                }
                
                adminCtx.fillStyle = fontColor;
                adminCtx.font = `${fontWeight} ${fontSize}px ${fontFamily}`;
                adminCtx.textBaseline = 'top';
                const lines = wrapTextForAdminCanvas(adminCtx, textToRender, effectiveBlockWidth, fontSize, fontFamily, fontWeight);
                const actualLineHeight = fontSize * lineHeightMultiplier;
                let currentTextY = posY + textPadding;

                for (const line of lines) {
                    if (currentTextY + actualLineHeight - posY > blockHeight) break;
                    
                    let textX;
                    const finalAlignment = (alignment === 'justify') ? 'left' : alignment;
                    adminCtx.textAlign = finalAlignment;
                    if (finalAlignment === 'left') textX = posX + textPadding;
                    else if (finalAlignment === 'center') textX = posX + blockWidth / 2;
                    else textX = posX + blockWidth - textPadding;
                    
                    adminCtx.fillText(line, textX, currentTextY);
                    currentTextY += actualLineHeight;
                }
            });
        };

        window.createOrUpdateFieldHandles = function() {
            if (displayScale === 0) return;
            $('.bcek-field-item').each(function() {
                const $fieldItem = $(this);
                const index = $fieldItem.data('index');
                let $handle = $interactiveOverlay.find('.bcek-interactive-handle[data-field-index="' + index + '"]');
                if ($handle.length === 0) {
                    $handle = $('<div class="bcek-interactive-handle"></div>').attr('data-field-index', index).appendTo($interactiveOverlay);
                }
                const fieldType = $fieldItem.find('.bcek-field-type-selector').val();
                $handle.attr('data-field-type', fieldType);

                const posX = parseFloat($fieldItem.find('input[name*="[pos_x]"]').val()) || 0;
                const posY = parseFloat($fieldItem.find('input[name*="[pos_y]"]').val()) || 0;
                const blockWidth = parseFloat($fieldItem.find('input[name*="[width]"]').val()) || 100;
                const blockHeight = parseFloat($fieldItem.find('input[name*="[height]"]').val()) || 50;

                $handle.css({
                    left: (posX * displayScale) + 'px',
                    top: (posY * displayScale) + 'px',
                    width: (blockWidth * displayScale) + 'px',
                    height: (blockHeight * displayScale) + 'px',
                }).attr('data-x', posX).attr('data-y', posY);
            });
        };

        $adminBaseImage.off('load.bcekAdminPreview').on('load.bcekAdminPreview', window.setupAdminCanvas);
        $('#bcek-admin-form-fields').on('input change', '.bcek-preview-input', debounce(function() {
            window.drawAdminFieldsPreview();
            window.createOrUpdateFieldHandles();
        }, 300));

        setTimeout(function(){
            if ($adminBaseImage.length > 0 && $adminBaseImage[0].complete && $adminBaseImage[0].naturalWidth > 0) {
                 $adminBaseImage.trigger('load.bcekAdminPreview');
            }
        }, 150);
        $(window).on('resize', debounce(window.setupAdminCanvas, 250));

        interact('.bcek-interactive-handle')
            .draggable({
                listeners: {
                    move(event) {
                        const target = event.target;
                        let x = (parseFloat(target.getAttribute('data-x')) || 0) + (event.dx / displayScale);
                        let y = (parseFloat(target.getAttribute('data-y')) || 0) + (event.dy / displayScale);
                        const fieldIndex = $(target).data('field-index');
                        const $fieldItem = $('.bcek-field-item[data-index="' + fieldIndex + '"]');
                        $fieldItem.find('input[name*="[pos_x]"]').val(Math.round(x));
                        $fieldItem.find('input[name*="[pos_y]"]').val(Math.round(y)).trigger('input');
                        target.setAttribute('data-x', x);
                        target.setAttribute('data-y', y);
                    }
                }
            })
            .resizable({
                edges: { left: true, right: true, bottom: true, top: true },
                listeners: {
                    move: function (event) {
                        const target = event.target;
                        let x = parseFloat(target.getAttribute('data-x')) || 0;
                        let y = parseFloat(target.getAttribute('data-y')) || 0;

                        target.style.width = event.rect.width + 'px';
                        target.style.height = event.rect.height + 'px';
                        x += event.deltaRect.left / displayScale;
                        y += event.deltaRect.top / displayScale;
                        target.setAttribute('data-x', x);
                        target.setAttribute('data-y', y);
                        
                        const fieldIndex = $(target).data('field-index');
                        const $fieldItem = $('.bcek-field-item[data-index="' + fieldIndex + '"]');
                        $fieldItem.find('input[name*="[width]"]').val(Math.round(event.rect.width / displayScale));
                        $fieldItem.find('input[name*="[height]"]').val(Math.round(event.rect.height / displayScale));
                        $fieldItem.find('input[name*="[pos_x]"]').val(Math.round(x));
                        $fieldItem.find('input[name*="[pos_y]"]').val(Math.round(y)).trigger('input');
                    }
                }
            })
            .on('tap', function(event) {
                const $target = $(event.currentTarget);
                const fieldIndex = $target.data('field-index');
                const $fieldItem = $('.bcek-field-item[data-index="' + fieldIndex + '"]');

                $('.bcek-interactive-handle').removeClass('selected');
                $('.bcek-field-item').removeClass('is-selected');
                $target.addClass('selected');
                $fieldItem.addClass('is-selected');

                $fieldItem[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
    }

    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() { timeout = null; func.apply(context, args); };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
