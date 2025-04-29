jQuery(document).ready(function ($) {
    const galleryButton = $('.view-gallery-btn');
    const modal = $('#gallery-modal');
    const closeModal = $('#close-modal');
    const body = $('body');
    var selectedImages = [];
    let currentGallery = null;
    let max_images = -1;
    const editorModal = $('#editor-modal');
    let editorInstance = null;
    let currentImageIndex = 0;
    let imageThumbs = null;
    let originalImageData = null;
    let originalImageId = null;
    let lockedAspectRatio = null;
    let isUsingCustomRatios = false;
    let hasFrameImage = false;
    let frameTemplate = null;

    function getImagePixelData(imageSrc, callback) {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.onload = function () {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            const imageData = ctx.getImageData(0, 0, img.width, img.height).data;
            callback(imageData);
        };
        img.onerror = function () {
            console.error('Failed to load image for comparison:', imageSrc);
            callback(null);
        };
        img.src = imageSrc;
    }

    function areImagesDifferent(originalData, editedData) {
        if (!originalData || !editedData) return true;
        if (originalData.length !== editedData.length) return true;
        for (let i = 0; i < originalData.length; i++) {
            if (originalData[i] !== editedData[i]) return true;
        }
        return false;
    }

    function cropImageFromCenter(imageUrl, aspectRatio, callback) {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.onload = function () {
            const originalWidth = img.width;
            const originalHeight = img.height;
            console.log('Original image dimensions for cropping:', { width: originalWidth, height: originalHeight });

            let cropWidth, cropHeight;
            const imageAspectRatio = originalWidth / originalHeight;

            if (imageAspectRatio > aspectRatio) {
                cropHeight = originalHeight;
                cropWidth = cropHeight * aspectRatio;
            } else {
                cropWidth = originalWidth;
                cropHeight = cropWidth / aspectRatio;
            }

            const cropX = (originalWidth - cropWidth) / 2;
            const cropY = (originalHeight - cropHeight) / 2;

            console.log('Crop dimensions:', { width: cropWidth, height: cropHeight });
            console.log('Crop position (centered):', { x: cropX, y: cropY });

            const canvas = document.createElement('canvas');
            canvas.width = cropWidth;
            canvas.height = cropHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, cropX, cropY, cropWidth, cropHeight, 0, 0, cropWidth, cropHeight);

            const croppedImageDataUrl = canvas.toDataURL('image/png');
            console.log('Cropped image data URL generated');

            callback(null, croppedImageDataUrl);
        };
        img.onerror = function () {
            console.error('Failed to load image for cropping:', imageUrl);
            callback(new Error('Failed to load image for cropping'), null);
        };
        img.src = imageUrl;
    }

    function showPreviewModal(compositeImageUrl) {
        if (!$('#preview-modal').length) {
            const previewModalHtml = `
                <div id="preview-modal">
                    <div id="preview-modal-content">
                        <span id="preview-modal-close">×</span>
                        <h2>Image Preview</h2>
                        <img id="preview-image" src="" alt="Composited Image" />
                    </div>
                </div>
            `;
            $('body').append(previewModalHtml);
        }

        $('#preview-image').attr('src', compositeImageUrl);
        $('#preview-modal').show();
        body.css('overflow', 'hidden');

        $('#preview-modal-close').off('click').on('click', function () {
            $('#preview-modal').hide();
            body.css('overflow', '');
            const previewButton = $(`.preview-image-btn[data-index="${currentImageIndex}"]`);
            previewButton.prop('disabled', false).css('opacity', '1');
            console.log(`Preview button for image index ${currentImageIndex} re-enabled`);
        });

        $('#preview-modal').off('click').on('click', function (e) {
            if ($(e.target).is('#preview-modal')) {
                $('#preview-modal').hide();
                body.css('overflow', '');
                const previewButton = $(`.preview-image-btn[data-index="${currentImageIndex}"]`);
                previewButton.prop('disabled', false).css('opacity', '1');
                console.log(`Preview button for image index ${currentImageIndex} re-enabled`);
            }
        });
    }

    function checkCustomRatios() {
        frameTemplate = myAjax.frame_template || {};
        const coordinates = frameTemplate.coordinates || {
            x1: 0,
            y1: 0,
            x2: 0,
            y2: 0,
            aspect_ratio: 0
        };
        hasFrameImage = frameTemplate.frame_image_url && frameTemplate.frame_image_url.trim() !== '';
        const hasValidAspectRatio = parseFloat(coordinates.aspect_ratio) > 0;
        const hasValidCoordinates = coordinates.x1 !== 0 || coordinates.y1 !== 0 || coordinates.x2 !== 0 || coordinates.y2 !== 0;
        isUsingCustomRatios = hasFrameImage && hasValidCoordinates && hasValidAspectRatio;
        console.log('Custom ratios ' + (isUsingCustomRatios ? 'are' : 'are not') + ' used');
    }

    galleryButton.on('click', function () {
        currentGallery = $(this);
        modal.show();
        hiddenImagesInput = currentGallery.closest('.image-selection-container').find('.selected-image-ids');
        let selectedImageIds = hiddenImagesInput.val().split(',').filter(Boolean);
        let galleryoption = hiddenImagesInput.data("galleryoption");
        max_images = hiddenImagesInput.data("max_images");
        modal.find('input').prop('checked', false);
        modal.find('input').data('galleryoption', galleryoption);
        modal.find('input').data('max_images', max_images);
        modal.find('input').each(function () {
            if (selectedImageIds.includes($(this).val())) {
                $(this).prop('checked', true);
            }
        });

        body.css('overflow', 'hidden');
    });

    closeModal.on('click', function () {
        currentGallery = null;
        modal.hide();
        body.css('overflow', '');

        imageThumbs = $('.selected-image-thumb');
        currentImageIndex = 0;

        checkCustomRatios();

        imageThumbs.each(function (index) {
            const $thumb = $(this);
            const $parent = $thumb.closest('.selected-image');

            $parent.find('.edit-image-btn, .preview-image-btn').remove();

            const editButton = $('<button>')
                .addClass('edit-image-btn')
                .text('Edit')
                .css({
                    backgroundColor: '#4CAF50',
                    color: '#fff',
                    border: 'none',
                    padding: '5px 10px',
                    cursor: 'pointer',
                    marginTop: '5px',
                    display: 'block',
                    marginLeft: 'auto',
                    marginRight: 'auto'
                })
                .attr('data-index', index);
            $parent.append(editButton);

            if (isUsingCustomRatios) {
                const previewButton = $('<button>')
                    .addClass('preview-image-btn')
                    .text('Preview')
                    .css({
                        backgroundColor: '#007BFF',
                        color: '#fff',
                        border: 'none',
                        padding: '5px 10px',
                        cursor: 'pointer',
                        marginTop: '5px',
                        display: 'block',
                        marginLeft: 'auto',
                        marginRight: 'auto'
                    })
                    .attr('data-index', index);
                $parent.append(previewButton);
            }
        });
    });

    $(document).on('click', '.preview-image-btn', function () {
        currentImageIndex = parseInt($(this).attr('data-index'), 10);
        console.log('Previewing image at index:', currentImageIndex);

        const previewButton = $(this);
        previewButton.prop('disabled', true).css('opacity', '0.5');
        console.log(`Preview button for image index ${currentImageIndex} disabled`);

        const currentImageThumb = imageThumbs.eq(currentImageIndex);
        const imageUrl = currentImageThumb.attr('src');

        if (isUsingCustomRatios) {
            const frameImageUrl = frameTemplate.frame_image_url;
            let coordinates = frameTemplate.coordinates;

            if (frameImageUrl && coordinates.x1 !== 0 && coordinates.y1 !== 0 && coordinates.x2 !== 0 && coordinates.y2 !== 0) {
                console.log('Coordinates (pre-scaled to 1200px):', coordinates);

                cropImageFromCenter(imageUrl, coordinates.aspect_ratio, function (cropError, croppedImageDataUrl) {
                    if (cropError) {
                        console.error('Failed to crop image:', cropError);
                        alert('Failed to crop image for preview.');
                        showPreviewModal(imageUrl);
                        return;
                    }

                    $.ajax({
                        url: myAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'save_edited_image',
                            image_data: croppedImageDataUrl,
                            nonce: myAjax.nonce
                        },
                        success: function (response) {
                            console.log('AJAX response for saving cropped image:', response);
                            if (response.success) {
                                const croppedImageUrl = response.data.file_url;
                                console.log('Cropped image URL:', croppedImageUrl);

                                const frameImg = new Image();
                                frameImg.crossOrigin = 'Anonymous';
                                frameImg.onload = function () {
                                    const frameWidth = frameImg.width;
                                    const frameHeight = frameImg.height;
                                    console.log('Frame image dimensions:', { width: frameWidth, height: frameHeight });

                                    const previewWidth = 1200;
                                    const unscaleFactor = frameWidth / previewWidth;
                                    const unscaledCoordinates = {
                                        x1: coordinates.x1 * unscaleFactor,
                                        y1: coordinates.y1 * unscaleFactor,
                                        x2: coordinates.x2 * unscaleFactor,
                                        y2: coordinates.y2 * unscaleFactor,
                                        aspect_ratio: coordinates.aspect_ratio,
                                        frame_original_width: frameWidth,
                                        frame_original_height: frameHeight
                                    };

                                    console.log('Unscaled coordinates for composite (original frame dimensions):', unscaledCoordinates);

                                    console.log('Compositing images with frame:', frameImageUrl);
                                    $.ajax({
                                        url: myAjax.ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'composite_images',
                                            frame_image_url: frameImageUrl,
                                            edited_image_url: croppedImageUrl,
                                            coordinates: unscaledCoordinates,
                                            nonce: myAjax.nonce
                                        },
                                        success: function (compositeResponse) {
                                            console.log('Composite AJAX response:', compositeResponse);
                                            if (compositeResponse.success) {
                                                console.log('Composited image URL:', compositeResponse.data.composite_url);
                                                showPreviewModal(compositeResponse.data.composite_url);
                                            } else {
                                                console.error('Failed to composite image:', compositeResponse.data);
                                                alert('Failed to generate preview: ' + (compositeResponse.data || 'Unknown error'));
                                                showPreviewModal(croppedImageUrl);
                                            }
                                        },
                                        error: function (xhr, status, error) {
                                            console.error('Composite AJAX error:', status, error);
                                            console.log('Response Text:', xhr.responseText);
                                            alert('Failed to generate preview. Please check the server logs for more details.');
                                            showPreviewModal(croppedImageUrl);
                                        }
                                    });
                                };
                                frameImg.onerror = function () {
                                    console.error('Failed to load frame image:', frameImageUrl);
                                    alert('Failed to load frame image for compositing.');
                                    showPreviewModal(croppedImageUrl);
                                };
                                frameImg.src = frameImageUrl;
                            } else {
                                console.error('Failed to save cropped image:', response.data);
                                alert('Failed to save cropped image for preview.');
                                showPreviewModal(imageUrl);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('AJAX error while saving cropped image:', status, error);
                            alert('Failed to save cropped image for preview.');
                            showPreviewModal(imageUrl);
                        }
                    });
                });
            } else {
                console.log('Frame image or coordinates not available, skipping composite preview');
                showPreviewModal(imageUrl);
            }
        } else {
            console.log('No custom ratios received, skipping preview compositing');
            showPreviewModal(imageUrl);
        }
    });

    $(document).on('click', '.edit-image-btn', function () {
        currentImageIndex = parseInt($(this).attr('data-index'), 10);
        console.log('Editing image at index:', currentImageIndex);
        editorModal.show();
        initializeModal();
    });

    function initializeModal() {
        const editorElement = document.querySelector('#tui-image-editor');
        if (editorElement) {
            editorElement.innerHTML = '';

            const initialImageSrc = imageThumbs.eq(currentImageIndex).attr('src');
            getImagePixelData(initialImageSrc, function (data) {
                originalImageData = data;
                console.log('Original image data loaded for index:', currentImageIndex);
            });

            const currentImageElement = imageThumbs.eq(currentImageIndex);
            const parentSelectedImage = currentImageElement.closest('.selected-image');
            originalImageId = parentSelectedImage.length ? parentSelectedImage.attr('data-id') : null;
            console.log('Original image ID:', originalImageId);

            editorInstance = new tui.ImageEditor(editorElement, {
                includeUI: {
                    loadImage: {
                        path: initialImageSrc,
                        name: 'Placeholder Image',
                    },
                    theme: {
                        'common.bi.image': '',
                        'common.bisize.width': '0px',
                        'common.backgroundColor': '#fff',
                    },
                    menu: ['crop', 'flip', 'rotate', 'shape', 'text', 'filter'],
                    initMenu: 'crop',
                    uiSize: {
                        width: '700px',
                        height: '500px',
                    },
                    menuBarPosition: 'bottom',
                },
                cssMaxWidth: 700,
                cssMaxHeight: 500,
                selectionStyle: {
                    cornerSize: 20,
                    rotatingPointOffset: 70,
                },
            });

            setTimeout(() => {
                frameTemplate = myAjax.frame_template || {};
                const coordinates = frameTemplate.coordinates || {
                    x1: 0,
                    y1: 0,
                    x2: 0,
                    y2: 0,
                    aspect_ratio: 0
                };
                console.log('Parsed frame template coordinates:', coordinates);

                hasFrameImage = frameTemplate.frame_image_url && frameTemplate.frame_image_url.trim() !== '';
                const hasValidAspectRatio = parseFloat(coordinates.aspect_ratio) > 0;
                const hasValidCoordinates = coordinates.x1 !== 0 || coordinates.y1 !== 0 || coordinates.x2 !== 0 || coordinates.y2 !== 0;

                isUsingCustomRatios = hasValidCoordinates && hasFrameImage && hasValidAspectRatio;
                console.log('Custom ratios ' + (isUsingCustomRatios ? 'are' : 'are not') + ' used');

                if (isUsingCustomRatios) {
                    const canvasSize = editorInstance.getCanvasSize();
                    console.log('Editor canvas size:', canvasSize);

                    const img = new Image();
                    img.onload = function () {
                        const originalWidth = img.width;
                        const originalHeight = img.height;
                        console.log('Original image dimensions:', { width: originalWidth, height: originalHeight });

                        const scaleX = canvasSize.width / originalWidth;
                        const scaleY = canvasSize.height / originalHeight;
                        console.log('Scaling factors:', { scaleX, scaleY });

                        const roundedScaleX = Number(scaleX.toFixed(4));
                        const roundedScaleY = Number(scaleY.toFixed(4));
                        console.log('Rounded scaling factors:', { scaleX: roundedScaleX, scaleY: roundedScaleY });

                        let scaledX1 = coordinates.x1 * roundedScaleX;
                        let scaledY1 = coordinates.y1 * roundedScaleY;
                        let scaledX2 = coordinates.x2 * roundedScaleX;
                        let scaledY2 = coordinates.y2 * roundedScaleY;
                        console.log('Scaled coordinates for canvas:', { x1: scaledX1, y1: scaledY1, x2: scaledX2, y2: scaledY2 });

                        let cropWidth = scaledX2 - scaledX1;
                        let cropHeight = scaledY2 - scaledY1;

                        lockedAspectRatio = coordinates.aspect_ratio;
                        console.log('Using admin-provided aspect ratio:', lockedAspectRatio);

                        if (scaledX2 > canvasSize.width || scaledY2 > canvasSize.height || scaledX1 < 0 || scaledY1 < 0) {
                            console.log('Crop coordinates exceed canvas bounds, adjusting...');
                            const maxWidth = canvasSize.width;
                            const maxHeight = canvasSize.height;

                            const scaleToFitWidth = maxWidth / cropWidth;
                            const scaleToFitHeight = maxHeight / cropHeight;
                            const scaleToFit = Math.min(scaleToFitWidth, scaleToFitHeight);

                            cropWidth = cropWidth * scaleToFit;
                            cropHeight = cropWidth / lockedAspectRatio;

                            scaledX1 = (canvasSize.width - cropWidth) / 2;
                            scaledY1 = (canvasSize.height - cropHeight) / 2;
                            scaledX2 = scaledX1 + cropWidth;
                            scaledY2 = scaledY1 + cropHeight;

                            const displayScaledX1 = Number(scaledX1.toFixed(2));
                            const displayScaledY1 = Number(scaledY1.toFixed(2));
                            const displayScaledX2 = Number(scaledX2.toFixed(2));
                            const displayScaledY2 = Number(scaledY2.toFixed(2));
                            const displayCropWidth = Number(cropWidth.toFixed(2));
                            const displayCropHeight = Number(cropHeight.toFixed(2));

                            console.log('Adjusted scaled coordinates:', { x1: displayScaledX1, y1: displayScaledY1, x2: displayScaledX2, y2: displayScaledY2 });
                            console.log('Adjusted crop dimensions:', { width: displayCropWidth, height: displayCropHeight });

                            const finalAspectRatio = cropWidth / cropHeight;
                            console.log('Final aspect ratio after adjustment:', finalAspectRatio);
                            console.log('Difference from admin-provided aspect ratio:', Math.abs(finalAspectRatio - lockedAspectRatio));
                        }

                        try {
                            editorInstance.startDrawingMode('CROPPER');

                            const enforceCropZone = () => {
                                let fabricCanvas;
                                try {
                                    fabricCanvas = editorInstance._graphics._canvas;
                                } catch (err) {
                                    console.error('Failed to access Fabric.js canvas:', err);
                                    return;
                                }

                                if (fabricCanvas) {
                                    console.log('Fabric.js canvas accessed successfully');

                                    fabricCanvas.on('mouse:down', (e) => {
                                        const isRightClick = e.e.button === 2;
                                        const cropZoneObject = fabricCanvas.getObjects().find(obj => obj.type === 'cropzone');
                                        if (cropZoneObject && !e.target) {
                                            console.log(`Preventing ${isRightClick ? 'right-click' : 'left-click'} drag to draw new crop area`);
                                            e.e.preventDefault();
                                            e.e.stopPropagation();
                                            fabricCanvas.setActiveObject(cropZoneObject);
                                        }
                                    });

                                    fabricCanvas.wrapperEl.addEventListener('contextmenu', (e) => {
                                        console.log('Preventing right-click context menu');
                                        e.preventDefault();
                                    });

                                    fabricCanvas.on('mouse:up', () => {
                                        const cropZoneObject = fabricCanvas.getObjects().find(obj => obj.type === 'cropzone');
                                        if (cropZoneObject) {
                                            const currentWidth = cropZoneObject.width * cropZoneObject.scaleX;
                                            const currentHeight = cropZoneObject.height * cropZoneObject.scaleY;
                                            const currentAspectRatio = currentWidth / currentHeight;

                                            if (Math.abs(currentAspectRatio - lockedAspectRatio) > 0.01) {
                                                console.log('Adjusting crop box after drag to enforce aspect ratio');
                                                const newHeight = currentWidth / lockedAspectRatio;
                                                setTimeout(() => {
                                                    const updatedCropZoneObject = fabricCanvas.getObjects().find(obj => obj.type === 'cropzone');
                                                    if (updatedCropZoneObject) {
                                                        updatedCropZoneObject.set({
                                                            height: newHeight / updatedCropZoneObject.scaleX,
                                                            scaleY: updatedCropZoneObject.scaleX,
                                                        });
                                                        console.log('Aspect ratio enforced after drag:', {
                                                            width: (updatedCropZoneObject.width * updatedCropZoneObject.scaleX).toFixed(2),
                                                            height: (updatedCropZoneObject.height * updatedCropZoneObject.scaleY).toFixed(2),
                                                            aspectRatio: (updatedCropZoneObject.width * updatedCropZoneObject.scaleX / (updatedCropZoneObject.height * updatedCropZoneObject.scaleY)).toFixed(4),
                                                        });
                                                        fabricCanvas.renderAll();
                                                    } else {
                                                        console.warn('Crop zone object not found after mouse:up');
                                                    }
                                                }, 0);
                                            }
                                        } else {
                                            console.warn('Crop zone object not found on mouse:up');
                                        }
                                    });

                                    const findCropZoneObject = (attempt = 1) => {
                                        const maxAttempts = 10;
                                        const cropZoneObject = fabricCanvas.getObjects().find(obj => obj.type === 'cropzone');
                                        if (cropZoneObject) {
                                            console.log('Crop zone object found on attempt:', attempt);

                                            cropZoneObject.set({
                                                left: scaledX1 + cropWidth / 2,
                                                top: scaledY1 + cropHeight / 2,
                                                width: cropWidth,
                                                height: cropHeight,
                                                scaleX: 1,
                                                scaleY: 1,
                                                lockScalingX: false,
                                                lockScalingY: false,
                                                lockUniScaling: true,
                                                uniformScaling: true,
                                                hasControls: true,
                                                lockRotation: true,
                                                lockScalingFlip: true,
                                                lockSkewingX: true,
                                                lockSkewingY: true,
                                                selectable: true,
                                                evented: true,
                                            });

                                            cropZoneObject.setControlsVisibility({
                                                mt: false,
                                                mb: false,
                                                ml: false,
                                                mr: false,
                                                tl: true,
                                                tr: true,
                                                bl: true,
                                                br: true,
                                                mtr: false
                                            });
                                            console.log('Midpoint controls disabled (mt, mb, ml, mr)');

                                            cropZoneObject.on('scaling', () => {
                                                const currentWidth = cropZoneObject.width * cropZoneObject.scaleX;
                                                const currentHeight = cropZoneObject.height * cropZoneObject.scaleY;
                                                const currentAspectRatio = currentWidth / currentHeight;

                                                if (Math.abs(currentAspectRatio - lockedAspectRatio) > 0.01) {
                                                    const newHeight = currentWidth / lockedAspectRatio;
                                                    cropZoneObject.set({
                                                        height: newHeight / cropZoneObject.scaleX,
                                                        scaleY: cropZoneObject.scaleX,
                                                    });
                                                    console.log('Aspect ratio enforced during scaling:', {
                                                        width: (cropZoneObject.width * cropZoneObject.scaleX).toFixed(2),
                                                        height: (cropZoneObject.height * cropZoneObject.scaleY).toFixed(2),
                                                        aspectRatio: (cropZoneObject.width * cropZoneObject.scaleX / (cropZoneObject.height * cropZoneObject.scaleY)).toFixed(4),
                                                    });
                                                }
                                                fabricCanvas.renderAll();
                                            });

                                            cropZoneObject.off('moving');
                                            cropZoneObject.on('moving', () => {
                                                // Get the underlying image object
                                                const imageObject = fabricCanvas.getObjects().find(obj => obj.type === 'image');
                                                if (!imageObject) {
                                                    console.warn('Underlying image object not found');
                                                    return;
                                                }

                                                // Get image dimensions and position
                                                const imageWidth = imageObject.width * imageObject.scaleX;
                                                const imageHeight = imageObject.height * imageObject.scaleY;
                                                const imageLeft = imageObject.left - (imageWidth / 2); // Image left edge
                                                const imageTop = imageObject.top - (imageHeight / 2); // Image top edge
                                                const imageRight = imageObject.left + (imageWidth / 2); // Image right edge
                                                const imageBottom = imageObject.top + (imageHeight / 2); // Image bottom edge

                                                const cropZoneWidth = cropZoneObject.width * cropZoneObject.scaleX;
                                                const cropZoneHeight = cropZoneObject.height * cropZoneObject.scaleY;

                                                // Calculate the actual edges of the crop box
                                                const actualLeft = cropZoneObject.left - (cropZoneWidth / 2);
                                                const actualTop = cropZoneObject.top - (cropZoneHeight / 2);
                                                const actualRight = cropZoneObject.left + (cropZoneWidth / 2);
                                                const actualBottom = cropZoneObject.top + (cropZoneHeight / 2);

                                                // Boundary checks relative to the image's bounds
                                                if (actualLeft < imageLeft) {
                                                    cropZoneObject.set({ left: imageLeft + (cropZoneWidth / 2) });
                                                }
                                                if (actualTop < imageTop) {
                                                    cropZoneObject.set({ top: imageTop + (cropZoneHeight / 2) });
                                                }
                                                if (actualRight > imageRight) {
                                                    cropZoneObject.set({ left: imageRight - (cropZoneWidth / 2) });
                                                }
                                                if (actualBottom > imageBottom) {
                                                    cropZoneObject.set({ top: imageBottom - (cropZoneHeight / 2) });
                                                }

                                                console.log('Image bounds:', {
                                                    imageLeft: imageLeft.toFixed(2),
                                                    imageTop: imageTop.toFixed(2),
                                                    imageRight: imageRight.toFixed(2),
                                                    imageBottom: imageBottom.toFixed(2),
                                                });

                                                console.log('Crop zone position after moving:', {
                                                    left: cropZoneObject.left.toFixed(2),
                                                    top: cropZoneObject.top.toFixed(2),
                                                    actualLeft: (cropZoneObject.left - (cropZoneWidth / 2)).toFixed(2),
                                                    actualTop: (cropZoneObject.top - (cropZoneHeight / 2)).toFixed(2),
                                                    actualRight: (cropZoneObject.left + (cropZoneWidth / 2)).toFixed(2),
                                                    actualBottom: (cropZoneObject.top + (cropZoneHeight / 2)).toFixed(2),
                                                });

                                                fabricCanvas.renderAll();
                                            });

                                            fabricCanvas.renderAll();
                                            console.log('Crop zone set with dimensions:', {
                                                left: Number(cropZoneObject.left.toFixed(2)),
                                                top: Number(cropZoneObject.top.toFixed(2)),
                                                width: Number((cropZoneObject.width * cropZoneObject.scaleX).toFixed(2)),
                                                height: Number((cropZoneObject.height * cropZoneObject.scaleY).toFixed(2)),
                                                aspectRatio: Number(((cropZoneObject.width * cropZoneObject.scaleX) / (cropZoneObject.height * cropZoneObject.scaleY)).toFixed(4)),
                                            });
                                            console.log('Crop zone properties:', {
                                                lockScalingX: cropZoneObject.lockScalingX,
                                                lockScalingY: cropZoneObject.lockScalingY,
                                                hasControls: cropZoneObject.hasControls,
                                                lockRotation: cropZoneObject.lockRotation,
                                                lockScalingFlip: cropZoneObject.lockScalingFlip,
                                                lockSkewingX: cropZoneObject.lockSkewingX,
                                                lockSkewingY: cropZoneObject.lockSkewingY,
                                                selectable: cropZoneObject.selectable,
                                                evented: cropZoneObject.evented,
                                                lockUniScaling: cropZoneObject.lockUniScaling,
                                                uniformScaling: cropZoneObject.uniformScaling,
                                            });
                                        } else if (attempt < maxAttempts) {
                                            console.log('Crop zone object not found on attempt:', attempt);
                                            setTimeout(() => findCropZoneObject(attempt + 1), 500);
                                        } else {
                                            console.log('Crop zone object not found after', maxAttempts, 'attempts.');
                                        }
                                    };

                                    findCropZoneObject();
                                }
                            };

                            const monitorCropMode = (attempt = 1) => {
                                const maxAttempts = 5;
                                const cropButton = document.querySelector('.tie-btn-crop.tui-image-editor-item');
                                if (cropButton) {
                                    console.log('Crop button found on attempt:', attempt);
                                    cropButton.addEventListener('click', () => {
                                        console.log('Crop mode activated, reapplying crop zone');
                                        setTimeout(() => {
                                            editorInstance.stopDrawingMode();
                                            editorInstance.startDrawingMode('CROPPER');
                                            enforceCropZone();
                                        }, 200);
                                    });
                                } else if (attempt < maxAttempts) {
                                    console.log('Crop button not found, retrying attempt:', attempt);
                                    setTimeout(() => monitorCropMode(attempt + 1), 500);
                                }
                            };

                            setTimeout(() => {
                                enforceCropZone();
                                monitorCropMode();
                            }, 2000);
                        } catch (err) {
                            console.error('Failed to apply crop zone:', err);
                        }
                    };
                    img.onerror = function () {
                        console.error('Failed to load image for scaling:', initialImageSrc);
                    };
                    img.src = initialImageSrc;
                } else {
                    console.log('No valid crop coordinates or frame image; enabling default crop functionality');
                    editorInstance.startDrawingMode('CROPPER');
                }
            }, 1000);

            setTimeout(() => {
                const headerButtonsContainer = editorElement.querySelector('.tui-image-editor-header-buttons');
                if (headerButtonsContainer) {
                    const downloadButton = headerButtonsContainer.querySelector('.tui-image-editor-download-btn');
                    if (downloadButton) {
                        downloadButton.remove();
                        console.log('Default Download button removed');
                    } else {
                        console.log('Download button not found');
                    }

                    const doneButton = document.createElement('button');
                    doneButton.textContent = 'Done';
                    doneButton.classList.add('tui-image-editor-done-btn');
                    doneButton.style.backgroundColor = '#fdba3b';
                    doneButton.style.color = '#fff';
                    doneButton.style.border = '0';
                    doneButton.style.padding = '';
                    doneButton.style.cursor = 'pointer';

                    headerButtonsContainer.appendChild(doneButton);
                    console.log('New Done button added');

                    doneButton.addEventListener('click', function (event) {
                        event.preventDefault();
                        event.stopPropagation();

                        doneButton.disabled = true;
                        doneButton.style.opacity = '0.5';
                        console.log('Done button disabled during processing');

                        const editedImageDataUrl = editorInstance.toDataURL();

                        getImagePixelData(editedImageDataUrl, function (editedData) {
                            const isDifferent = areImagesDifferent(originalImageData, editedData);
                            console.log('Images are different:', isDifferent);

                            if (isDifferent) {
                                $.ajax({
                                    url: myAjax.ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'save_edited_image',
                                        image_data: editedImageDataUrl,
                                        nonce: myAjax.nonce,
                                    },
                                    success: function (response) {
                                        console.log('AJAX response:', response);
                                        if (response.success) {
                                            console.log('Image saved successfully:', response.data);
                                            const newAttachmentId = response.data.attachment_id;
                                            const newImageUrl = response.data.file_url;
                                            console.log('New attachment ID:', newAttachmentId);
                                            console.log('New image URL:', newImageUrl);

                                            const currentImageThumb = imageThumbs.eq(currentImageIndex);
                                            const imageSelectionContainer = currentImageThumb.closest('.image-selection-container');
                                            const selectedImageIdsInput = imageSelectionContainer.find('.selected-image-ids');
                                            if (selectedImageIdsInput.length) {
                                                let selectedImageIds = selectedImageIdsInput.val().split(',').filter(Boolean);
                                                const index = selectedImageIds.indexOf(originalImageId);
                                                if (index !== -1) {
                                                    selectedImageIds[index] = newAttachmentId;
                                                    selectedImageIdsInput.val(selectedImageIds.join(','));
                                                    console.log('Updated selected image IDs:', selectedImageIds.join(','));
                                                } else {
                                                    console.error('Original image ID not found in selected image IDs');
                                                }
                                            } else {
                                                console.error('selected-image-ids input not found');
                                            }

                                            const parentSelectedImage = currentImageThumb.closest('.selected-image');
                                            if (parentSelectedImage.length) {
                                                parentSelectedImage.attr('data-id', newAttachmentId);
                                                console.log('Updated data-id of .selected-image to:', newAttachmentId);

                                                parentSelectedImage.find('.edit-image-btn, .preview-image-btn').remove();

                                                const editButton = $('<button>')
                                                    .addClass('edit-image-btn')
                                                    .text('Edit')
                                                    .css({
                                                        backgroundColor: '#4CAF50',
                                                        color: '#fff',
                                                        border: 'none',
                                                        padding: '5px 10px',
                                                        cursor: 'pointer',
                                                        marginTop: '5px',
                                                        display: 'block',
                                                        marginLeft: 'auto',
                                                        marginRight: 'auto'
                                                    })
                                                    .attr('data-index', currentImageIndex);
                                                parentSelectedImage.append(editButton);

                                                if (isUsingCustomRatios) {
                                                    const previewButton = $('<button>')
                                                        .addClass('preview-image-btn')
                                                        .text('Preview')
                                                        .css({
                                                            backgroundColor: '#007BFF',
                                                            color: '#fff',
                                                            border: 'none',
                                                            padding: '5px 10px',
                                                            cursor: 'pointer',
                                                            marginTop: '5px',
                                                            display: 'block',
                                                            marginLeft: 'auto',
                                                            marginRight: 'auto'
                                                        })
                                                        .attr('data-index', currentImageIndex);
                                                    parentSelectedImage.append(previewButton);
                                                }
                                            } else {
                                                console.error('Parent .selected-image not found');
                                            }

                                            if (currentImageThumb.length && newImageUrl) {
                                                currentImageThumb.attr('src', newImageUrl);
                                                console.log('Updated imageThumb src to:', newImageUrl);

                                                if (isUsingCustomRatios) {
                                                    const frameImageUrl = frameTemplate.frame_image_url;
                                                    let coordinates = frameTemplate.coordinates;
                                                    if (frameImageUrl && coordinates.x1 !== 0 && coordinates.y1 !== 0 && coordinates.x2 !== 0 && coordinates.y2 !== 0) {
                                                        console.log('Coordinates (pre-scaled to 1200px):', coordinates);

                                                        const frameImg = new Image();
                                                        frameImg.crossOrigin = 'Anonymous';
                                                        frameImg.onload = function () {
                                                            const frameWidth = frameImg.width;
                                                            const frameHeight = frameImg.height;
                                                            console.log('Frame image dimensions:', { width: frameWidth, height: frameHeight });

                                                            const previewWidth = 1200;
                                                            const unscaleFactor = frameWidth / previewWidth;
                                                            const unscaledCoordinates = {
                                                                x1: coordinates.x1 * unscaleFactor,
                                                                y1: coordinates.y1 * unscaleFactor,
                                                                x2: coordinates.x2 * unscaleFactor,
                                                                y2: coordinates.y2 * unscaleFactor,
                                                                aspect_ratio: coordinates.aspect_ratio,
                                                                frame_original_width: frameWidth,
                                                                frame_original_height: frameHeight
                                                            };

                                                            console.log('Unscaled coordinates for composite (original frame dimensions):', unscaledCoordinates);

                                                            console.log('Compositing images with frame:', frameImageUrl);
                                                            $.ajax({
                                                                url: myAjax.ajaxurl,
                                                                type: 'POST',
                                                                data: {
                                                                    action: 'composite_images',
                                                                    frame_image_url: frameImageUrl,
                                                                    edited_image_url: newImageUrl,
                                                                    coordinates: unscaledCoordinates,
                                                                    nonce: myAjax.nonce
                                                                },
                                                                success: function (compositeResponse) {
                                                                    console.log('Composite AJAX response:', compositeResponse);
                                                                    if (compositeResponse.success) {
                                                                        console.log('Composited image URL:', compositeResponse.data.composite_url);
                                                                        showPreviewModal(compositeResponse.data.composite_url);
                                                                    } else {
                                                                        console.error('Failed to composite image:', compositeResponse.data);
                                                                        alert('Failed to generate preview: ' + (compositeResponse.data || 'Unknown error'));
                                                                        showPreviewModal(newImageUrl);
                                                                    }
                                                                },
                                                                error: function (xhr, status, error) {
                                                                    console.error('Composite AJAX error:', status, error);
                                                                    console.log('Response Text:', xhr.responseText);
                                                                    alert('Failed to generate preview. Please check the server logs for more details.');
                                                                    showPreviewModal(newImageUrl);
                                                                }
                                                            });
                                                        };
                                                        frameImg.onerror = function () {
                                                            console.error('Failed to load frame image:', frameImageUrl);
                                                            alert('Failed to load frame image for compositing.');
                                                            showPreviewModal(newImageUrl);
                                                        };
                                                        frameImg.src = frameImageUrl;
                                                    } else {
                                                        console.log('Frame image or coordinates not available, skipping composite preview');
                                                        showPreviewModal(newImageUrl);
                                                    }
                                                } else {
                                                    console.log('No custom ratios received, skipping preview compositing');
                                                }
                                            } else {
                                                console.error('Failed to update imageThumb src');
                                            }
                                        } else {
                                            console.error('Failed to save image:', response.data);
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        console.error('AJAX error:', status, error);
                                    },
                                    complete: function () {
                                        if (editorInstance) {
                                            editorInstance.destroy();
                                            editorInstance = null;
                                            console.log('Editor destroyed');
                                        }
                                        editorModal.hide();
                                        console.log('Editor modal closed after Done');
                                    }
                                });
                            } else {
                                console.log('Image unchanged, skipping save');
                                if (editorInstance) {
                                    editorInstance.destroy();
                                    editorInstance = null;
                                    console.log('Editor destroyed');
                                }
                                editorModal.hide();
                                console.log('Editor modal closed after Done (no changes)');
                            }
                        });
                    });
                } else {
                    console.log('Header buttons container not found');
                }
            }, 500);
            console.log('Editor instance created:', editorInstance);
        } else {
            console.log('Editor element not found');
        }
    }

    $('#close-editor-modal').on('click', function () {
        if (editorInstance) {
            editorInstance.destroy();
            editorInstance = null;
            console.log('Editor destroyed');
        }
        editorModal.hide();
    });

    $(window).on('click', function (event) {
        if ($(event.target).is(modal)) {
            currentGallery = null;
            modal.hide();
            body.css('overflow', '');
        }
    });

    modal.find('input').on('change', function () {
        const imageId = $(this).val();
        const imageUrl = $(this).siblings('img').attr('src');
        const imageTitle = $(this).siblings('img').attr('alt');
        hiddenImagesInput = currentGallery.closest('.image-selection-container').find('.selected-image-ids');
        let selectedImageIds = hiddenImagesInput.val().split(',').filter(Boolean);

        if ($(this).prop('checked')) {
            if ($(this).data('galleryoption') === 'single_image' && selectedImageIds.length >= max_images && max_images != -1) {
                $(this).prop('checked', false);
                alert('You can only select ' + max_images + ' images here.');
                return;
            }
            if ($(this).data('galleryoption') === 'multiple_images' && selectedImageIds.length >= max_images && max_images != -1) {
                $(this).prop('checked', false);
                alert('You can only select ' + max_images + ' images.');
                return;
            }

            selectedImageIds.push(imageId);
            hiddenImagesInput.val(selectedImageIds.join(','));

            currentGallery.closest('.image-selection-container').find('.selected-images-container').append(
                `<div class="selected-image" data-id="${imageId}">
                    <img src="${imageUrl}" alt="${imageTitle}" class="selected-image-thumb">
                    <span class="remove-image" style="color:red; position: absolute; top: -5px; right: -5px; cursor: pointer;">×</span>
                </div>`
            );
        } else {
            selectedImageIds = selectedImageIds.filter(id => id != imageId);
            hiddenImagesInput.val(selectedImageIds.join(','));

            currentGallery.closest('.image-selection-container').find(`.selected-images-container .selected-image[data-id="${imageId}"]`).remove();
        }

        updateSelectedCount();
    });

    $('.image-selection-container').on('click', '.remove-image', function () {
        const imageId = $(this).parent().data('id');
        hiddenImagesInput = $(this).closest('.image-selection-container').find('.selected-image-ids');
        let selectedImageIds = hiddenImagesInput.val().split(',').filter(Boolean);

        selectedImageIds = selectedImageIds.filter(id => id != imageId);
        hiddenImagesInput.val(selectedImageIds.join(','));

        const count = selectedImageIds.length;
        $(this).closest('.image-selection-container').find('.view-gallery-btn').text(`Select Photos (${count})`);
        $(this).closest('.selected-image').remove();
        $(document).trigger('singleQtyChange', count);

        $(this).siblings('.edit-image-btn, .preview-image-btn').remove();
    });

    function updateSelectedCount() {
        hiddenImagesInput = currentGallery.closest('.image-selection-container').find('.selected-image-ids');
        const count = hiddenImagesInput.val().split(',').filter(Boolean).length;
        currentGallery.text(`Select Photos (${count})`);
        $(document).trigger('singleQtyChange', count);
    }

    $('form.cart').on('submit', function (e) {
        var selectedImagesForCart = {};

        $('.selected-image-ids').each(function () {
            var productId = $(this).closest('.image-selection-container').data('product');
            var imageIds = $(this).val().split(',').filter(Boolean);

            if (!selectedImagesForCart[productId]) {
                selectedImagesForCart[productId] = [];
            }

            selectedImagesForCart[productId].push(imageIds);
        });

        console.log(selectedImagesForCart);
        console.log(JSON.stringify(selectedImagesForCart));
        console.log(Object.keys(selectedImagesForCart).length > 0);

        if (Object.keys(selectedImagesForCart).length > 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'selected_gallery_images',
                value: JSON.stringify(selectedImagesForCart)
            }).appendTo('form.cart');
        }
    });

    $('form.carsdfdft').on('submit', function (e) {
        var groupedImagesForCart = {};

        $('.selected-image-ids').each(function () {
            var productId = $(this).closest('.image-selection-container').data('product');
            var imageIds = $(this).val().split(',').filter(Boolean);

            if (!groupedImagesForCart[productId]) {
                groupedImagesForCart[productId] = [];
            }

            groupedImagesForCart[productId].push(imageIds);
        });

        console.log(groupedImagesForCart);
        console.log(JSON.stringify(groupedImagesForCart));

        if (Object.keys(groupedImagesForCart).length > 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'selected_gallery_images',
                value: JSON.stringify(groupedImagesForCart)
            }).appendTo('form.cart');
        }
    });
});