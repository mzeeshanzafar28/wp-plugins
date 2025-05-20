jQuery(document).ready(function ($) {
    const galleryButton = $('.view-gallery-btn');
    const modal = $('#gallery-modal');
    const closeModal = $('#close-modal');
    const body = $('body');
    let selectedImages = [];
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
    let isMultipleImageProduct = false;
    let updatedCropCoordinates = null;
    const productImage = $('.woocommerce-product-gallery__image img');
    const galleryOption = $('#gallery-option').val() || 'single_image';
    let hiddenImagesInput = null;
    let updateProductImageTimeout = null; // For debouncing

    function checkCustomRatios() {
        frameTemplate = myAjax.frame_template || {};
        const coordinates = frameTemplate.coordinates || (galleryOption === 'single_image' ? { x1: 0, y1: 0, x2: 0, y2: 0, aspect_ratio: 0 } : []);
        hasFrameImage = frameTemplate.frame_image_url && frameTemplate.frame_image_url.trim() !== '';
        isMultipleImageProduct = Array.isArray(coordinates) && coordinates.length > 1;
        isUsingCustomRatios = hasFrameImage && (isMultipleImageProduct ? coordinates.some(coord => coord && coord.x1 != null && coord.aspect_ratio > 0) : (coordinates.x1 != null && coordinates.aspect_ratio > 0));
        console.log('Is multiple image product:', isMultipleImageProduct);
        console.log('Is using custom ratios:', isUsingCustomRatios);
    }

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

    function updateProductImage(newImageUrl = null) {
        // Debounce the function to prevent multiple calls in quick succession
        if (updateProductImageTimeout) {
            clearTimeout(updateProductImageTimeout);
        }

        updateProductImageTimeout = setTimeout(() => {
            checkCustomRatios();

            if (!hasFrameImage || !frameTemplate.coordinates) {
                console.warn('Skipping updateProductImage: Missing frame data');
                return;
            }

            const galleryImages = $('.gallery-images input[type="checkbox"]').map(function () {
                return $(this).siblings('img').attr('src');
            }).get();

            const selectedImages = $('.selected-image-thumb').map(function () {
                return $(this).attr('src');
            }).get();

            const coordinates = isMultipleImageProduct ? frameTemplate.coordinates : [frameTemplate.coordinates];
            const requiredImages = coordinates.length;

            const promises = [];
            let imagesToProcess = [];

            if (isMultipleImageProduct) {
                // For multiple image products:
                // - If there are selected images, use them (up to requiredImages)
                // - Otherwise, on page load, use the first x gallery images
                if (selectedImages.length > 0) {
                    imagesToProcess = selectedImages.slice(0, requiredImages);
                } else {
                    imagesToProcess = galleryImages.slice(0, requiredImages);
                }

                imagesToProcess.forEach((imageUrl, index) => {
                    const coord = coordinates[index] || {};
                    const aspectRatio = coord.aspect_ratio || 1;
                    promises.push(new Promise((resolve, reject) => {
                        cropImageFromCenter(imageUrl, aspectRatio, (cropError, croppedDataUrl) => {
                            if (cropError) reject(cropError);
                            else {
                                $.ajax({
                                    url: myAjax.ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'save_edited_image',
                                        image_data: croppedDataUrl,
                                        nonce: myAjax.nonce
                                    },
                                    success: (response) => response.success ? resolve(response.data.file_url) : reject('Failed to save cropped image'),
                                    error: () => reject('AJAX error')
                                });
                            }
                        });
                    }));
                });
            } else {
                // For single image products:
                // - If newImageUrl is provided (from selection or edit), use it
                // - Otherwise, on page load, use the first gallery image
                const imageUrlToUse = newImageUrl || (selectedImages.length > 0 ? selectedImages[selectedImages.length - 1] : (galleryImages.length > 0 ? galleryImages[0] : null));
                if (imageUrlToUse) {
                    const coord = coordinates[0] || {};
                    const aspectRatio = coord.aspect_ratio || 1;
                    promises.push(new Promise((resolve, reject) => {
                        cropImageFromCenter(imageUrlToUse, aspectRatio, (cropError, croppedDataUrl) => {
                            if (cropError) reject(cropError);
                            else {
                                $.ajax({
                                    url: myAjax.ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'save_edited_image',
                                        image_data: croppedDataUrl,
                                        nonce: myAjax.nonce
                                    },
                                    success: (response) => response.success ? resolve(response.data.file_url) : reject('Failed to save cropped image'),
                                    error: () => reject('AJAX error')
                                });
                            }
                        });
                    }));
                }
            }

            if (promises.length === 0) {
                const unscaledCoordinates = coordinates.map(coord => ({
                    x1: coord.x1 || 0,
                    y1: coord.y1 || 0,
                    x2: coord.x2 || 0,
                    y2: coord.y2 || 0,
                    aspect_ratio: coord.aspect_ratio || 1,
                    frame_original_width: frameTemplate.frame_width || 1200,
                    frame_original_height: frameTemplate.frame_height || 800
                }));

                $.ajax({
                    url: myAjax.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'composite_images',
                        nonce: myAjax.nonce,
                        edited_image_urls: Array(requiredImages).fill(''),
                        coordinates: unscaledCoordinates,
                        frame_image_url: frameTemplate.frame_image_url
                    },
                    success: function (response) {
                        if (response.success && response.composite_url) {
                            const compositeUrl = response.composite_url;
                            console.log('Composite URL received (no images): ' + compositeUrl);
                            updateThumbnail(compositeUrl);
                        } else {
                            console.error('Failed to get composite URL or invalid response structure:', response);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error details:', { status, error, responseText: xhr.responseText });
                    }
                });
                return;
            }

            $.when.apply($, promises).then(function () {
                const croppedImageUrls = Array.prototype.slice.call(arguments);
                while (croppedImageUrls.length < requiredImages) {
                    croppedImageUrls.push('');
                }

                const unscaledCoordinates = coordinates.map(coord => ({
                    x1: coord.x1 || 0,
                    y1: coord.y1 || 0,
                    x2: coord.x2 || 0,
                    y2: coord.y2 || 0,
                    aspect_ratio: coord.aspect_ratio || 1,
                    frame_original_width: frameTemplate.frame_width || 1200,
                    frame_original_height: frameTemplate.frame_height || 800
                }));

                $.ajax({
                    url: myAjax.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'composite_images',
                        nonce: myAjax.nonce,
                        edited_image_urls: croppedImageUrls,
                        coordinates: unscaledCoordinates,
                        frame_image_url: frameTemplate.frame_image_url
                    },
                    success: function (response) {
                        if (response.success && response.composite_url) {
                            const compositeUrl = response.composite_url;
                            console.log('Composite URL received: ' + compositeUrl);
                            updateThumbnail(compositeUrl);
                        } else {
                            console.error('Failed to get composite URL or invalid response structure:', response);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error details:', { status, error, responseText: xhr.responseText });
                    }
                });
            }).fail(function (error) {
                console.error('Error in image processing:', error);
            });
        }, 100); // Debounce delay of 100ms
    }

    function updateThumbnail(compositeUrl) {
        productImage.attr({
            src: compositeUrl,
            srcset: compositeUrl,
            'data-src': compositeUrl,
            'data-large_image': compositeUrl,
            'data-large_image_width': productImage.data('large_image_width') || 510,
            'data-large_image_height': productImage.data('large_image_height') || 595
        });
        const tempImg = new Image();
        tempImg.src = compositeUrl;
        tempImg.onload = function () {
            if (typeof wc_single_product_params !== 'undefined') {
                $(document).trigger('wc_update_product_image', [tempImg, productImage.parent().find('a')]);
            } else {
                productImage.css('opacity', 0).attr('src', compositeUrl).css('opacity', 1);
            }
        };
        console.log('Thumbnail updated with composite: ' + compositeUrl);
    }

    function saveUpdatedCoordinates(updatedCoords) {
        const productId = $('.image-selection-container').data('product');
        $.ajax({
            url: myAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_frame_template_coordinates',
                nonce: myAjax.nonce,
                product_id: productId,
                coordinates: updatedCoords
            },
            success: function (response) {
                if (response.success) {
                    console.log('Frame template coordinates updated successfully:', response.data);
                    frameTemplate.coordinates = updatedCoords;
                } else {
                    console.error('Failed to update frame template coordinates:', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error while saving frame template coordinates:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
            }
        });
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
            $parent.find('.edit-image-btn').remove();

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
        });

        if (hasFrameImage) {
            updateProductImage();
        }
    });

    $(document).on('click', '.edit-image-btn', function () {
        currentImageIndex = parseInt($(this).attr('data-index'), 10);
        console.log('Editing image at index:', currentImageIndex);
        updatedCropCoordinates = null;
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

            editorInstance.on('crop', function (event) {
                updatedCropCoordinates = {
                    x1: event.x,
                    y1: event.y,
                    x2: event.x + event.width,
                    y2: event.y + event.height,
                    aspect_ratio: event.width / event.height
                };
                console.log('Updated crop coordinates:', updatedCropCoordinates);
            });

            setTimeout(() => {
                frameTemplate = myAjax.frame_template || {};
                let coordinates = frameTemplate.coordinates || (galleryOption === 'single_image' ? { x1: 0, y1: 0, x2: 0, y2: 0, aspect_ratio: 0 } : []);
                hasFrameImage = frameTemplate.frame_image_url && frameTemplate.frame_image_url.trim() !== '';

                let cropCoords = isMultipleImageProduct ? (coordinates[currentImageIndex] || coordinates[0] || { x1: 0, y1: 0, x2: 0, y2: 0, aspect_ratio: 0 }) : coordinates;

                const hasValidAspectRatio = parseFloat(cropCoords.aspect_ratio) > 0;
                const hasValidCoordinates = cropCoords.x1 != 0 || cropCoords.y1 != 0 || cropCoords.x2 != 0 || cropCoords.y2 != 0;
                isUsingCustomRatios = hasFrameImage && hasValidCoordinates && hasValidAspectRatio;

                if (isUsingCustomRatios) {
                    const canvasSize = editorInstance.getCanvasSize();
                    const img = new Image();
                    img.onload = function () {
                        const originalWidth = img.width;
                        const originalHeight = img.height;
                        const scaleX = canvasSize.width / originalWidth;
                        const scaleY = canvasSize.height / originalHeight;

                        let scaledX1 = (cropCoords.x1 || 0) * scaleX;
                        let scaledY1 = (cropCoords.y1 || 0) * scaleY;
                        let scaledX2 = (cropCoords.x2 || 0) * scaleX;
                        let scaledY2 = (cropCoords.y2 || 0) * scaleY;
                        let cropWidth = scaledX2 - scaledX1;
                        let cropHeight = scaledY2 - scaledY1;
                        lockedAspectRatio = cropCoords.aspect_ratio || 1;

                        if (scaledX2 > canvasSize.width || scaledY2 > canvasSize.height || scaledX1 < 0 || scaledY1 < 0) {
                            const maxWidth = canvasSize.width;
                            const maxHeight = canvasSize.height;
                            const scaleToFit = Math.min(maxWidth / cropWidth, maxHeight / cropHeight);
                            cropWidth = cropWidth * scaleToFit;
                            cropHeight = cropWidth / lockedAspectRatio;
                            scaledX1 = (canvasSize.width - cropWidth) / 2;
                            scaledY1 = (canvasSize.height - cropHeight) / 2;
                            scaledX2 = scaledX1 + cropWidth;
                            scaledY2 = scaledY1 + cropHeight;
                        }

                        editorInstance.startDrawingMode('CROPPER');
                        const fabricCanvas = editorInstance._graphics._canvas;
                        if (fabricCanvas) {
                            const cropZoneObject = fabricCanvas.getObjects().find(obj => obj.type === 'cropzone');
                            if (cropZoneObject) {
                                cropZoneObject.set({
                                    left: scaledX1 + cropWidth / 2,
                                    top: scaledY1 + cropHeight / 2,
                                    width: cropWidth,
                                    height: cropHeight,
                                    scaleX: 1,
                                    scaleY: 1,
                                    lockUniScaling: true,
                                    uniformScaling: true,
                                    lockRotation: true,
                                    lockScalingFlip: true,
                                    selectable: true,
                                    evented: true
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
                                        if (Math.abs(currentWidth / currentHeight - lockedAspectRatio) > 0.01) {
                                            cropZoneObject.set({
                                                height: currentWidth / lockedAspectRatio / cropZoneObject.scaleX,
                                                scaleY: cropZoneObject.scaleX
                                            });
                                            fabricCanvas.renderAll();
                                        }
                                    }
                                });

                                cropZoneObject.on('moving', () => {
                                    const imageObject = fabricCanvas.getObjects().find(obj => obj.type === 'image');
                                    if (imageObject) {
                                        const imageWidth = imageObject.width * imageObject.scaleX;
                                        const imageHeight = imageObject.height * imageObject.scaleY;
                                        const imageLeft = imageObject.left - (imageWidth / 2);
                                        const imageTop = imageObject.top - (imageHeight / 2);
                                        const imageRight = imageObject.left + (imageWidth / 2);
                                        const imageBottom = imageObject.top + (imageHeight / 2);
                                        const cropZoneWidth = cropZoneObject.width * cropZoneObject.scaleX;
                                        const cropZoneHeight = cropZoneObject.height * cropZoneObject.scaleY;
                                        const actualLeft = cropZoneObject.left - (cropZoneWidth / 2);
                                        const actualTop = cropZoneObject.top - (cropZoneHeight / 2);
                                        const actualRight = cropZoneObject.left + (cropZoneWidth / 2);
                                        const actualBottom = cropZoneObject.top + (cropZoneHeight / 2);

                                        if (actualLeft < imageLeft) cropZoneObject.set({ left: imageLeft + (cropZoneWidth / 2) });
                                        if (actualTop < imageTop) cropZoneObject.set({ top: imageTop + (cropZoneHeight / 2) });
                                        if (actualRight > imageRight) cropZoneObject.set({ left: imageRight - (cropZoneWidth / 2) });
                                        if (actualBottom > imageBottom) cropZoneObject.set({ top: imageBottom - (cropZoneHeight / 2) });
                                        fabricCanvas.renderAll();
                                    }
                                });

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
                                                const cropZoneObject = fabricCanvas.getObjects().find(obj => obj.type === 'cropzone');
                                                if (cropZoneObject) {
                                                    cropZoneObject.set({
                                                        left: scaledX1 + cropWidth / 2,
                                                        top: scaledY1 + cropHeight / 2,
                                                        width: cropWidth,
                                                        height: cropHeight,
                                                        scaleX: 1,
                                                        scaleY: 1,
                                                        lockUniScaling: true,
                                                        uniformScaling: true,
                                                        lockRotation: true,
                                                        lockScalingFlip: true,
                                                        selectable: true,
                                                        evented: true
                                                    });
                                                    fabricCanvas.renderAll();
                                                }
                                            }, 200);
                                        });
                                    } else if (attempt < maxAttempts) {
                                        console.log('Crop button not found, retrying attempt:', attempt);
                                        setTimeout(() => monitorCropMode(attempt + 1), 500);
                                    }
                                };
                                monitorCropMode();

                                fabricCanvas.renderAll();
                            }
                        }
                    };
                    img.onerror = function () {
                        console.error('Failed to load image for editor scaling:', initialImageSrc);
                    };
                    img.src = initialImageSrc;
                } else {
                    editorInstance.startDrawingMode('CROPPER');
                }
            }, 1000);

            setTimeout(() => {
                const headerButtonsContainer = editorElement.querySelector('.tui-image-editor-header-buttons');
                if (headerButtonsContainer) {
                    const downloadButton = headerButtonsContainer.querySelector('.tui-image-editor-download-btn');
                    if (downloadButton) downloadButton.remove();

                    const doneButton = document.createElement('button');
                    doneButton.textContent = 'Done';
                    doneButton.classList.add('tui-image-editor-done-btn');
                    doneButton.style.backgroundColor = '#fdba3b';
                    doneButton.style.color = '#fff';
                    doneButton.style.border = '0';
                    doneButton.style.cursor = 'pointer';
                    headerButtonsContainer.appendChild(doneButton);

                    doneButton.addEventListener('click', function (event) {
                        event.preventDefault();
                        doneButton.disabled = true;
                        doneButton.style.opacity = '0.5';

                        if (editorInstance.getDrawingMode() === 'CROPPER') {
                            editorInstance.crop(editorInstance.getCropzoneRect()).then(() => {
                                processImageAfterCrop();
                            }).catch(() => {
                                processImageAfterCrop();
                            });
                        } else {
                            processImageAfterCrop();
                        }

                        function processImageAfterCrop() {
                            const editedImageDataUrl = editorInstance.toDataURL();
                            getImagePixelData(editedImageDataUrl, function (editedData) {
                                if (areImagesDifferent(originalImageData, editedData)) {
                                    $.ajax({
                                        url: myAjax.ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'save_edited_image',
                                            image_data: editedImageDataUrl,
                                            nonce: myAjax.nonce,
                                        },
                                        success: function (response) {
                                            if (response.success) {
                                                const newAttachmentId = response.data.attachment_id;
                                                const newImageUrl = response.data.file_url;
                                                const currentImageThumb = imageThumbs.eq(currentImageIndex);
                                                const selectedImageIdsInput = currentImageThumb.closest('.image-selection-container').find('.selected-image-ids');
                                                let selectedImageIds = selectedImageIdsInput.val().split(',').filter(Boolean);
                                                const index = selectedImageIds.indexOf(originalImageId);
                                                if (index !== -1) {
                                                    selectedImageIds[index] = newAttachmentId;
                                                    selectedImageIdsInput.val(selectedImageIds.join(','));
                                                }

                                                const parentSelectedImage = currentImageThumb.closest('.selected-image');
                                                parentSelectedImage.attr('data-id', newAttachmentId);
                                                parentSelectedImage.find('.edit-image-btn').remove();

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

                                                currentImageThumb.attr('src', newImageUrl);

                                                if (hasFrameImage) {
                                                    // Update the composite image with the newly edited image
                                                    if (isMultipleImageProduct) {
                                                        updateProductImage(); // For multiple images, use all selected images
                                                    } else {
                                                        updateProductImage(newImageUrl); // For single image, use the edited image
                                                    }
                                                }
                                            } else {
                                                console.error('Failed to save edited image:', response);
                                            }
                                        },
                                        error: function (xhr, status, error) {
                                            console.error('AJAX error saving edited image:', { status, error, responseText: xhr.responseText });
                                        },
                                        complete: function () {
                                            editorInstance.destroy();
                                            editorInstance = null;
                                            editorModal.hide();
                                        }
                                    });
                                } else {
                                    editorInstance.destroy();
                                    editorInstance = null;
                                    editorModal.hide();
                                }
                            });
                        }
                    });
                }
            }, 500);
        }
    }

    $('#close-editor-modal').on('click', function () {
        if (editorInstance) {
            editorInstance.destroy();
            editorInstance = null;
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
        if (hasFrameImage && $(this).data('galleryoption') === 'single_image') {
            updateProductImage(imageUrl); // Pass the newly selected image URL for single image products
        } else if (hasFrameImage) {
            updateProductImage(); // Use selected images for multiple image products
        }
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

        checkCustomRatios();
        if (hasFrameImage) {
            updateProductImage();
        }
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

        if (Object.keys(selectedImagesForCart).length > 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'selected_gallery_images',
                value: JSON.stringify(selectedImagesForCart)
            }).appendTo('form.cart');
        }
    });

    if (!$('.image-selection-container').length) {
        console.warn('Image selection container not found. Gallery script initialization skipped.');
    }

    if (!myAjax || !myAjax.ajaxurl || !myAjax.nonce) {
        console.error('myAjax object is not properly initialized. Check wp_localize_script.');
    }

    // Initialize thumbnail update on page load if frame exists
    checkCustomRatios();
    if (hasFrameImage) {
        updateProductImage();
    }
});