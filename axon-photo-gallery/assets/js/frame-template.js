jQuery(document).ready(function ($) {
    // Debug: Confirm the script is loaded
    console.log('frame-template.js loaded');

    // Log the initial values of the form fields on page load
    console.log('Initial form field values on page load:', {
        x1: $('input[name="axon_frame_template[coordinates][x1]"]').val(),
        y1: $('input[name="axon_frame_template[coordinates][y1]"]').val(),
        x2: $('input[name="axon_frame_template[coordinates][x2]"]').val(),
        y2: $('input[name="axon_frame_template[coordinates][y2]"]').val()
    });

    // Media uploader for selecting the frame template image
    $('#upload-frame-template-image').on('click', function (e) {
        e.preventDefault();

        // Debug: Confirm the click event is triggered
        console.log('Upload frame template image button clicked');

        // Check if wp.media is available
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

            // Update the preview
            $('#image-preview-container').html('<img id="frame-template-image" src="' + attachment.url + '" style="max-width: 100%; height: auto;" />');

            // Debug: Confirm the image is selected
            console.log('Image selected:', attachment);

            // Initialize Cropper.js
            initializeCropper();
        });

        frame.on('open', function () {
            console.log('Media library opened');
        });

        frame.on('close', function () {
            console.log('Media library closed');
        });

        frame.open();
    });

    // Initialize Cropper.js on the image
    function initializeCropper() {
        var image = document.getElementById('frame-template-image');
        if (!image) {
            console.error('Frame template image element not found');
            return;
        }

        // Get the saved coordinates
        const savedCoordinates = {
            x1: parseFloat($('input[name="axon_frame_template[coordinates][x1]"]').val()),
            y1: parseFloat($('input[name="axon_frame_template[coordinates][y1]"]').val()),
            x2: parseFloat($('input[name="axon_frame_template[coordinates][x2]"]').val()),
            y2: parseFloat($('input[name="axon_frame_template[coordinates][y2]"]').val())
        };
        console.log('Saved coordinates for Cropper.js:', savedCoordinates);

        var cropper = new Cropper(image, {
            aspectRatio: NaN, // Freeform selection
            viewMode: 1,
            autoCrop: false, // Disable automatic cropping on initialization
            movable: true,
            zoomable: true,
            rotatable: false,
            scalable: false,
            ready: function () {
                // Set the initial crop area based on the saved coordinates
                if (savedCoordinates.x1 !== 0 || savedCoordinates.y1 !== 0 || savedCoordinates.x2 !== 0 || savedCoordinates.y2 !== 0) {
                    this.cropper.crop(); // Enable cropping
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

                    // Calculate and store the aspect ratio on ready
                    const width = savedCoordinates.x2 - savedCoordinates.x1;
                    const height = savedCoordinates.y2 - savedCoordinates.y1;
                    const aspectRatio = width / height;
                    $('input[name="axon_frame_template[coordinates][aspect_ratio]"]').val(aspectRatio);
                    console.log('Aspect ratio calculated on ready:', aspectRatio);
                }
            },
            crop: function (event) {
                // Update the coordinate fields only when the user interacts with the crop area
                console.log('Crop event triggered:', {
                    x: event.detail.x,
                    y: event.detail.y,
                    width: event.detail.width,
                    height: event.detail.height
                });
                $('input[name="axon_frame_template[coordinates][x1]"]').val(event.detail.x);
                $('input[name="axon_frame_template[coordinates][y1]"]').val(event.detail.y);
                $('input[name="axon_frame_template[coordinates][x2]"]').val(event.detail.x + event.detail.width);
                $('input[name="axon_frame_template[coordinates][y2]"]').val(event.detail.y + event.detail.height);

                // Calculate and store the aspect ratio
                const width = event.detail.width;
                const height = event.detail.height;
                const aspectRatio = width / height;
                $('input[name="axon_frame_template[coordinates][aspect_ratio]"]').val(aspectRatio);
                console.log('Aspect ratio calculated on crop:', aspectRatio);
            }
        });
    }

    // Log coordinates and aspect ratio before form submission
    $('form#post').on('submit', function () {
        const coordinates = {
            x1: $('input[name="axon_frame_template[coordinates][x1]"]').val(),
            y1: $('input[name="axon_frame_template[coordinates][y1]"]').val(),
            x2: $('input[name="axon_frame_template[coordinates][x2]"]').val(),
            y2: $('input[name="axon_frame_template[coordinates][y2]"]').val(),
            aspect_ratio: $('input[name="axon_frame_template[coordinates][aspect_ratio]"]').val()
        };
        console.log('Coordinates and aspect ratio before saving:', coordinates);
    });

    // Initialize Cropper.js on page load if an image is already selected
    if ($('#frame-template-image').length) {
        initializeCropper();
    }
});