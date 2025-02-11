jQuery(document).ready(function ($) {
    var plateStyleSelect = document.getElementById("plate_style_select");
    var fontSelect = $("#fonts_select");
    var borderSelect = $("#border_select");
    var bottomTextColorSelect = $("#bottom_text_color_select");
    var bottomTextFontSelect = $("#bottom_text_font_select");
    var bottomTextSizeSelect = $("#bottom_text_size_select");
    var badgeTypeSelect = $("#badge_type_select");
    var imagesCarousel = $("#plate_images_carousel");
    var badgeImageSelect = $("#badge_image_select");
    const plusFront = $('#plus-front');
    const minusFront = $('#minus-front');
    const plusRear = $('#plus-rear');
    const minusRear = $('#minus-rear');
    var frontPlateQty = $('#front-plate-quantity');
    var rearPlateQty = $('#rear-plate-quantity');
    var priceHolder = $('#total_price'); 
    var frontPlatePreview = '';
    var rearPlatePreview = '';
    var frontClick = false;
    var rearClick = false;

    $('.bottom-text-p').css('font-size', '16px');
 
    $(".owl-carousel").owlCarousel({
        items: 1,
        pagination: false,
        navigationText: false,
        autoPlay: true
    });


    function doCapture()
    {
        // window.scroll(0,0);
        html2canvas(document.querySelector("#front-plate-preview")).then( function(canvas) {
            // document.body.appendChild(canvas)
            frontPlatePreview = canvas.toDataURL("image/jpeg" , 0.9);
                frontClick = true;
                performSend();
        });

        html2canvas(document.querySelector("#rear-plate-preview")).then( function(canvas) {
            // document.body.appendChild(canvas)
            rearPlatePreview = canvas.toDataURL("image/jpeg" , 0.9);
                rearClick = true;
                performSend();
        });
    }

    function performSend(){ 
        if (frontClick && rearClick)
        {
    var data = new Object();
    data['plate_number'] = $('#plate_number_input').val();
    data['plate_style'] = $('#plate_style_select option:selected').text();
    data['plate_font'] = $('#fonts_select option:selected').text();  
    data['badge_type'] = $('#badge_type_select option:selected').text();
    data['badge_image_url'] = $('#badge_image_select option:selected').val();
    data['border'] = $('#border_select option:selected').text();
    data['bottom_text'] = $('#bottom_text_input').val();
    data['bottom_text_color'] = $('#bottom_text_color_select option:selected').text();
    data['bottom_text_font'] = $('#bottom_text_font_select option:selected').text();
    data['bottom_text_size'] = $('#bottom_text_size_select option:selected').text();
    data['front_plate_qty'] = $('#front-plate-quantity').text();
    data['rear_plate_qty'] = $('#rear-plate-quantity').text();
    data['total_price'] = $('#total_price').text();
    data['front_plate_preview'] = frontPlatePreview;
    data['rear_plate_preview'] = rearPlatePreview;
    // console.log(data);

    $.ajax({
        url: plate_customizer_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'processOrder',
            data : data
        },
        success: function (response) {
            if (response == "success0")
            {
                alert("Added to cart successfully.");
              document.location.reload();

            }
            else {
                alert("An unexpected error occured while adding to cart, please try later");
                console.log('Error: ' + response);
            }
                 
        },
        error: function (error) {
            alert("An unexpected error occured while adding to cart, please try later");
            console.log('Error: ' + error);
        }
    });
    frontClick = false;
    rearClick = false;
}
    }

    $('#addToCart').on('click', function(e){
      doCapture();
        });

    $('#plate_form').on('submit', function(){
        alert('Added To Cart Successfully');
    });

    $('#plate_number_input').on('input', function(){
        $('.preview-div-text').text($(this).val());
    });

    $('#bottom_text_input').on('input', function(){
        $('.bottom-text-p').text($(this).val());
    });

    badgeImageSelect.on('change', function () {
        var selectedOption = $(this).find('option:selected');
        var imageUrl = selectedOption.data('img_src');
        if ($('.badge-image-div').length === 0) {
            $('.preview-div').before('<div class="badge-image-div"><img class="badge-image" src="{imageUrl}" alt=""></div>');
          }
        $('.badge-image').attr('src', imageUrl);
    });

    borderSelect.on('change', function () {
        var border = $(this).find('option:selected').text();
        if (border == 'None')
        {
        $('.preview-div-text').css('border', 'none');
        }
        else{
            $('.preview-div-text').css('border', '1px solid ' + border);
        }
    });

    fontSelect.on('change', function(){
        var selectedFont = $(this).find('option:selected').text();
        $('.preview-div-text').css('font-family', selectedFont);
    });

    bottomTextFontSelect.on('change', function(){
        var selectedBottomFont = $(this).find('option:selected').text();
        $('.bottom-text-p').css('font-family', selectedBottomFont);
    });

    bottomTextColorSelect.on('change', function(){
        var color = $(this).find('option:selected').text();
        $('.bottom-text-p').css('color', color);
    });

    bottomTextSizeSelect.on('change', function(){
        var size = $(this).find('option:selected').text();
        if (size == '8mm')
        {
            $('.bottom-text-p').css('font-size', '16px');
        }
        else if (size == '9mm')
        {
            $('.bottom-text-p').css('font-size', '18px');
        }
        else if (size == '10mm')
        {
            $('.bottom-text-p').css('font-size', '20px');
        }
        else{
            size = size.split('mm')[0];
            $('.bottom-text-p').css('font-size', size * 2 + 'px');
        }
        });

    function updateFinalQty()
    {
        front = frontPlateQty.text();
        rear = rearPlateQty.text();
        txt = front + ' Front, ' + rear + ' Rear';
        $('#finalQty').text(txt);

    }

    function custom_template(obj) {
        var data = $(obj.element).data();
        var text = $(obj.element).text();
        var hidden = $(obj.element).attr('flag');
        if (data && data['img_src']&& hidden !== '1') {
            img_src = data['img_src'];
            template = $("<div><img src=\"" + img_src + "\" style=\"width:100%;height:150px;\"/><p style=\"font-weight: 700;font-size:14pt;text-align:center;\">" + text + "</p></div>");
            return template;
        }
    }
    var options = {
        'templateSelection': custom_template,
        'templateResult': custom_template,
    }
    $('#badge_image_select').select2(options);
    $('.select2-container--default .select2-selection--single').css({ 'height': '155px' });


   
      
    function checkLimit() {
        var frontQty = parseInt(frontPlateQty.text());
        var rearQty = parseInt(rearPlateQty.text());
    
        if (frontQty === 0) {
            minusFront.attr("disabled", true);
        }
        else{
            minusFront.attr("disabled", false);
        }
        
        if (rearQty === 0) {
            minusRear.attr("disabled", true);
        } else {
            minusRear.attr("disabled", false);
        }

        if (frontQty === 0 && rearQty === 0)
        {
            priceHolder.text(0);
        }

        if ($('#total_price').text() == '0')
    {
        $('#addToCart').prop('disabled', true);
    }
    else{
        $('#addToCart').prop('disabled', false);
    }


    }
    
    plusFront.on('click', function (e) {
        e.preventDefault();
        checkLimit();
    
        var platePrice = parseFloat(priceHolder.text());
        var frontPlatePrice = parseFloat(priceHolder.attr("front-plate-price"));
        var rearPlatePrice = parseFloat(priceHolder.attr("rear-plate-price"));
    
        platePrice += frontPlatePrice;
        priceHolder.text(platePrice);
        frontPlateQty.text(parseInt(frontPlateQty.text()) + 1);
    
        checkLimit();
        updateFinalQty();
    });
    
    minusFront.on('click', function (e) {
        e.preventDefault();
        checkLimit();
    
        var platePrice = parseFloat(priceHolder.text());
        var frontPlatePrice = parseFloat(priceHolder.attr("front-plate-price"));
        var rearPlatePrice = parseFloat(priceHolder.attr("rear-plate-price"));
    
        var frontQty = parseInt(frontPlateQty.text());
    
        if (frontQty > 0) {
            platePrice -= frontPlatePrice;
            priceHolder.text(platePrice);
            frontPlateQty.text(frontQty - 1);
        }
    
        checkLimit();
        updateFinalQty();
    });
    
    plusRear.on('click', function (e) {
        e.preventDefault();
        checkLimit();
    
        var platePrice = parseFloat(priceHolder.text());
        var frontPlatePrice = parseFloat(priceHolder.attr("front-plate-price"));
        var rearPlatePrice = parseFloat(priceHolder.attr("rear-plate-price"));
    
        platePrice += rearPlatePrice;
        priceHolder.text(platePrice);
        rearPlateQty.text(parseInt(rearPlateQty.text()) + 1);
    
        checkLimit();
        updateFinalQty();
    });
    
    minusRear.on('click', function (e) {
        e.preventDefault();
        checkLimit();
    
        var platePrice = parseFloat(priceHolder.text());
        var frontPlatePrice = parseFloat(priceHolder.attr("front-plate-price"));
        var rearPlatePrice = parseFloat(priceHolder.attr("rear-plate-price"));
    
        var rearQty = parseInt(rearPlateQty.text());
    
        if (rearQty > 0) {
            platePrice -= rearPlatePrice;
            priceHolder.text(platePrice);
            rearPlateQty.text(rearQty - 1);
        }
    
        checkLimit();
        updateFinalQty();
    });
    
    checkLimit();
    
    function hideAllPropValues() {
        fontSelect.children().hide();
        borderSelect.children().hide();
        bottomTextColorSelect.children().hide();
        bottomTextFontSelect.children().hide();
        bottomTextSizeSelect.children().hide();
        badgeTypeSelect.children().hide();
        // imagesCarousel.children().hide();

    }

    function fetchAllowances() {

        $.ajax({
            url: plate_customizer_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_plate_customizer_allowances',


            },
            success: function (response) {
                setVals(response);
            },
            error: function (error) {
                console.log('Error: ' + error);
            }
        });
    }

    function showHideBadges() {
        $('#badge_type_heading').show();
            badgeTypeSelect.show();
            $('#badge_image_heading').show();
            $('.select2-container--default').show();

        if (plateStyleSelect.value == "standard_black_plates") {
            $('#badge_type_heading').hide();
            badgeTypeSelect.hide();
            $('#badge_image_heading').hide();
            $('.select2-container--default').hide();
            $('.preview-div-text').css('border' , 'none');
            $('.bottom-text-p').css('color' , 'black');
            borderSelect.val('border_none');
            bottomTextColorSelect.val('bottom_text_color_black');
            $('.badge-image-div').remove();
            fontSelect.val("standard");

        }
        else if (plateStyleSelect.value == "colour_badged_plates") {
            fontSelect.val("standard");
    }

         else if (plateStyleSelect.value == "3d_gel_plates") {
            fontSelect.val("3d_gel");
    }
    else if (plateStyleSelect.value == "4d_plates_-_3mm") {
        fontSelect.val("4d_3mm");
}
else if (plateStyleSelect.value == "4d_plates_-_5mm") {
    fontSelect.val("4d_5mm");
}
else if (plateStyleSelect.value == "4d+gel_plates") {
    fontSelect.val("4d_gel");
}
}

    function setVals(response) {


        var data = response;
        hideAllPropValues();
        urls = [];
        var set = true;
        data.forEach(function (item) {
            var plateName = item.plate_name;
            var allows = item.allows;

            var plateStyle = allows[0];
            var platePrice = allows[1];
            var frontPlatePrice = allows[10];
            var rearPlatePrice = allows[11];
            var plateImageUrls = allows[2];
            var allowedFonts = allows[3];
            var allowedBorders = allows[4]; //for badge
            var allowedBottomTextColor = allows[5];
            var allowedBottomTextFont = allows[6];
            var allowedBottomTextSize = allows[7];
            var allowedBadgeTypes = allows[8];
            var allowedBadgeImageFolders = allows[9];

            function refreshProps()
            {
                checkLimit();

                allowedFonts.forEach(function (fontValue) {
                    fontValue = fontValue.toLowerCase().replace(/\s/g, '_');
                    fontSelect.find(`option[value="${fontValue}"]`).show();
                });

                allowedBorders.forEach(function (borderValue) {
                    borderValue = 'border_' + borderValue.toLowerCase().replace(/\s/g, '_');
                    borderSelect.find(`option[value="${borderValue}"]`).show();
                });

                allowedBottomTextColor.forEach(function (colorValue) {
                    colorValue = 'bottom_text_color_' + colorValue.toLowerCase().replace(/\s/g, '_');
                    bottomTextColorSelect.find(`option[value="${colorValue}"]`).show();
                });

                allowedBottomTextFont.forEach(function (fontValue) {
                    fontValue = 'bottom_text_font_' + fontValue.toLowerCase().replace(/\s/g, '_');
                    bottomTextFontSelect.find(`option[value="${fontValue}"]`).show();
                });

                allowedBottomTextSize.forEach(function (sizeValue) {
                    sizeValue = 'bottom_text_size_' + sizeValue.toLowerCase().replace(/\s/g, '_');
                    bottomTextSizeSelect.find(`option[value="${sizeValue}"]`).show();
                });

                allowedBadgeTypes.forEach(function (typeValue) {
                    typeValue = 'badge_type_' + typeValue.toLowerCase().replace(/\s/g, '_');
                    badgeTypeSelect.find(`option[value="${typeValue}"]`).show();
                });

                priceHolder.attr("front-plate-price" , frontPlatePrice);
                priceHolder.attr("rear-plate-price" , rearPlatePrice);

                $(document).on("change",badgeTypeSelect, function () {
                    var selected = badgeTypeSelect.find("option:selected").text().toLowerCase().replace(/\s/g, '_');
                    
                    badgeImageSelect.find("option").each(function() {
                        var option = $(this);
                        var value = option.val();
                        var dataImgSrc = option.data('img_src');
                
                        if (!(value.includes(selected) && dataImgSrc.includes(selected))) {
                            option.attr('flag', '1');
                        }
                        else{
                            option.attr('flag', '0');
                        }
                    });
                });

                $(".owl-carousel").remove();
                    var final = '<div class="owl-carousel owl-theme">';
                    plateImageUrls.forEach(function (url) {
                        if (urls.includes(url)) {
                            var html = '<div class="carousel-item" style="display:block;"><img src=" ' + url + ' " class="d-block w-100"></div>';
                            final += html;
                        }
                    });
                    final += "</div>";
                    $("#plate-customizer-carousl").append(final);
                    $(".owl-carousel").owlCarousel({
                        items: 1,
                        loop: true,
                        dots: false,
                        autoplay: true,
                        autoplayTimeout: 4000
                    });
            }

            plateImageUrls.forEach(function (url) {
                urls.push(url);
            });

            if (set)
            {
                $("#finalStyle").text(plateStyle.toUpperCase().replace(/_/g, ' '));
                $("#total_price").text(platePrice);
                refreshProps();
                set = false;
            }


            plateStyleSelect.addEventListener("change", function () {
                const selectedPlateStyle = plateStyleSelect.value;
                showHideBadges();
                $('#finalQty').text("1 FRONT, 1 REAR");
                $('#finalStyle').text(selectedPlateStyle.toUpperCase().replace(/_/g, ' '));

                if (selectedPlateStyle === plateName) {
                    hideAllPropValues();
                    priceHolder.text(platePrice);
                    priceHolder.attr("front-plate-price" , frontPlatePrice);
                    priceHolder.attr("rear-plate-price" , rearPlatePrice);

                    frontPlateQty.text(1);
                    rearPlateQty.text(1);

                    refreshProps();
                  
                    
                }
            });
        });
    }
   
    $(document).on('load', function (){
        plateStyleSelect.dispatchEvent(new Event("change"));
    });
    fetchAllowances();
    showHideBadges();

});