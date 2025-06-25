// assets/js/bcek-editor-script.js - VERSÃO FINAL
jQuery(document).ready(function ($) {
  "use strict";

  if (typeof bcek_data === "undefined") return;
  const { template: templateData, fields: fieldsData = [], nonce: generateNonce } = bcek_data;
  const { ajax_url, fonts_url } = bcek_editor_ajax || {};
  if (!templateData || !ajax_url || !generateNonce) { console.error("BCEK: Dados essenciais em falta."); return; }

  const $canvas = $("#bcek-text-overlay-canvas");
  const visibleCtx = $canvas[0] ? $canvas[0].getContext("2d") : null;
  if (!visibleCtx) return;

  const $baseImage = $("#bcek-base-image-preview");
  let baseImageWidth = 0, baseImageHeight = 0;
  let userUploadedImages = {};
  let cropperInstance = null;
  let currentCropperFieldId = null;
  const $cropperModal = $("#bcek-cropper-modal"), $imageToCrop = $("#bcek-image-to-crop");

  const fontPromises = fieldsData.filter(f => f && (f.field_type === 'text' || !f.field_type) && f.font_family)
    .map(f => f.font_family)
    .filter((value, index, self) => self.indexOf(value) === index)
    .map(fontFamilyFile => {
        let fontWeight = "400", fontName = "Montserrat";
        if (fontFamilyFile.includes("Bold")) fontWeight = "700";
        if (fontFamilyFile.includes("Black")) fontWeight = "900";
        const font = new FontFace(fontName, `url(${fonts_url}${fontFamilyFile}.ttf)`, { weight: fontWeight });
        return font.load();
    });

  Promise.all(fontPromises).then(loadedFonts => {
      if (document.fonts) loadedFonts.forEach(font => document.fonts.add(font));
      initializeEditor();
  }).catch(err => { console.error("BCEK: Font loading error:", err); initializeEditor(); });

  function initializeEditor() {
    if ($baseImage[0] && $baseImage[0].complete) {
        setupCanvas();
    } else {
        $baseImage.on("load.bcek", setupCanvas);
    }
    
    $("#bcek-editor-wrapper").on("input change", ".bcek-dynamic-text-input, .bcek-dynamic-fontsize-input", debounce(drawPreview, 200));
    $("#bcek-editor-wrapper").on("change", ".bcek-dynamic-image-input", handleImageUpload);
    $("#bcek-confirm-crop-btn").on("click", confirmCrop);
    $("#bcek-cancel-crop-btn").on("click", () => $cropperModal.hide());
    $("#bcek-editor-wrapper").on("click", ".bcek-generate-btn", function() { handleGenerateImage($(this).data('format')); });
    $(window).on("resize", debounce(setupCanvas, 250));
  }

  function setupCanvas() {
    if ($baseImage.length && $baseImage[0] && $baseImage[0].naturalWidth > 0) {
        baseImageWidth = $baseImage[0].naturalWidth;
        baseImageHeight = $baseImage[0].naturalHeight;
    } else {
        let maxW = 0, maxH = 0;
        fieldsData.forEach(f => {
            if (f) {
                const right = (parseInt(f.pos_x) || 0) + (parseInt(f.width) || 0);
                const bottom = (parseInt(f.pos_y) || 0) + (parseInt(f.height) || 0);
                if (right > maxW) maxW = right;
                if (bottom > maxH) maxH = bottom;
            }
        });
        baseImageWidth = maxW > 0 ? maxW + 20 : 1200;
        baseImageHeight = maxH > 0 ? maxH + 20 : 800;
    }
    
    const containerWidth = $("#bcek-canvas-container").width();
    const displayScale = baseImageWidth > 0 ? Math.min(1, containerWidth / baseImageWidth) : 1;
    $canvas[0].width = baseImageWidth;
    $canvas[0].height = baseImageHeight;
    const cssSize = { width: baseImageWidth * displayScale, height: baseImageHeight * displayScale };
    $canvas.css(cssSize);
    $baseImage.css(cssSize);
    drawPreview();
  }
  
  function wrapText(ctx, text, maxWidth, fontSize, fontFamily, fontWeight) {
    if (!text) return [];
    ctx.font = `${fontWeight} ${fontSize}px ${fontFamily}`;
    return String(text).split('\n').flatMap(p => {
        if (p.trim() === '') return [''];
        let words = p.split(' '), currentLine = words.shift() || '';
        const lines = [];
        for (const word of words) {
            if (ctx.measureText(`${currentLine} ${word}`).width > maxWidth) {
                lines.push(currentLine);
                currentLine = word;
            } else {
                currentLine += ` ${word}`;
            }
        }
        lines.push(currentLine);
        return lines;
    });
  }

  function drawPreview() {
    if (!visibleCtx || baseImageWidth === 0) return;
    visibleCtx.clearRect(0, 0, baseImageWidth, baseImageHeight);
    
    if ($baseImage[0] && $baseImage[0].complete) {
        visibleCtx.drawImage($baseImage[0], 0, 0, baseImageWidth, baseImageHeight);
    }
    
    fieldsData.forEach(field => {
        if (!field) return;
        if (field.field_type === 'image') {
            const img = userUploadedImages[field.field_id];
            if (img && img.complete) visibleCtx.drawImage(img, parseInt(field.pos_x), parseInt(field.pos_y));
        } else {
            const text = $(`#bcek_field_text_${field.field_id}`).val() || field.default_text || "";
            if (!text.trim()) return;
            
            const fontSize = parseFloat($(`#bcek_field_fontsize_${field.field_id}`).val()) || parseFloat(field.font_size);
            const fontWeight = (field.font_family || "").includes("Bold") ? "700" : ((field.font_family || "").includes("Black") ? "900" : "400");
            visibleCtx.fillStyle = field.font_color;
            visibleCtx.font = `${fontWeight} ${fontSize}px "Montserrat"`;
            visibleCtx.textBaseline = 'top';

            const x = parseInt(field.pos_x), y = parseInt(field.pos_y);
            const blockW = parseInt(field.width), blockH = parseInt(field.height);
            const pad = 3;
            const lines = wrapText(visibleCtx, text, blockW - (pad * 2), fontSize, "Montserrat", fontWeight);
            
            let currentY = y + pad;
            const lineHeight = Math.round(fontSize * (parseFloat(field.line_height_multiplier) || 1.3));

            lines.forEach(line => {
                if ((currentY + lineHeight - y) > blockH + pad) return;
                const align = field.alignment || 'left';
                visibleCtx.textAlign = align;
                let textX = x + pad;
                if (align === 'center') textX = x + blockW / 2;
                else if (align === 'right') textX = x + blockW - pad;
                
                visibleCtx.fillText(line, textX, currentY);
                currentY += lineHeight;
            });
        }
    });
  }

  function handleImageUpload(e) {
    currentCropperFieldId = $(e.target).data("field-id");
    const file = e.target.files[0];
    if (!file || !currentCropperFieldId) return;
    const reader = new FileReader();
    reader.onload = function(event) {
      $imageToCrop.attr("src", event.target.result);
      $cropperModal.show();
      const field = fieldsData.find(f => f.field_id == currentCropperFieldId);
      const aspectRatio = (field && field.width > 0 && field.height > 0) ? parseInt(field.width) / parseInt(field.height) : NaN;
      if (cropperInstance) cropperInstance.destroy();
      cropperInstance = new Cropper($imageToCrop[0], { aspectRatio, viewMode: 1 });
    };
    reader.readAsDataURL(file);
    e.target.value = '';
  }

  function confirmCrop() {
    if (!cropperInstance || !currentCropperFieldId) return;
    const field = fieldsData.find(f => f.field_id == currentCropperFieldId);
    const canvas = cropperInstance.getCroppedCanvas({
        width: field ? parseInt(field.width) : 500,
        height: field ? parseInt(field.height) : 500,
    });
    const img = new Image();
    img.onload = () => { userUploadedImages[currentCropperFieldId] = img; drawPreview(); $cropperModal.hide(); };
    img.src = canvas.toDataURL('image/png');
  }
  
  function handleGenerateImage(format) {
    const userInputs = {};
    fieldsData.forEach(field => {
        if(!field) return;
        const fieldId = field.field_id;
        if ((!field.field_type || field.field_type === 'text')) {
            userInputs[fieldId] = { type: 'text', text: $(`#bcek_field_text_${fieldId}`).val(), fontSize: $(`#bcek_field_fontsize_${fieldId}`).val() };
        } else if (field.field_type === 'image') {
            userInputs[fieldId] = { type: 'image', imageDataUrl: (userUploadedImages[fieldId] ? userUploadedImages[fieldId].src : null) };
        }
    });
    
    const $buttons = $(".bcek-generate-btn"), $loader = $("#bcek-loader"), $resultArea = $("#bcek-result-area");
    $buttons.prop("disabled", true); $loader.show(); $resultArea.empty();
    
    $.ajax({
      url: ajax_url, type: "POST",
      data: { action: "bcek_generate_image", nonce: generateNonce, template_id: templateData.template_id, user_inputs: userInputs, user_filename: $("#bcek_filename").val(), format },
      success: res => $resultArea.html(res.success ? `<p style="color:green;">Sucesso!</p><a href="${res.data.url}" download="${res.data.filename}">Baixar</a>...` : `<p style="color:red;">Erro: ${res.data.message || '?'}</p>`),
      error: () => $resultArea.html(`<p style="color:red;">Erro de comunicação.</p>`),
      complete: () => { $buttons.prop("disabled", false); $loader.hide(); }
    });
  }
  
  function debounce(func, wait) {
    let timeout;
    return function(...args) { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), wait); };
  }
});
