// assets/js/bcek-editor-script.js

jQuery(document).ready(function ($) {
  "use strict"; // Ativa o modo estrito do JavaScript.
  // Verifica se os dados essenciais passados do PHP (via wp_localize_script) estão disponíveis.

  if (typeof bcek_data === "undefined" || !bcek_data.template) {
    console.error(
      "BCEK FATAL JS: bcek_data (contendo template) não está definido! O editor não pode inicializar corretamente.",
    ); // Poderia exibir uma mensagem para o utilizador aqui, mas o console.error é para depuração.

    return;
  }

  if (typeof bcek_data.fields === "undefined") {
    console.warn(
      "BCEK WARN JS: bcek_data.fields não está definido, assumindo array vazio.",
    );
  }

  if (
    typeof bcek_editor_ajax === "undefined" ||
    !bcek_editor_ajax.ajax_url ||
    !bcek_data.nonce
  ) {
    // Nonce é crucial para a segurança da chamada AJAX de geração.

    // ajax_url é necessário para a chamada AJAX.

    // fonts_url é para o carregamento de fontes.

    console.error(
      "BCEK FATAL JS: bcek_editor_ajax.ajax_url ou bcek_data.nonce não está definido! A geração de imagem e/ou o carregamento de fontes podem falhar.",
    );
  } // Constantes com os dados recebidos do PHP.

  const templateData = bcek_data.template;
  const fieldsData = bcek_data.fields || []; // Garante que fieldsData seja um array.

  const ajaxUrl = bcek_editor_ajax.ajax_url;
  const generateNonce = bcek_data.nonce; // Seletores jQuery para os principais elementos da interface.
  const $editorWrapper = $("#bcek-editor-wrapper");

  if (!$editorWrapper.length) {
    console.error(
      "BCEK FATAL JS: Elemento wrapper principal #bcek-editor-wrapper não encontrado no DOM.",
    );

    return;
  }

  const $canvasContainer = $("#bcek-canvas-container");

  const $baseImagePreviewImgTag = $("#bcek-base-image-preview"); // Elemento <img> para a imagem base.

  const $canvas = $("#bcek-text-overlay-canvas"); // Elemento <canvas> para o overlay.
  const visibleCtx = $canvas[0] ? $canvas[0].getContext("2d") : null; // Contexto do canvas VISÍVEL

  if (!visibleCtx) {
    console.error(
      "BCEK FATAL JS: Não foi possível obter o contexto 2D do canvas visível. O preview não funcionará.",
    );
  } // Variáveis para armazenar as dimensões da imagem base e a escala de exibição.

  let baseImageWidth = 0;
  let baseImageHeight = 0;
  let displayScale = 1; // Objeto para guardar as imagens carregadas pelo utilizador para os campos de imagem (objetos Image)
  let userUploadedImages = {}; // Canvas fora do ecrã (off-screen) para double buffering
  let offScreenCanvas = null;

  let offCtx = null; // NOVO: Variáveis para o Cropper.js

  let cropperInstance = null;

  let currentCropperFieldId = null;

  const $cropperModal = $("#bcek-cropper-modal");

  const $imageToCrop = $("#bcek-image-to-crop"); // Tenta carregar as fontes Montserrat usando a API FontFace para melhor renderização no canvas.

  const fontFacesPromises = [];

  if (
    fieldsData &&
    fieldsData.length > 0 &&
    "fonts" in document &&
    bcek_editor_ajax &&
    bcek_editor_ajax.fonts_url
  ) {
    const uniqueFontFamilies = [
      ...new Set(
        fieldsData

          .filter(
            (f) =>
              isObject(f) &&
              (f.field_type === "text" || !f.field_type) &&
              f.font_family &&
              typeof f.font_family === "string" &&
              f.font_family.trim() !== "",
          )

          .map((f) => f.font_family),
      ),
    ];
    uniqueFontFamilies.forEach((fontFamilyFile) => {
      if (!fontFamilyFile) return;
      let fontWeight = "400";
      let fontName = "Montserrat";
      if (fontFamilyFile.includes("Bold")) fontWeight = "700";
      if (fontFamilyFile.includes("Black")) fontWeight = "900";

      const fontFile = fontFamilyFile + ".ttf";
      const font = new FontFace(
        fontName,
        `url(${bcek_editor_ajax.fonts_url}${fontFile})`,
        {
          weight: fontWeight,
          style: "normal",
        },
      );

      fontFacesPromises.push(font.load());
    });
  }

  Promise.all(fontFacesPromises)

    .then((loadedFonts) => {
      if ("fonts" in document) {
        loadedFonts.forEach((loadedFont) => document.fonts.add(loadedFont));
      }

      initializeEditor();
    })

    .catch((err) => {
      console.error(
        "BCEK ERROR JS: Ocorreu um erro ao tentar carregar as fontes Montserrat para o canvas:",
        err,
      );

      initializeEditor();
    }); /**

     * Inicializa os componentes principais do editor, configura listeners de eventos e desenha o preview inicial.

     */

  function initializeEditor() {
    if (
      $baseImagePreviewImgTag.length &&
      $baseImagePreviewImgTag[0] &&
      ($baseImagePreviewImgTag[0].complete ||
        $baseImagePreviewImgTag[0].readyState === 4 ||
        $baseImagePreviewImgTag[0].readyState === "complete") &&
      $baseImagePreviewImgTag[0].naturalWidth > 0
    ) {
      setupCanvas();
      drawPreview();
    } else if ($baseImagePreviewImgTag.length && $baseImagePreviewImgTag[0]) {
      $baseImagePreviewImgTag
        .on("load.bcek", function () {
          setupCanvas();
          drawPreview();
          $(this).off("load.bcek");
          $baseImagePreviewImgTag.data("bcek-loaded", true);
        })
        .on("error.bcek", function () {
          console.error(
            "BCEK ERROR JS: Erro ao carregar a imagem base do template (evento 'error').",
          );
          baseImageWidth = 800;
          baseImageHeight = 600;
          setupCanvasSizing();
          drawPreview();
          $(this).off("error.bcek");
        });

      if (
        $baseImagePreviewImgTag[0].complete &&
        $baseImagePreviewImgTag[0].naturalWidth > 0 &&
        !$baseImagePreviewImgTag.data("bcek-loaded")
      ) {
        setTimeout(function () {
          if (!$baseImagePreviewImgTag.data("bcek-loaded")) {
            setupCanvas();
            drawPreview();
          }
        }, 50);
      }
    } else if (visibleCtx) {
      let maxW = 0,
        maxH = 0;

      if (fieldsData)
        fieldsData.forEach((field) => {
          if (
            isObject(field) &&
            field.pos_x !== undefined &&
            field.width !== undefined
          ) {
            if (parseInt(field.pos_x) + parseInt(field.width) > maxW)
              maxW = parseInt(field.pos_x) + parseInt(field.width);
          }

          if (
            isObject(field) &&
            field.pos_y !== undefined &&
            field.height !== undefined
          ) {
            if (parseInt(field.pos_y) + parseInt(field.height) > maxH)
              maxH = parseInt(field.pos_y) + parseInt(field.height);
          }
        });

      baseImageWidth = maxW > 0 ? maxW + 20 : 800;
      baseImageHeight = maxH > 0 ? maxH + 20 : 600;

      setupCanvasSizing();
      drawPreview();
    } // Listeners para campos de texto e tamanho de fonte.

    $editorWrapper.on(
      "input change",
      ".bcek-dynamic-text-input, .bcek-dynamic-fontsize-input",
      debounce(drawPreview, 250),
    ); // MODIFICADO: Listener para campos de upload de imagem para abrir o modal de corte.

    $editorWrapper.on("change", ".bcek-dynamic-image-input", function (event) {
      currentCropperFieldId = $(this).data("field-id"); // Guarda o fieldId atual

      const file = event.target.files[0];

      const $input = $(this); // Guarda a referência do input de ficheiro

      if (file && currentCropperFieldId) {
        const reader = new FileReader();

        reader.onload = function (e) {
          // Prepara o Cropper.js

          $imageToCrop.attr("src", e.target.result); // Coloca a imagem no modal

          $cropperModal.show(); // Mostra o modal
          // Inicia o Cropper.js

          const fieldSettings = fieldsData.find(
            (f) => f.field_id == currentCropperFieldId,
          );

          const aspectRatio =
            fieldSettings && fieldSettings.width > 0 && fieldSettings.height > 0
              ? parseInt(fieldSettings.width) / parseInt(fieldSettings.height)
              : NaN; // Sem proporção se as dimensões forem inválidas

          if (cropperInstance) {
            cropperInstance.destroy(); // Destrói a instância anterior, se houver
          }

          cropperInstance = new Cropper($imageToCrop[0], {
            aspectRatio: aspectRatio,

            viewMode: 1, // Restringe a área de corte ao canvas do cropper

            dragMode: "move", // Permite mover a imagem por baixo da área de corte

            background: false, // Não mostra a grelha de fundo do cropper

            autoCropArea: 0.95, // A área de corte inicial ocupa 95%

            movable: true,

            zoomable: true,

            scalable: true,

            rotatable: false, // Desativa rotação para simplificar

            cropBoxMovable: false, // Impede que o utilizador mova a caixa de corte

            cropBoxResizable: false, // Impede que o utilizador redimensione a caixa de corte
          });
        };

        reader.readAsDataURL(file); // Limpa o valor do input para permitir que o evento 'change' dispare novamente se o mesmo ficheiro for selecionado

        $input.val("");
      }
    }); // NOVO: Listeners para os botões do modal de corte

    $("#bcek-confirm-crop-btn").on("click", function () {
      if (cropperInstance && currentCropperFieldId) {
        const croppedCanvas = cropperInstance.getCroppedCanvas({
          // Tenta obter uma resolução maior para melhor qualidade, mas respeitando o tamanho original do campo

          maxWidth: 1024,

          maxHeight: 1024,

          fillColor: "#fff",

          imageSmoothingEnabled: true,

          imageSmoothingQuality: "high",
        });

        const croppedDataUrl = croppedCanvas.toDataURL("image/png"); // Obtém a imagem cortada como Data URL

        const img = new Image();

        img.onload = function () {
          userUploadedImages[currentCropperFieldId] = img; // Guarda a imagem JÁ CORTADA

          drawPreview(); // Redesenha o preview principal

          cropperInstance.destroy();

          cropperInstance = null;

          $cropperModal.hide(); // Esconde o modal
        };

        img.src = croppedDataUrl;
      }
    });

    $("#bcek-cancel-crop-btn").on("click", function () {
      if (cropperInstance) {
        cropperInstance.destroy();

        cropperInstance = null;
      }

      $cropperModal.hide();
    });

    const $generateButton = $("#bcek-generate-image-button", $editorWrapper);

    if ($generateButton.length)
      $generateButton.on("click", handleGenerateImage);
    else
      console.error(
        "BCEK FATAL JS: Botão #bcek-generate-image-button NÃO encontrado!",
      );

    $(window).on(
      "resize",
      debounce(function () {
        if (
          ($baseImagePreviewImgTag.length && baseImageWidth > 0) ||
          visibleCtx
        ) {
          setupCanvasSizing();
          drawPreview();
        }
      }, 250),
    );

    if (visibleCtx) {
      setTimeout(function () {
        if (
          ($baseImagePreviewImgTag.length &&
            $baseImagePreviewImgTag[0] &&
            $baseImagePreviewImgTag[0].complete) ||
          !$baseImagePreviewImgTag.length
        ) {
          drawPreview();
        }
      }, 150);
    }
  } /**

     * Configura as dimensões CSS do canvas e da imagem base para exibição responsiva,

     * e os atributos width/height do canvas para a sua resolução de desenho.

     * Também inicializa o canvas fora do ecrã.

     */

  function setupCanvasSizing() {
    if (!visibleCtx) return;
    const containerWidth = $canvasContainer.width();
    if (baseImageWidth > 0 && baseImageHeight > 0) {
      displayScale = Math.min(1, containerWidth / baseImageWidth);
      $canvas[0].width = baseImageWidth;
      $canvas[0].height = baseImageHeight;

      const cssWidth = baseImageWidth * displayScale;
      const cssHeight = baseImageHeight * displayScale;

      $canvas.css({ width: cssWidth, height: cssHeight });

      if ($baseImagePreviewImgTag.length)
        $baseImagePreviewImgTag.css({ width: cssWidth, height: cssHeight });
    } else if (visibleCtx) {
      const defaultWidth = containerWidth || 800;
      $canvas[0].width = defaultWidth;
      $canvas[0].height = defaultWidth * (3 / 4);
      $canvas.css({ width: "100%", height: "auto" });
      baseImageWidth = defaultWidth;
      baseImageHeight = defaultWidth * (3 / 4);
    } // Inicializa ou redimensiona o canvas fora do ecrã

    if (baseImageWidth > 0 && baseImageHeight > 0) {
      if (!offScreenCanvas) {
        offScreenCanvas = document.createElement("canvas");
      }

      offScreenCanvas.width = baseImageWidth;

      offScreenCanvas.height = baseImageHeight;

      offCtx = offScreenCanvas.getContext("2d");
    } else {
      offScreenCanvas = null;

      offCtx = null;
    }
  } /**

     * Obtém as dimensões naturais da imagem base e chama setupCanvasSizing.

     */

  function setupCanvas() {
    if (!visibleCtx) {
      return;
    }
    if (
      $baseImagePreviewImgTag.length &&
      $baseImagePreviewImgTag[0] &&
      $baseImagePreviewImgTag[0].naturalWidth > 0
    ) {
      baseImageWidth = $baseImagePreviewImgTag[0].naturalWidth;
      baseImageHeight = $baseImagePreviewImgTag[0].naturalHeight;

      $baseImagePreviewImgTag.data("bcek-loaded", true);
    } else {
      let maxW = 0,
        maxH = 0;

      if (fieldsData)
        fieldsData.forEach((field) => {
          if (
            isObject(field) &&
            field.pos_x !== undefined &&
            field.width !== undefined
          ) {
            if (parseInt(field.pos_x) + parseInt(field.width) > maxW)
              maxW = parseInt(field.pos_x) + parseInt(field.width);
          }

          if (
            isObject(field) &&
            field.pos_y !== undefined &&
            field.height !== undefined
          ) {
            if (parseInt(field.pos_y) + parseInt(field.height) > maxH)
              maxH = parseInt(field.pos_y) + parseInt(field.height);
          }
        });

      baseImageWidth = maxW > 0 ? maxW + 20 : $canvasContainer.width() || 800;

      baseImageHeight = maxH > 0 ? maxH + 20 : baseImageWidth * (3 / 4);
    }

    setupCanvasSizing();
  } /**

     * Função auxiliar para quebrar o texto em múltiplas linhas para caber numa largura máxima.

     * Respeita quebras de linha manuais (\n) e faz a quebra automática.

     */

  function wrapTextCanvas(
    context,
    text,
    maxWidth,
    fontSize,
    fontFamily,
    fontWeight,
  ) {
    if (text === undefined || text === null) text = "";
    const paragraphs = String(text).replace(/\r\n/g, "\n").split("\n");

    const finalLines = [];

    context.font = `${fontWeight} ${fontSize}px ${fontFamily}`;
    paragraphs.forEach((paragraph) => {
      if (paragraph === "") {
        finalLines.push("");

        return;
      }

      let words = paragraph.split(" ");
      let currentLine = "";

      if (words.length > 0) {
        currentLine = words.shift() || "";
      }

      for (const word of words) {
        let testLine = currentLine + (currentLine === "" ? "" : " ") + word;

        if (
          context.measureText(testLine).width > maxWidth &&
          currentLine !== ""
        ) {
          finalLines.push(currentLine);
          currentLine = word;
        } else {
          currentLine = testLine;
        }
      }

      finalLines.push(currentLine);
    });

    return finalLines;
  } /**

     * Função auxiliar para desenhar uma imagem de utilizador no canvas especificado (pode ser o off-screen).

     */

  function drawUserImageToCanvas(targetCtx, userImg, fieldSettings) {
    if (!targetCtx || !isObject(fieldSettings)) return;
    const x = parseInt(fieldSettings.pos_x);

    const y = parseInt(fieldSettings.pos_y);

    const blockWidth = parseInt(fieldSettings.width);

    const blockHeight = parseInt(fieldSettings.height);

    const containerShape = fieldSettings.container_shape || "rectangle";

    targetCtx.save();
    if (containerShape === "circle") {
      targetCtx.beginPath();

      targetCtx.arc(
        x + blockWidth / 2,
        y + blockHeight / 2,
        Math.min(blockWidth, blockHeight) / 2,
        0,
        Math.PI * 2,
        true,
      );

      targetCtx.clip();
    } else {
      targetCtx.beginPath();

      targetCtx.rect(x, y, blockWidth, blockHeight);

      targetCtx.clip();
    }

    if (userImg && userImg.complete && userImg.naturalWidth > 0) {
      const imgWidth = userImg.naturalWidth;

      const imgHeight = userImg.naturalHeight;

      const containerAspect = blockWidth / blockHeight;

      const imgAspect = imgWidth / imgHeight;

      let sx = 0,
        sy = 0,
        sWidth = imgWidth,
        sHeight = imgHeight;
      let dx = x,
        dy = y,
        dWidth = blockWidth,
        dHeight = blockHeight;
      if (imgAspect > containerAspect) {
        sWidth = imgHeight * containerAspect;
        sx = (imgWidth - sWidth) / 2;
      } else {
        sHeight = imgWidth / containerAspect;
        sy = (imgHeight - sHeight) / 2;
      }

      targetCtx.drawImage(
        userImg,
        sx,
        sy,
        sWidth,
        sHeight,
        dx,
        dy,
        dWidth,
        dHeight,
      );
    } else {
      targetCtx.fillStyle = "#f0f0f0";
      targetCtx.fillRect(x, y, blockWidth, blockHeight);

      targetCtx.fillStyle = "#bbbbbb";
      targetCtx.font = "12px Arial";

      targetCtx.textAlign = "center";

      targetCtx.textBaseline = "middle";

      targetCtx.fillText(
        "Upload Img Aqui",
        x + blockWidth / 2,
        y + blockHeight / 2,
      );
    }

    targetCtx.restore();
  } /**

     * Desenha o preview do texto e/ou imagem no canvas, respeitando a ordem das camadas, usando double buffering.

     */

  function drawPreview() {
    if (
      !visibleCtx ||
      !offCtx ||
      baseImageWidth === 0 ||
      baseImageHeight === 0
    ) {
      if (
        $baseImagePreviewImgTag.length &&
        $baseImagePreviewImgTag[0] &&
        ($baseImagePreviewImgTag[0].complete ||
          $baseImagePreviewImgTag[0].readyState === 4 ||
          $baseImagePreviewImgTag[0].readyState === "complete") &&
        $baseImagePreviewImgTag[0].naturalWidth > 0
      ) {
        setupCanvas();
        if (!offCtx) return;
      } else {
        return;
      }
    }

    offCtx.clearRect(0, 0, baseImageWidth, baseImageHeight);
    const imageFieldsBelow = fieldsData.filter(
      (f) =>
        isObject(f) &&
        f.field_type === "image" &&
        (parseInt(f.z_index_order) === 0 ||
          f.z_index_order === undefined ||
          f.z_index_order === null ||
          f.z_index_order === ""),
    );

    const imageFieldsAbove = fieldsData.filter(
      (f) =>
        isObject(f) &&
        f.field_type === "image" &&
        parseInt(f.z_index_order) === 1,
    );

    const textFieldsToDraw = fieldsData.filter(
      (f) => isObject(f) && (!f.field_type || f.field_type === "text"),
    ); // 1. Desenha imagens do utilizador "Por Baixo" no canvas FORA DO ECRÃ

    imageFieldsBelow.forEach(function (field) {
      const userImg = userUploadedImages[field.field_id];

      drawUserImageToCanvas(offCtx, userImg, field);
    }); // 2. Desenha a imagem base do template no canvas FORA DO ECRÃ

    if (
      $baseImagePreviewImgTag.length &&
      $baseImagePreviewImgTag[0] &&
      $baseImagePreviewImgTag[0].complete &&
      $baseImagePreviewImgTag[0].naturalWidth > 0
    ) {
      try {
        offCtx.drawImage(
          $baseImagePreviewImgTag[0],
          0,
          0,
          baseImageWidth,
          baseImageHeight,
        );
      } catch (e) {
        console.error(
          "BCEK JS Error: Falha ao desenhar imagem base no canvas fora do ecrã.",
          e,
        );
      }
    } // 3. Desenha imagens do utilizador "Por Cima" no canvas FORA DO ECRÃ

    imageFieldsAbove.forEach(function (field) {
      const userImg = userUploadedImages[field.field_id];

      drawUserImageToCanvas(offCtx, userImg, field);
    }); // 4. Desenha todos os campos de texto no canvas FORA DO ECRÃ

    textFieldsToDraw.forEach(function (field) {
      const fieldId = field.field_id;

      let textFromInput = $(
        "#bcek_field_text_" + fieldId,
        $editorWrapper,
      ).val();
      let currentFontSize =
        parseFloat(
          $("#bcek_field_fontsize_" + fieldId, $editorWrapper).val(),
        ) || parseFloat(field.font_size);

      let textToRender;

      if (textFromInput === undefined || textFromInput.trim() === "") {
        textToRender = field.default_text || "";
      } else {
        textToRender = textFromInput;
      }

      let lineHeightMultiplier =
        parseFloat(field.line_height_multiplier) || 1.3;

      let fontFamilyName = "Montserrat";
      let fontWeight = "400";
      if (
        isObject(field) &&
        field.font_family &&
        field.font_family.includes("Bold")
      )
        fontWeight = "700";
      if (
        isObject(field) &&
        field.font_family &&
        field.font_family.includes("Black")
      )
        fontWeight = "900";
      offCtx.font = `${fontWeight} ${currentFontSize}px ${fontFamilyName}`;
      offCtx.fillStyle = field.font_color;
      offCtx.textBaseline = "alphabetic";

      const x = parseInt(field.pos_x);
      const y = parseInt(field.pos_y);

      const blockWidth = parseInt(field.width);
      const blockHeight = parseInt(field.height);

      const textPadding = 3;
      const effectiveBlockWidth = blockWidth - textPadding * 2;

      const lines = wrapTextCanvas(
        offCtx,
        textToRender,
        effectiveBlockWidth,
        currentFontSize,
        fontFamilyName,
        fontWeight,
      );
      let actualLineHeight = currentFontSize * lineHeightMultiplier;
      let currentTextY = y + currentFontSize + textPadding;
      const num_lines = lines.length;

      for (let i = 0; i < num_lines; i++) {
        const line = lines[i];

        if (
          currentTextY - currentFontSize - y - textPadding + actualLineHeight >
            blockHeight &&
          i > 0
        )
          break;
        if (line === "") {
          currentTextY += actualLineHeight;
          continue;
        }

        let textX;
        const alignment = field.alignment || "left";
        const isLastLineOfParagraph =
          i === num_lines - 1 ||
          (lines[i + 1] !== undefined && lines[i + 1] === "");

        if (
          alignment === "justify" &&
          !isLastLineOfParagraph &&
          line.trim() !== "" &&
          line.includes(" ")
        ) {
          offCtx.textAlign = "left";
          let wordsInLine = line.split(" ");
          wordsInLine = wordsInLine.filter(
            (w) => w.length > 0 || wordsInLine.length === 1,
          );
          if (wordsInLine.length === 0) {
            currentTextY += actualLineHeight;
            continue;
          }

          let lineWithoutSpaces = wordsInLine.join("");
          let totalWordsWidth = offCtx.measureText(lineWithoutSpaces).width;
          let totalSpacesToDistribute = wordsInLine.length - 1;

          if (totalSpacesToDistribute > 0) {
            let spacePerGap =
              (effectiveBlockWidth - totalWordsWidth) / totalSpacesToDistribute;

            let singleSpaceApproxWidth = offCtx.measureText(" ").width;
            if (spacePerGap < 0 || spacePerGap > singleSpaceApproxWidth * 4) {
              offCtx.textAlign = "left";
              textX = x + textPadding;
              offCtx.fillText(line, textX, currentTextY);
            } else {
              let currentX = x + textPadding;

              wordsInLine.forEach((word, index) => {
                offCtx.fillText(word, currentX, currentTextY);
                currentX += offCtx.measureText(word).width;
                if (index < totalSpacesToDistribute) currentX += spacePerGap;
              });
            }
          } else {
            offCtx.textAlign = "left";
            textX = x + textPadding;
            offCtx.fillText(line, textX, currentTextY);
          }
        } else {
          let finalAlignment = alignment === "justify" ? "left" : alignment;
          offCtx.textAlign = finalAlignment;
          if (finalAlignment === "left") textX = x + textPadding;
          else if (finalAlignment === "center") textX = x + blockWidth / 2;
          else textX = x + blockWidth - textPadding;
          offCtx.fillText(line, textX, currentTextY);
        }

        currentTextY += actualLineHeight;
      }
    }); // Finalmente, desenha o conteúdo do canvas fora do ecrã no canvas visível de uma só vez

    if (visibleCtx && offScreenCanvas) {
      visibleCtx.clearRect(0, 0, baseImageWidth, baseImageHeight);

      visibleCtx.drawImage(offScreenCanvas, 0, 0);
    }
  } /**

     * Manipula o clique em qualquer um dos botões "Gerar Imagem".

     * Modificado para aceitar um formato (png ou bmp).

     * @param {string} format O formato de imagem desejado ('png' ou 'bmp').

     */

  function handleGenerateImage(format) {
    const $button = $(".bcek-generate-btn"); // Seleciona ambos os botões pela classe comum

    const $loader = $("#bcek-loader");
    const $resultArea = $("#bcek-result-area");

    $button.prop("disabled", true).css("opacity", 0.7);
    $loader.show();
    $resultArea.html("");
    const userInputs = {};
    if (fieldsData && fieldsData.length > 0) {
      fieldsData.forEach(function (field) {
        if (!isObject(field) || !field.field_id) return;

        const fieldId = field.field_id;

        if (!field.field_type || field.field_type === "text") {
          userInputs[fieldId] = {
            type: "text",

            text: $("#bcek_field_text_" + fieldId, $editorWrapper).val(),
            fontSize:
              $("#bcek_field_fontsize_" + fieldId, $editorWrapper).val() ||
              field.font_size,
          };
        } else if (field.field_type === "image") {
          if (userUploadedImages[fieldId] && userUploadedImages[fieldId].src) {
            userInputs[fieldId] = {
              type: "image",

              imageDataUrl: userUploadedImages[fieldId].src,
            };
          } else {
            userInputs[fieldId] = { type: "image", imageDataUrl: null };
          }
        }
      });
    }

    const userFilename = $("#bcek_filename", $editorWrapper).val();

    if (!ajaxUrl || !generateNonce) {
      console.error(
        "BCEK FATAL JS: ajaxUrl ou generateNonce não definidos em handleGenerateImage.",
      );

      $resultArea.html(
        '<p style="color:red;">Erro de configuração interna. Contacte o administrador.</p>',
      );

      $button.prop("disabled", false).css("opacity", 1);
      $loader.hide();

      return;
    }

    $.ajax({
      url: ajaxUrl,
      type: "POST",
      data: {
        action: "bcek_generate_image",
        nonce: generateNonce,
        template_id: templateData.template_id,
        user_inputs: userInputs,
        user_filename: userFilename,

        format: format, // NOVO: envia o formato desejado para o backend
      },

      success: function (response) {
        if (response.success) {
          let filenameFromResponse = response.data.filename || "download.png";

          $resultArea.html(
            '<p style="color:green;">' +
              (bcek_strings.image_generated_success || "Imagem gerada!") +
              '</p><a href="' +
              esc_url(response.data.url) +
              '" target="_blank" download="' +
              esc_attr(filenameFromResponse) +
              '" class="button button-secondary">' +
              (bcek_strings.download_image || "Baixar") +
              '</a><p><input type="text" value="' +
              esc_attr(response.data.url) +
              '" readonly style="width:100%;" onclick="this.select();"></p>' +
              (response.data.deleted_in
                ? '<p style="font-size:0.9em; color:#777;">' +
                  esc_html(response.data.deleted_in) +
                  "</p>"
                : ""),
          );
        } else {
          let errorMessage =
            response.data && response.data.message
              ? response.data.message
              : bcek_strings.error_generating_image_unknown ||
                "Erro desconhecido.";

          $resultArea.html(
            '<p style="color:red;">' +
              (bcek_strings.error_generating_image || "Erro: ") +
              esc_html(errorMessage) +
              "</p>",
          );
          console.error("BCEK AJAX Success false:", response);
        }
      },

      error: function (jqXHR, textStatus, errorThrown) {
        console.error("BCEK AJAX Error Full:", jqXHR);
        let errorDetail = jqXHR.responseText || errorThrown || textStatus;

        if (
          jqXHR.responseText &&
          jqXHR.responseText.toLowerCase().includes("<html")
        ) {
          errorDetail = "Erro do servidor.";
        }

        $resultArea.html(
          '<p style="color:red;">' +
            (bcek_strings.error_ajax || "Erro AJAX: ") +
            esc_html(errorDetail) +
            "</p>",
        );
      },

      complete: function () {
        $button.prop("disabled", false).css("opacity", 1);
        $loader.hide();
      },
    });
  } // Listener de clique modificado para usar delegação e obter o formato

  $editorWrapper.on("click", ".bcek-generate-btn", function () {
    const format = $(this).data("format"); // Obtém 'png' ou 'bmp' do atributo data-format

    handleGenerateImage(format);
  }); /**

     * Função Debounce: Limita a frequência com que uma função é executada.

     */

  function debounce(func, wait, immediate) {
    var timeout;

    return function () {
      var context = this,
        args = arguments;

      var later = function () {
        timeout = null;
        if (!immediate) func.apply(context, args);
      };

      var callNow = immediate && !timeout;

      clearTimeout(timeout);

      timeout = setTimeout(later, wait);

      if (callNow) func.apply(context, args);
    };
  } // Strings para mensagens.

  const bcek_strings = {
    image_generated_success:
      "Imagem gerada! Ela será excluída do servidor em aproximadamente 30 minutos.",
    download_image: "Baixar / Ver Imagem",
    error_generating_image: "Erro ao gerar imagem: ",
    error_generating_image_unknown:
      "Ocorreu um erro desconhecido durante a geração.",
    error_ajax: "Erro na requisição AJAX: ",
  }; // Funções de escape simples.

  function esc_attr(str) {
    if (typeof str !== "string") return "";
    return str
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  function esc_html(str) {
    if (typeof str !== "string") return "";
    return str
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function esc_url(str) {
    if (typeof str !== "string") return "";
    return encodeURI(str);
  } // Helper para verificar se uma variável é um objeto e não nula

  function isObject(value) {
    return value && typeof value === "object" && value !== null;
  }
});
