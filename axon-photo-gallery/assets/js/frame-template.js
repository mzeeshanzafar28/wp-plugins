jQuery(document).ready(function ($) {
    console.log('frame-template.js loaded');
    var galleryOption = $('#gallery-option').val();
    var cropper;
    var nextCropIndex = $('#crop-areas-container .crop-area').length || 0;
    var $imageContainer = $('#image-preview-container');
    var imageWidth, imageHeight;

    console.log('Gallery option:', galleryOption);

    // Inject CSS styles for buttons and crop area overlays
    if (!$('#frame-template-styles').length) {
        const styles = `
            <style id="frame-template-styles">
                #add-crop-area {
                    background-color: #28a745;
                    color: #fff;
                    border: none;
                    border-radius: 5px;
                    padding: 8px 16px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background-color 0.3s ease, transform 0.1s ease;
                    margin-bottom: 10px;
                    margin-top: 10px;

                }
                #add-crop-area:hover {
                    background-color: #218838;
                    transform: scale(1.02);
                }
                #add-crop-area:active {
                    transform: scale(0.98);
                }
                #save-crop-area {
                    background-color: #007bff;
                    color: #fff;
                    border: none;
                    border-radius: 5px;
                    padding: 8px 16px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background-color 0.3s ease, transform 0.1s ease;
                    margin-top: 10px;
                    display: none;
                }
                #save-crop-area:hover {
                    background-color: #0056b3;
                    transform: scale(1.02);
                }
                #save-crop-area:active {
                    transform: scale(0.98);
                }
                .remove-crop-area {
                    background-color: #dc3545;
                    color: #fff;
                    border: none;
                    border-radius: 5px;
                    padding: 6px 12px;
                    font-size: 13px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background-color 0.3s ease, transform 0.1s ease;
                    margin-top: 5px;
                }
                .remove-crop-area:hover {
                    background-color: #c82333;
                    transform: scale(1.02);
                }
                .remove-crop-area:active {
                    transform: scale(0.98);
                }
                .crop-area-overlay {
                    position: absolute;
                    border: 2px dashed #007bff;
                    background-color: rgba(0, 123, 255, 0.2);
                    box-sizing: border-box;
                    pointer-events: none;
                }
            </style>
        `;
        $('head').append(styles);
        console.log('Button and overlay styles injected');
    }

    function initializeCropper(imageId, savedCoordinates = null) {
        var image = document.getElementById(imageId || 'frame-template-image');
        if (!image) {
            console.error('Frame template image element not found');
            return false;
        }

        if (cropper) {
            cropper.destroy();
        }

        cropper = new Cropper(image, {
            aspectRatio: NaN, // Freeform selection
            viewMode: 1,
            autoCrop: false,
            movable: true,
            zoomable: true,
            rotatable: false,
            scalable: false,
            ready: function () {
                console.log('Cropper initialized');
                imageWidth = image.naturalWidth;
                imageHeight = image.naturalHeight;
                $imageContainer.css({
                    position: 'relative',
                    display: 'inline-block'
                });
                renderAllCropAreas();
                if (savedCoordinates && galleryOption === 'single_image') {
                    this.cropper.crop();
                    this.cropper.setData({
                        x: savedCoordinates.x1,
                        y: savedCoordinates.y1,
                        width: savedCoordinates.x2 - savedCoordinates.x1,
                        height: savedCoordinates.y2 - savedCoordinates.y1
                    });
                    console.log('Initial crop area set to saved coordinates:', {
                        x: savedCoordinates.x1,
                        y: savedCoordinates.y1,
                        width: savedCoordinates.x2 - savedCoordinates.x1,
                        height: savedCoordinates.y2 - savedCoordinates.y1
                    });
                    updateAspectRatio(savedCoordinates.x2 - savedCoordinates.x1, savedCoordinates.y2 - savedCoordinates.y1);
                }
            },
            crop: function (event) {
                console.log('Crop event triggered:', {
                    x: event.detail.x,
                    y: event.detail.y,
                    width: event.detail.width,
                    height: event.detail.height
                });
                if (galleryOption === 'single_image') {
                    $('input[name="axon_frame_template[coordinates][0][x1]"]').val(event.detail.x);
                    $('input[name="axon_frame_template[coordinates][0][y1]"]').val(event.detail.y);
                    $('input[name="axon_frame_template[coordinates][0][x2]"]').val(event.detail.x + event.detail.width);
                    $('input[name="axon_frame_template[coordinates][0][y2]"]').val(event.detail.y + event.detail.height);
                    updateAspectRatio(event.detail.width, event.detail.height);
                    renderAllCropAreas();
                }
            }
        });
        return true;
    }

    function updateAspectRatio(width, height) {
        const aspectRatio = width / height;
        $('input[name="axon_frame_template[coordinates][0][aspect_ratio]"]').val(aspectRatio);
        console.log('Aspect ratio calculated:', aspectRatio);
    }

    function renderAllCropAreas() {
        $imageContainer.find('.crop-area-overlay').remove();

        if (galleryOption === 'single_image') {
            const coords = {
                x1: parseFloat($('input[name="axon_frame_template[coordinates][0][x1]"]').val()) || 0,
                y1: parseFloat($('input[name="axon_frame_template[coordinates][0][y1]"]').val()) || 0,
                x2: parseFloat($('input[name="axon_frame_template[coordinates][0][x2]"]').val()) || 0,
                y2: parseFloat($('input[name="axon_frame_template[coordinates][0][y2]"]').val()) || 0
            };
            if (coords.x2 > coords.x1 && coords.y2 > coords.y1 && imageWidth && imageHeight) {
                const scaleX = $imageContainer.width() / imageWidth;
                const scaleY = $imageContainer.height() / imageHeight;
                const width = (coords.x2 - coords.x1) * scaleX;
                const height = (coords.y2 - coords.y1) * scaleY;
                const left = coords.x1 * scaleX;
                const top = coords.y1 * scaleY;
                $imageContainer.append(`<div class="crop-area-overlay" style="left: ${left}px; top: ${top}px; width: ${width}px; height: ${height}px;"></div>`);
            }
        } else if (galleryOption === 'multiple_images') {
            $('#crop-areas-container .crop-area').each(function () {
                const $area = $(this);
                const index = $area.data('index');
                const coords = {
                    x1: parseFloat($area.find(`input[name="axon_frame_template[coordinates][${index}][x1]"]`).val()) || 0,
                    y1: parseFloat($area.find(`input[name="axon_frame_template[coordinates][${index}][y1]"]`).val()) || 0,
                    x2: parseFloat($area.find(`input[name="axon_frame_template[coordinates][${index}][x2]"]`).val()) || 0,
                    y2: parseFloat($area.find(`input[name="axon_frame_template[coordinates][${index}][y2]"]`).val()) || 0
                };
                if (coords.x2 > coords.x1 && coords.y2 > coords.y1 && imageWidth && imageHeight) {
                    const scaleX = $imageContainer.width() / imageWidth;
                    const scaleY = $imageContainer.height() / imageHeight;
                    const width = (coords.x2 - coords.x1) * scaleX;
                    const height = (coords.y2 - coords.y1) * scaleY;
                    const left = coords.x1 * scaleX;
                    const top = coords.y1 * scaleY;
                    $imageContainer.append(`<div class="crop-area-overlay" style="left: ${left}px; top: ${top}px; width: ${width}px; height: ${height}px;"></div>`);
                }
            });
        }
    }

    // Handle single image mode
    if (galleryOption === 'single_image' && $('#frame-template-image').length) {
        console.log('Single image mode activated');
        const savedCoordinates = {
            x1: parseFloat($('input[name="axon_frame_template[coordinates][0][x1]"]').val()) || 0,
            y1: parseFloat($('input[name="axon_frame_template[coordinates][0][y1]"]').val()) || 0,
            x2: parseFloat($('input[name="axon_frame_template[coordinates][0][x2]"]').val()) || 0,
            y2: parseFloat($('input[name="axon_frame_template[coordinates][0][y2]"]').val()) || 0
        };
        console.log('Saved coordinates for single image:', savedCoordinates);
        initializeCropper('frame-template-image', savedCoordinates);
    }

    // Handle multiple images mode
    if (galleryOption === 'multiple_images') {
        console.log('Multiple images mode activated');

        // Initialize crop areas on page load if image exists
        if ($('#frame-template-image').length) {
            var image = document.getElementById('frame-template-image');
            imageWidth = image.naturalWidth;
            imageHeight = image.naturalHeight;
            $imageContainer.css({
                position: 'relative',
                display: 'inline-block'
            });
            renderAllCropAreas();
        }

        $('#add-crop-area').on('click', function () {
            console.log('Add Crop Area clicked');
            var image = document.getElementById('frame-template-image');
            if (!image) {
                console.error('Image not found for cropping');
                return;
            }

            if (!initializeCropper('frame-template-image')) {
                return;
            }

            $('#save-crop-area').show();
        });

        $('#save-crop-area').on('click', function () {
            console.log('Save Crop Area clicked');
            if (cropper) {
                var cropData = cropper.getData();
                if (!cropData.x || !cropData.y || !cropData.width || !cropData.height) {
                    console.error('Invalid crop data');
                    return;
                }

                var newIndex = nextCropIndex;
                var newCropArea = `
                    <div class="crop-area" data-index="${newIndex}">
                        <p><strong>Crop Area ${newIndex + 1}</strong></p>
                        <input type="hidden" name="axon_frame_template[coordinates][${newIndex}][x1]" value="${cropData.x}" />
                        <input type="hidden" name="axon_frame_template[coordinates][${newIndex}][y1]" value="${cropData.y}" />
                        <input type="hidden" name="axon_frame_template[coordinates][${newIndex}][x2]" value="${cropData.x + cropData.width}" />
                        <input type="hidden" name="axon_frame_template[coordinates][${newIndex}][y2]" value="${cropData.y + cropData.height}" />
                        <input type="hidden" name="axon_frame_template[coordinates][${newIndex}][aspect_ratio]" value="${cropData.width / cropData.height}" />
                        <button type="button" class="remove-crop-area">Remove</button>
                    </div>
                `;
                $('#crop-areas-container').append(newCropArea);
                nextCropIndex++;
                cropper.destroy();
                cropper = null;
                $('#save-crop-area').hide();
                renderAllCropAreas();
                console.log('Crop area added');
            } else {
                console.error('Cropper not initialized');
            }
        });

        $(document).on('click', '.remove-crop-area', function () {
            $(this).closest('.crop-area').remove();
            renderAllCropAreas();
            // Ensure the save button remains hidden after removal
            $('#save-crop-area').hide();
            console.log('Crop area removed');
        });
    }

    // Media uploader for selecting the frame template image
    $('#upload-frame-template-image').on('click', function (e) {
        e.preventDefault();
        console.log('Upload frame template image button clicked');

        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            console.error('wp.media is not available. Ensure wp_enqueue_media() is called.');
            return;
        }

        var frame = wp.media({
            title: 'Select Frame Template Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#frame-template-image-id').val(attachment.id);
            $('#image-preview-container').html('<img id="frame-template-image" src="' + attachment.url + '" style="max-width: 100%; height: auto;" />');

            if (galleryOption === 'multiple_images') {
                $('#crop-areas-container').empty();
                nextCropIndex = 0;
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                $('#save-crop-area').hide();
                // Initialize dimensions and render crop areas for the new image
                var image = document.getElementById('frame-template-image');
                image.onload = function () {
                    imageWidth = image.naturalWidth;
                    imageHeight = image.naturalHeight;
                    $imageContainer.css({
                        position: 'relative',
                        display: 'inline-block'
                    });
                    renderAllCropAreas();
                };
            } else if (galleryOption === 'single_image') {
                $('input[name="axon_frame_template[coordinates][0][x1]"]').val(0);
                $('input[name="axon_frame_template[coordinates][0][y1]"]').val(0);
                $('input[name="axon_frame_template[coordinates][0][x2]"]').val(0);
                $('input[name="axon_frame_template[coordinates][0][y2]"]').val(0);
                $('input[name="axon_frame_template[coordinates][0][aspect_ratio]"]').val(0);
                if (cropper) {
                    cropper.destroy();
                }
                initializeCropper('frame-template-image');
            }
        });

        frame.open();
    });

    // Log coordinates and aspect ratio before form submission
    $('form#post').on('submit', function () {
        const coordinates = galleryOption === 'single_image'
            ? {
                x1: $('input[name="axon_frame_template[coordinates][0][x1]"]').val(),
                y1: $('input[name="axon_frame_template[coordinates][0][y1]"]').val(),
                x2: $('input[name="axon_frame_template[coordinates][0][x2]"]').val(),
                y2: $('input[name="axon_frame_template[coordinates][0][y2]"]').val(),
                aspect_ratio: $('input[name="axon_frame_template[coordinates][0][aspect_ratio]"]').val()
            }
            : Array.from($('#crop-areas-container .crop-area')).map((area, index) => ({
                x1: $(area).find(`input[name="axon_frame_template[coordinates][${index}][x1]"]`).val(),
                y1: $(area).find(`input[name="axon_frame_template[coordinates][${index}][y1]"]`).val(),
                x2: $(area).find(`input[name="axon_frame_template[coordinates][${index}][x2]"]`).val(),
                y2: $(area).find(`input[name="axon_frame_template[coordinates][${index}][y2]"]`).val(),
                aspect_ratio: $(area).find(`input[name="axon_frame_template[coordinates][${index}][aspect_ratio]"]`).val()
            }));
        console.log('Coordinates and aspect ratio before saving:', coordinates);
    });
});