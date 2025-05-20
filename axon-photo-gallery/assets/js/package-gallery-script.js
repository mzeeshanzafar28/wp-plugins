jQuery(document).ready(function ($) {
    console.log('from_package_script: package-gallery-script.js loaded');
    const subProductSettings = packageAjax.sub_product_settings || {};
    console.log('from_package_script: Step 1 - All subProductSettings:', subProductSettings);
    const settingsMap = {};
    const hasCustomRatiosMap = {};

    Object.keys(subProductSettings).forEach(productId => {
        const settings = subProductSettings[productId];
        console.log(`from_package_script: Step 2 - Setting for product ${productId}:`, settings);

        const frameTemplate = settings || {};
        const galleryOption = frameTemplate.gallery_option || 'single_image';
        const coordinates = frameTemplate.coordinates || (galleryOption === 'single_image' ? { x1: 0, y1: 0, x2: 0, y2: 0, aspect_ratio: 1 } : []);
        const hasFrameImage = frameTemplate.frame_image_url && frameTemplate.frame_image_url.trim() !== '';

        let hasValidAspectRatio = false;
        let hasValidCoordinates = false;

        if (galleryOption === 'single_image') {
            hasValidAspectRatio = coordinates && typeof coordinates === 'object' && parseFloat(coordinates.aspect_ratio) > 0;
            hasValidCoordinates = coordinates && (parseFloat(coordinates.x1) !== 0 || parseFloat(coordinates.y1) !== 0 || parseFloat(coordinates.x2) !== 0 || parseFloat(coordinates.y2) !== 0) || (parseFloat(coordinates.x1) === 0 && parseFloat(coordinates.y1) === 0 && parseFloat(coordinates.x2) === 0 && parseFloat(coordinates.y2) === 0);
            hasCustomRatiosMap[productId] = hasFrameImage && hasValidAspectRatio && hasValidCoordinates;
        } else if (galleryOption === 'multiple_images') {
            hasValidCoordinates = Array.isArray(coordinates) && coordinates.length > 0 && coordinates.some(coord =>
                coord && typeof coord === 'object' && (parseFloat(coord.x1) !== 0 || parseFloat(coord.y1) !== 0 || parseFloat(coord.x2) !== 0 || parseFloat(coord.y2) !== 0)
            );
            hasValidAspectRatio = Array.isArray(coordinates) && coordinates.length > 0 && coordinates.every(coord =>
                coord && typeof coord === 'object' && parseFloat(coord.aspect_ratio) > 0
            );
            hasCustomRatiosMap[productId] = hasFrameImage && hasValidAspectRatio && hasValidCoordinates;
        }

        console.log(`from_package_script: Product ${productId} has custom ratios:`, hasCustomRatiosMap[productId]);
        console.log(`Product ${productId} - hasFrameImage: ${hasFrameImage}, hasValidAspectRatio: ${hasValidAspectRatio}, hasValidCoordinates: ${hasValidCoordinates}`);

        settingsMap[productId] = {
            frameTemplate: settings,
            hasFrameImage: hasFrameImage,
            galleryOption: galleryOption
        };
    });
    console.log('from_package_script: Step 2 - Settings map created:', settingsMap);
    console.log('from_package_script: Step 2 - Custom ratios map created:', hasCustomRatiosMap);

    const galleryButton = $('.view-gallery-btn');
    const modal = $('#gallery-modal');
    const closeModal = $('#close-modal');
    const body = $('body');
    let selectedImages = [];
    let maxImages = -1;
    let hiddenImagesInput = null;
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
    let currentGalleryButton = null;

    function cropImageFromCenter(imageUrl, targetWidth, targetHeight, callback) {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.onload = function () {
            const originalWidth = img.width;
            const originalHeight = img.height;
            console.log('Original image dimensions for cropping:', { width: originalWidth, height: originalHeight });

            const targetAspectRatio = targetWidth / targetHeight;
            let cropWidth, cropHeight;

            const imageAspectRatio = originalWidth / originalHeight;
            if (imageAspectRatio > targetAspectRatio) {
                cropHeight = originalHeight;
                cropWidth = cropHeight * targetAspectRatio;
            } else {
                cropWidth = originalWidth;
                cropHeight = cropWidth / targetAspectRatio;
            }

            const cropX = (originalWidth - cropWidth) / 2;
            const cropY = (originalHeight - cropHeight) / 2;

            console.log('Crop dimensions before resize:', { width: cropWidth, height: cropHeight });
            console.log('Crop position (centered):', { x: cropX, y: cropY });

            if (cropWidth <= 0 || cropHeight <= 0 || targetWidth <= 0 || targetHeight <= 0) {
                console.error('Invalid dimensions:', { cropWidth, cropHeight, targetWidth, targetHeight });
                callback(new Error('Invalid crop or target dimensions'), null);
                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = targetWidth;
            canvas.height = targetHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, cropX, cropY, cropWidth, cropHeight, 0, 0, targetWidth, targetHeight);

            const croppedImageDataUrl = canvas.toDataURL('image/png');
            console.log('Cropped image data URL generated with dimensions:', { width: targetWidth, height: targetHeight });
            callback(null, croppedImageDataUrl);
        };
        img.onerror = function () {
            console.error('Failed to load image for cropping:', imageUrl);
            callback(new Error('Failed to load image for cropping'), null);
        };
        img.src = imageUrl;
    }

    function showPreviewModal(compositeImageUrl, imageIndex) {
        if (!$('#preview-modal').length) {
            const previewModalHtml = `
                <div id="preview-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
                    <div id="preview-modal-content" style="background: white; padding: 20px; border-radius: 5px; position: relative; max-width: 90%; max-height: 90%;">
                        <span id="preview-modal-close" style="position: absolute; top: 10px; right: 10px; font-size: 24px; cursor: pointer;">×</span>
                        <h2>Image Preview</h2>
                        <img id="preview-image" src="" alt="Composited Image" style="max-width: 100%; max-height: 80vh;" />
                    </div>
                </div>
            `;
            $('body').append(previewModalHtml);
        }

        $('#preview-image').attr('src', compositeImageUrl);
        $('#preview-modal').css('display', 'flex');
        body.css('overflow', 'hidden');

        $('#preview-modal-close').off('click').on('click', function () {
            $('#preview-modal').css('display', 'none');
            body.css('overflow', '');
            const previewButton = $(`.preview-image-btn[data-product-id="${imageIndex.productId}"][data-index="${imageIndex.index}"]`);
            previewButton.prop('disabled', false).css('opacity', '1');
            console.log(`Preview button for image index ${imageIndex.index} re-enabled`);
        });

        $('#preview-modal').off('click').on('click', function (e) {
            if ($(e.target).is('#preview-modal')) {
                $('#preview-modal').css('display', 'none');
                body.css('overflow', '');
                const previewButton = $(`.preview-image-btn[data-product-id="${imageIndex.productId}"][data-index="${imageIndex.index}"]`);
                previewButton.prop('disabled', false).css('opacity', '1');
                console.log(`Preview button for image index ${imageIndex.index} re-enabled`);
            }
        });
    }

    galleryButton.on('click', function () {
        currentGalleryButton = $(this);
        hiddenImagesInput = currentGalleryButton.closest('.image-selection-container').find('.selected-image-ids');
        let selectedImageIds = hiddenImagesInput.val().split(',').filter(Boolean);
        let galleryOption = hiddenImagesInput.data("galleryoption") || 'single_image';
        maxImages = parseInt(hiddenImagesInput.data("max_images"), 10) || -1;
        const productId = currentGalleryButton.closest('.image-selection-container').data('product');

        if (galleryOption === 'multiple_images' && settingsMap[productId] && settingsMap[productId].frameTemplate.coordinates) {
            maxImages = settingsMap[productId].frameTemplate.coordinates.length;
            console.log(`Overridden maxImages for Product ${productId} to ${maxImages} based on frame coordinates`);
        }

        modal.find('input[type="checkbox"]').prop('checked', false);
        modal.find('input').data('galleryoption', galleryOption);
        modal.find('input').data('max_images', maxImages);
        modal.find('input[type="checkbox"]').each(function () {
            if (selectedImageIds.includes($(this).val())) {
                $(this).prop('checked', true);
            }
        });

        modal.css('display', 'block');
        body.css('overflow', 'hidden');
    });

    closeModal.on('click', function () {
        modal.css('display', 'none');
        body.css('overflow', '');

        imageThumbs = $('.selected-image-thumb');
        currentImageIndex = 0;

        $('.image-selection-container').each(function () {
            const $container = $(this);
            const productId = $container.data('product');
            const galleryOption = $container.find('.selected-image-ids').data('galleryoption') || 'single_image';
            const $selectedImages = $container.find('.selected-images-container .selected-image');
            const selectedImageIds = $container.find('.selected-image-ids').val().split(',').filter(Boolean);

            $selectedImages.find('.edit-image-btn, .preview-image-btn').remove();
            $container.find('.preview-all-btn').remove();

            $selectedImages.each(function (index) {
                const $parent = $(this);
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
                    .attr('data-product-id', productId)
                    .attr('data-index', index);
                $parent.append(editButton);
                console.log(`Appended Edit Button for Product ${productId}, Index ${index}`);

                if (galleryOption === 'single_image' && hasCustomRatiosMap[productId]) {
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
                        .attr('data-product-id', productId)
                        .attr('data-index', index);
                    $parent.append(previewButton);
                    console.log(`Appended Preview Button for Product ${productId}, Index ${index}`);
                }
            });

            if (galleryOption === 'multiple_images' && hasCustomRatiosMap[productId] && selectedImageIds.length > 0) {
                const previewAllButton = $('<button>')
                    .addClass('preview-all-btn')
                    .text('Preview All')
                    .css({
                        backgroundColor: '#FF5733',
                        color: '#fff',
                        border: 'none',
                        padding: '5px 10px',
                        cursor: 'pointer',
                        marginTop: '5px',
                        display: 'block',
                        marginLeft: 'auto',
                        marginRight: 'auto'
                    })
                    .attr('data-product-id', productId);
                $container.find('.selected-images-container').after(previewAllButton);
                console.log(`Appended Preview All Button for Product ${productId} after image selection`);
            }
        });
    });

    $(document).on('click', '.preview-image-btn', function () {
        const productId = $(this).attr('data-product-id');
        currentImageIndex = parseInt($(this).attr('data-index'), 10);
        console.log(`Previewing image at index: ${currentImageIndex} for product: ${productId}`);

        const previewButton = $(this);
        previewButton.prop('disabled', true).css('opacity', '0.5');
        console.log(`Preview button for image index ${currentImageIndex} disabled`);

        // Select the image thumb from the same product container
        const $container = $(this).closest('.image-selection-container');
        const currentImageThumb = $container.find('.selected-image-thumb').eq(currentImageIndex);
        const imageUrl = currentImageThumb.attr('src');

        frameTemplate = settingsMap[productId].frameTemplate || {};
        hasFrameImage = frameTemplate.frame_image_url && frameTemplate.frame_image_url.trim() !== '';
        const frameImageUrl = frameTemplate.frame_image_url;
        let coordinates = frameTemplate.coordinates || { x1: 0, y1: 0, x2: 0, y2: 0, aspect_ratio: 1 };

        if (Array.isArray(coordinates)) {
            coordinates = coordinates[currentImageIndex] || { x1: 0, y1: 0, x2: 0, y2: 0, aspect_ratio: 1 };
        }

        console.log('Frame image URL from frameTemplate:', frameImageUrl);
        console.log('Coordinates:', coordinates);

        if (!hasFrameImage || !coordinates.aspect_ratio || parseFloat(coordinates.aspect_ratio) <= 0) {
            console.warn(`No frame image or invalid aspect ratio for Product ${productId}. Showing original image.`);
            showPreviewModal(imageUrl, { productId, index: currentImageIndex });
            previewButton.prop('disabled', false).css('opacity', '1');
            return;
        }

        const frameImg = new Image();
        frameImg.onload = function () {
            const frameWidth = frameTemplate.frame_width || frameImg.width;
            const frameHeight = frameTemplate.frame_height || frameImg.height;

            let finalCoordinates = { ...coordinates };
            let targetWidth = (coordinates.x2 || 0) - (coordinates.x1 || 0);
            let targetHeight = (coordinates.y2 || 0) - (coordinates.y1 || 0);

            if (targetWidth <= 0 || targetHeight <= 0) {
                console.log(`Coordinates for Product ${productId} result in invalid dimensions (width: ${targetWidth}, height: ${targetHeight}). Computing default area.`);

                const targetAspectRatio = parseFloat(coordinates.aspect_ratio);
                const maxWidth = frameWidth;
                const maxHeight = frameHeight;
                const frameAspectRatio = maxWidth / maxHeight;

                if (targetAspectRatio > frameAspectRatio) {
                    targetHeight = maxHeight * 0.8;
                    targetWidth = targetHeight * targetAspectRatio;
                    if (targetWidth > maxWidth) {
                        targetWidth = maxWidth * 0.8;
                        targetHeight = targetWidth / targetAspectRatio;
                    }
                } else {
                    targetWidth = maxWidth * 0.8;
                    targetHeight = targetWidth / targetAspectRatio;
                    if (targetHeight > maxHeight) {
                        targetHeight = maxHeight * 0.8;
                        targetWidth = targetHeight * targetAspectRatio;
                    }
                }

                const x1 = (maxWidth - targetWidth) / 2;
                const y1 = (maxHeight - targetHeight) / 2;
                const x2 = x1 + targetWidth;
                const y2 = y1 + targetHeight;

                finalCoordinates = {
                    x1: x1,
                    y1: y1,
                    x2: x2,
                    y2: y2,
                    aspect_ratio: targetAspectRatio,
                    frame_original_width: frameWidth,
                    frame_original_height: frameHeight
                };
            } else {
                finalCoordinates = {
                    x1: coordinates.x1 || 0,
                    y1: coordinates.y1 || 0,
                    x2: coordinates.x2 || 0,
                    y2: coordinates.y2 || 0,
                    aspect_ratio: coordinates.aspect_ratio || 1,
                    frame_original_width: frameWidth,
                    frame_original_height: frameHeight
                };
                targetWidth = finalCoordinates.x2 - finalCoordinates.x1;
                targetHeight = finalCoordinates.y2 - finalCoordinates.y1;
            }

            console.log('Final coordinates for compositing:', finalCoordinates);
            console.log('Target dimensions for cropping:', { targetWidth, targetHeight });

            cropImageFromCenter(imageUrl, targetWidth, targetHeight, function (cropError, croppedImageDataUrl) {
                if (cropError) {
                    console.error('Failed to crop image:', cropError);
                    showPreviewModal(imageUrl, { productId, index: currentImageIndex });
                    previewButton.prop('disabled', false).css('opacity', '1');
                    return;
                }

                $.ajax({
                    url: packageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'save_edited_image',
                        image_data: croppedImageDataUrl,
                        nonce: packageAjax.nonce
                    },
                    success: function (response) {
                        if (response.success && response.data && response.data.file_url) {
                            const croppedImageUrl = response.data.file_url;
                            console.log('Cropped image URL:', croppedImageUrl);

                            $.ajax({
                                url: packageAjax.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'composite_images', 
                                    frame_image_url: frameImageUrl,
                                    edited_image_url: croppedImageUrl,
                                    coordinates: finalCoordinates,
                                    nonce: packageAjax.nonce
                                },
                                success: function (compositeResponse) {
                                    if (compositeResponse.success && compositeResponse.composite_url) {
                                        showPreviewModal(compositeResponse.composite_url, { productId, index: currentImageIndex });
                                    } else {
                                        console.error('Failed to generate composite image:', compositeResponse);
                                        showPreviewModal(croppedImageUrl, { productId, index: currentImageIndex });
                                    }
                                },
                                error: function (xhr, status, error) {
                                    console.error('AJAX error generating composite image:', { status, error, responseText: xhr.responseText });
                                    showPreviewModal(croppedImageUrl, { productId, index: currentImageIndex });
                                },
                                complete: function () {
                                    previewButton.prop('disabled', false).css('opacity', '1');
                                }
                            });
                        } else {
                            console.error('Failed to save cropped image:', response);
                            showPreviewModal(imageUrl, { productId, index: currentImageIndex });
                            previewButton.prop('disabled', false).css('opacity', '1');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error saving cropped image:', { status, error, responseText: xhr.responseText });
                        showPreviewModal(imageUrl, { productId, index: currentImageIndex });
                        previewButton.prop('disabled', false).css('opacity', '1');
                    }
                });
            });
        };
        frameImg.onerror = function () {
            console.error('Failed to load frame image:', frameImageUrl);
            showPreviewModal(imageUrl, { productId, index: currentImageIndex });
            previewButton.prop('disabled', false).css('opacity', '1');
        };
        frameImg.src = frameImageUrl;
    });

    $(document).on('click', '.preview-all-btn', function () {
        const productId = $(this).attr('data-product-id');
        console.log(`Multiple Preview for Product ${productId}`);

        const $container = $(this).closest('.image-selection-container');
        const $selectedImagesContainer = $container.find('.selected-images-container');
        const imageCount = $selectedImagesContainer.find('.selected-image-thumb').length;
        frameTemplate = settingsMap[productId].frameTemplate || {};
        const frameImageUrl = frameTemplate.frame_image_url;
        const coordinates = frameTemplate.coordinates || [];

        console.log(`Image Count=${imageCount}, FrameURL=${frameImageUrl}, Coordinates=`, coordinates);

        if (imageCount > coordinates.length) {
            console.log(`Too Many Images for Product ${productId}, Max=${coordinates.length}`);
            alert(`Please select no more than ${coordinates.length} images to preview.`);
            return;
        }

        if (!frameImageUrl || coordinates.length === 0) {
            console.log('Frame image or coordinates not available, skipping composite preview');
            alert('Cannot generate preview: Frame image or coordinates are missing.');
            return;
        }

        const imageUrls = $selectedImagesContainer.find('.selected-image-thumb').map(function () {
            return $(this).attr('src');
        }).get();

        console.log('Image URLs for compositing:', imageUrls);

        const frameImg = new Image();
        frameImg.onload = function () {
            const frameWidth = frameImg.width;
            const scaleFactor = frameWidth / 1200;

            const cropPromises = imageUrls.map((imageUrl, index) => {
                const coord = coordinates[index] || {};
                const targetWidth = ((coord.x2 || 0) - (coord.x1 || 0)) * scaleFactor;
                const targetHeight = ((coord.y2 || 0) - (coord.y1 || 0)) * scaleFactor;

                if (targetWidth <= 0 || targetHeight <= 0) {
                    console.warn(`Invalid dimensions for image ${index}, skipping crop.`);
                    return Promise.resolve('');
                }

                return new Promise((resolve, reject) => {
                    cropImageFromCenter(imageUrl, targetWidth, targetHeight, (cropError, croppedImageDataUrl) => {
                        if (cropError) {
                            reject(cropError);
                        } else {
                            $.ajax({
                                url: packageAjax.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'save_edited_image',
                                    image_data: croppedImageDataUrl,
                                    nonce: packageAjax.nonce
                                },
                                success: (response) => response.success ? resolve(response.data.file_url) : reject(new Error('Failed to save cropped image')),
                                error: (xhr, status, error) => reject(new Error(`AJAX error: ${status} - ${error}`))
                            });
                        }
                    });
                });
            });

            Promise.all(cropPromises).then(croppedImageUrls => {
                console.log('Cropped image URLs:', croppedImageUrls);

                const unscaledCoordinates = coordinates.map(coord => ({
                    x1: (coord.x1 || 0) * scaleFactor,
                    y1: (coord.y1 || 0) * scaleFactor,
                    x2: (coord.x2 || 0) * scaleFactor,
                    y2: (coord.y2 || 0) * scaleFactor,
                    aspect_ratio: coord.aspect_ratio || 1,
                    frame_original_width: frameWidth
                }));

                while (croppedImageUrls.length < coordinates.length) {
                    croppedImageUrls.push('');
                }

                $.ajax({
                    url: packageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'composite_images',
                        frame_image_url: frameImageUrl,
                        edited_image_urls: croppedImageUrls,
                        coordinates: unscaledCoordinates,
                        nonce: packageAjax.nonce
                    },
                    success: function (response) {
                        if (response.success && response.composite_url) {
                            showPreviewModal(response.composite_url, { productId, index: 0 });
                        } else {
                            console.error('Failed to composite images:', response.data || 'No data returned');
                            alert('Failed to generate preview: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Composite AJAX error:', { status, error, responseText: xhr.responseText });
                        alert('Failed to generate preview. Please check the server logs.');
                    }
                });
            }).catch(error => {
                console.error('Failed to process images for preview:', error.message);
                alert('Failed to process images for preview.');
            });
        };
        frameImg.onerror = function () {
            console.error('Failed to load frame image:', frameImageUrl);
            alert('Failed to load frame image for compositing.');
        };
        frameImg.src = frameImageUrl;
    });

    $(document).on('click', '.edit-image-btn', function () {
        const productId = $(this).attr('data-product-id');
        currentImageIndex = parseInt($(this).attr('data-index'), 10);
        console.log(`Editing image at index: ${currentImageIndex} for product: ${productId}`);
        editorModal.css('display', 'block');
        initializeModal(productId);
    });

    function initializeModal(productId) {
        const editorElement = document.querySelector('#tui-image-editor');
        if (!editorElement) {
            console.error('Editor element not found');
            editorModal.css('display', 'none');
            return;
        }

        editorElement.innerHTML = '';

        imageThumbs = $('.selected-image-thumb');
        const initialImageSrc = imageThumbs.eq(currentImageIndex).attr('src');
        const loadImage = new Image();
        loadImage.crossOrigin = 'Anonymous';
        loadImage.onload = function () {
            const canvas = document.createElement('canvas');
            canvas.width = loadImage.width;
            canvas.height = loadImage.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(loadImage, 0, 0);
            originalImageData = ctx.getImageData(0, 0, loadImage.width, loadImage.height).data;
            console.log('Original image data loaded for index:', currentImageIndex);
        };
        loadImage.onerror = function () {
            console.error('Failed to load original image data:', initialImageSrc);
        };
        loadImage.src = initialImageSrc;

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
                    'menu.normalIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/img/icon-d.png',
                    'menu.activeIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/img/icon-b.png',
                    'menu.disabledIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/img/icon-a.png',
                    'menu.hoverIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/img/icon-c.png',
                    'submenu.normalIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/img/icon-d.png',
                    'submenu.activeIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/img/icon-c.png'
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
            }
        });

        setTimeout(() => {
            frameTemplate = settingsMap[productId].frameTemplate || {};
            console.log(`Raw frame template data for product ${productId}:`, frameTemplate);

            const coordinates = frameTemplate.coordinates || (frameTemplate.gallery_option === 'single_image' ? { x1: 0, y1: 0, x2: 0, y2: 0, aspect_ratio: 1 } : []);
            console.log(`Parsed frame template coordinates for product ${productId}:`, coordinates);

            hasFrameImage = frameTemplate.frame_image_url && frameTemplate.frame_image_url.trim() !== '';
            isUsingCustomRatios = hasCustomRatiosMap[productId];

            const presetButtons = editorElement.querySelectorAll(
                'div.tui-image-editor-container ul.tui-image-editor-submenu-item li.tui-image-editor-button.preset, ' +
                'div.tui-image-editor-container ul.tui-image-editor-submenu-item li.tie-crop-preset-button'
            );
            if (isUsingCustomRatios) {
                presetButtons.forEach(button => {
                    button.style.display = 'none';
                });
                console.log('Custom ratios will be used, hiding preset buttons');

                const canvasSize = editorInstance.getCanvasSize();
                console.log('Editor canvas size:', canvasSize);

                const img = new Image();
                img.onload = function () {
                    const originalWidth = img.width;
                    const originalHeight = img.height;
                    const scaleX = canvasSize.width / originalWidth;
                    const scaleY = canvasSize.height / originalHeight;

                    let cropCoords = Array.isArray(coordinates) ? coordinates[currentImageIndex] || coordinates[0] : coordinates;

                    let scaledX1 = (cropCoords.x1 || 0) * scaleX;
                    let scaledY1 = (cropCoords.y1 || 0) * scaleY;
                    let scaledX2 = (cropCoords.x2 || 0) * scaleX;
                    let scaledY2 = (cropCoords.y2 || 0) * scaleY;
                    let cropWidth = scaledX2 - scaledX1;
                    let cropHeight = scaledY2 - scaledY1;
                    lockedAspectRatio = parseFloat(cropCoords.aspect_ratio) || 1;

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

                            fabricCanvas.renderAll();
                        }
                    }
                };
                img.onerror = function () {
                    console.error('Failed to load image for scaling:', initialImageSrc);
                };
                img.src = initialImageSrc;
            } else {
                presetButtons.forEach(button => {
                    button.style.display = 'block';
                });
                console.log('No custom ratios will be used, showing preset buttons');
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
                        const editedImg = new Image();
                        editedImg.crossOrigin = 'Anonymous';
                        editedImg.onload = function () {
                            const canvas = document.createElement('canvas');
                            canvas.width = editedImg.width;
                            canvas.height = editedImg.height;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(editedImg, 0, 0);
                            const editedData = ctx.getImageData(0, 0, editedImg.width, editedImg.height).data;

                            if (areImagesDifferent(originalImageData, editedData)) {
                                $.ajax({
                                    url: packageAjax.ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'save_edited_image',
                                        image_data: editedImageDataUrl,
                                        nonce: packageAjax.nonce
                                    },
                                    success: function (response) {
                                        if (response.success && response.data) {
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
                                                .attr('data-index', currentImageIndex)
                                                .attr('data-product-id', productId);
                                            parentSelectedImage.append(editButton);

                                            if (hasCustomRatiosMap[productId] && frameTemplate.gallery_option === 'single_image') {
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
                                                    .attr('data-index', currentImageIndex)
                                                    .attr('data-product-id', productId);
                                                parentSelectedImage.append(previewButton);
                                            }

                                            currentImageThumb.attr('src', newImageUrl);
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
                                        editorModal.css('display', 'none');
                                    }
                                });
                            } else {
                                editorInstance.destroy();
                                editorInstance = null;
                                editorModal.css('display', 'none');
                            }
                        };
                        editedImg.onerror = function () {
                            console.error('Failed to load edited image data:', editedImageDataUrl);
                            editorInstance.destroy();
                            editorInstance = null;
                            editorModal.css('display', 'none');
                        };
                        editedImg.src = editedImageDataUrl;
                    }
                });
            }
        }, 500);
    }

    function areImagesDifferent(originalData, editedData) {
        if (!originalData || !editedData) return true;
        if (originalData.length !== editedData.length) return true;
        for (let i = 0; i < originalData.length; i++) {
            if (originalData[i] !== editedData[i]) return true;
        }
        return false;
    }

    $('#close-editor-modal').on('click', function () {
        if (editorInstance) {
            editorInstance.destroy();
            editorInstance = null;
        }
        editorModal.css('display', 'none');
    });

    $(window).on('click', function (event) {
        if ($(event.target).is(modal)) {
            modal.css('display', 'none');
            body.css('overflow', '');
        }
    });

    modal.find('input[type="checkbox"]').on('change', function () {
        if (!currentGalleryButton || !hiddenImagesInput.length) {
            console.error('Gallery button or hidden images input not set. Cannot proceed with image selection.');
            return;
        }

        const imageId = $(this).val();
        const imageUrl = $(this).siblings('img').attr('src');
        const imageTitle = $(this).siblings('img').attr('alt');
        let selectedImageIds = hiddenImagesInput.val().split(',').filter(Boolean);

        if ($(this).prop('checked')) {
            const galleryOption = $(this).data('galleryoption');
            if ((galleryOption === 'single_image' || galleryOption === 'multiple_images') && selectedImageIds.length >= maxImages && maxImages !== -1) {
                $(this).prop('checked', false);
                alert(`You can only select ${maxImages} image${maxImages === 1 ? '' : 's'}.`);
                return;
            }

            selectedImageIds.push(imageId);
            hiddenImagesInput.val(selectedImageIds.join(','));

            currentGalleryButton.closest('.image-selection-container').find('.selected-images-container').append(
                `<div class="selected-image" data-id="${imageId}" style="position: relative; display: inline-block; margin: 5px;">
                    <img src="${imageUrl}" alt="${imageTitle}" class="selected-image-thumb" style="width: 100px; height: auto;" />
                    <span class="remove-image" style="color: red; position: absolute; top: -5px; right: -5px; cursor: pointer;">×</span>
                </div>`
            );
        } else {
            selectedImageIds = selectedImageIds.filter(id => id !== imageId);
            hiddenImagesInput.val(selectedImageIds.join(','));

            currentGalleryButton.closest('.image-selection-container').find(`.selected-images-container .selected-image[data-id="${imageId}"]`).remove();
        }

        updateSelectedCount(currentGalleryButton);
    });

    $('.image-selection-container').on('click', '.remove-image', function () {
        const imageId = $(this).parent().data('id');
        hiddenImagesInput = $(this).closest('.image-selection-container').find('.selected-image-ids');
        let selectedImageIds = hiddenImagesInput.val().split(',').filter(Boolean);

        selectedImageIds = selectedImageIds.filter(id => id !== imageId);
        hiddenImagesInput.val(selectedImageIds.join(','));

        const count = selectedImageIds.length;
        $(this).closest('.image-selection-container').find('.view-gallery-btn').text(`Select Photos (${count})`);
        $(this).closest('.selected-image').remove();
        $(document).trigger('singleQtyChange', count);
    });

    function updateSelectedCount(galleryBtn) {
        const count = hiddenImagesInput.val().split(',').filter(Boolean).length;
        galleryBtn.text(`Select Photos (${count})`);
        $(document).trigger('singleQtyChange', count);
    }

    $('form.cart, form.carsdfdft').on('submit', function (e) {
        const selectedImagesForCart = {};

        $('.selected-image-ids').each(function () {
            const productId = $(this).closest('.image-selection-container').data('product');
            const imageIds = $(this).val().split(',').filter(Boolean);

            if (!selectedImagesForCart[productId]) {
                selectedImagesForCart[productId] = [];
            }

            selectedImagesForCart[productId] = selectedImagesForCart[productId].concat(imageIds);
        });

        console.log('Selected images for cart:', selectedImagesForCart);
        console.log('JSON stringified:', JSON.stringify(selectedImagesForCart));

        if (Object.keys(selectedImagesForCart).length > 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'selected_gallery_images',
                value: JSON.stringify(selectedImagesForCart)
            }).appendTo(this);
        }
    });

    if (!packageAjax || !packageAjax.ajaxurl) {
        console.error('packageAjax object is not properly initialized. Check wp_localize_script.');
    }
});