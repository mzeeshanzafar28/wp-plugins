jQuery(document).ready(function ($) {

    $('#display_order_id').change(function(){
        $('#sorting_form').submit();
    });
    $('#plate-customizer-plate-price-set').on('input',function(){
        prc = parseFloat($(this).val());
        $('#plate-customizer-front-plate-price-set').val(prc/2);
        $('#plate-customizer-rear-plate-price-set').val(prc/2);

    });

    $(".sortable").on("click", '.drop-this', function () {
        var table = $(this).closest('table');
        var categoryTitle = table.prevAll().find('.category-title').last();
        var property = categoryTitle.text().trim().toLowerCase().replace(/\s+/g, '_');
        var value = $(this).closest('tr').find('td:nth-child(2)').text();
        // alert(property + ' : ' + value)
        removePropertyValue(property, value);
        $(this).closest('tr').remove();
    });
    $(".sortable").on("click", ".edit-this", function () {
        
        // $('.wp-list-table').on('click', '.edit-this', function () {
        $(".add-plate-style").click();
        var table = $(this).closest('table');
        var categoryTitle = table.prevAll().find('.category-title').last();
        // var property = categoryTitle.text().trim().toLowerCase().replace(/\s+/g, '_');
        var value = $(this).closest('tr').find('td:nth-child(2)').text().toLowerCase().replace(/\s+/g, '_');
        const data = {
            action: 'fetch_data_for_plate',
            plate_name: value,
        };
        jQuery.post(plate_customizer_ajax.ajax_url,
             data,
             function (response) {
                response = response.slice(0, -1);
                response = JSON.parse(response);
                plate_style = response[0];
                plate_style_price = response[1];
                plate_style_images_array = response[2];
                plate_style_fonts = response[3];
                plate_style_border = response[4];
                plate_style_bottom_text_color = response[5];
                plate_style_bottom_text_font = response[6];
                plate_style_bottom_text_size = response[7];
                plate_style_badge_type = response[8];
                plate_style_badge_images = response[9];
                plate_style_front_plate_price = response[10];
                plate_style_rear_plate_price = response[11];
                $(".plate_style_imgs").show();
                $('.plate_style_imgs').next('span').show();
                selectOptionsInSelect3('.plate_style_imgs', plate_style_images_array);
                $('#plate-customizer-plate-style').val(plate_style);
                $('#plate-customizer-old-name').val(plate_style);
                $('#plate-customizer-plate-price-set').val(plate_style_price);
                $('#plate-customizer-front-plate-price-set').val(plate_style_front_plate_price);
                $('#plate-customizer-rear-plate-price-set').val(plate_style_rear_plate_price);

                function selectOptionsInSelect2(selectId, optionsToSelect) {
                    $(selectId).val(null).trigger('change');
                
                    optionsToSelect.forEach(function(option) {
                        $(selectId).append('<option value="' + option + '" selected="selected">' + option + '</option>');
                    });
                
                    $(selectId).trigger('change');
                }
                function selectOptionsInSelect3(selectId, optionsToSelect) {
                    $(selectId).val(null).trigger('change');
                    optionsToSelect.forEach(function(option) {
                        $(selectId).append('<option value="' + option + '" selected="selected" data-image="'+option+'">' + '</option>');
                        
                    });
                    $(selectId).trigger('change');
                }
                // Initialize Select2
                $(document).ready(function () {
                    $('.plate_style_imgs').select2({
                    templateResult: formatState,
                    templateSelection: formatState,
                    escapeMarkup: function (m) { return m; }
                    });
                });
            
                // Custom rendering function
                function formatState(state) {
                    if (!state.id) {
                    return state.text;
                    }
                    var $state = $(
                    '<span><img style="width:90px;height:90px;" src="' + $(state.element).data('image') + '" class="img-flag" /> ' + state.text + '</span>'
                    );
                    return $state;
                }
                
                selectOptionsInSelect2('.plate_style_fonts', plate_style_fonts);
                selectOptionsInSelect2('.plate_style_badge_type', plate_style_badge_type);
                selectOptionsInSelect2('.plate_style_border', plate_style_border);
                selectOptionsInSelect2('.plate_style_bottom_text_color', plate_style_bottom_text_color);
                selectOptionsInSelect2('.plate_style_bottom_text_font', plate_style_bottom_text_font);
                selectOptionsInSelect2('.plate_style_bottom_text_size', plate_style_bottom_text_size);

            

        });
        
    });


    $(document).on('change input', '.new-input', function(){
        console.log($(this).val());
        $('#hidden-name').val($(this).val());
    });
    $(".sortable").sortable({
        handle: "td:first-child",
        update: function (event, ui) {
            var column = $(this).data("column-name");
            var order = $(this).sortable("toArray", { attribute: "data-value" }).toString();
            const data = {
                action: "update_sort_order",
                column: column,
                order: order
            };
        
            jQuery.post(plate_customizer_ajax.ajax_url, data, function (response) {
                if (response === 'success') {
                    alert(' Update successfully.');
                } else {
                    alert('Failed to Update the Order. Error: ' + response);
                }
            });
        }
    });
    $(".sortable").disableSelection();

    var clicks = 0;
    $('.add-prop').click(function () {
        clicks++;
        if (clicks >= 1 )
        {
        $(this).prop('disabled',true);
        clicks = 0;
        }
        let categoryTitle = $(this).closest('div').find('h2.category-title').text();
        const inputElement = $('<input  type="text" class="new-input">');
        const confirmButton = $('<button class="confirm-button btn btn-warning">Confirm</button>');
        const flexDiv = $(this).closest('div');
        const file = $('<input type="file" class="plate_customizer_font_file" name="plate_customizer_font_file">');
        if (categoryTitle == 'Plate Font') {

            form = '<form style="width:8% !important;" method="POST" id="plate_customizer_font_form" enctype="multipart/form-data" action=""><input type="file" style="color: transparent !important;" class="plate_customizer_font_file" name="plate_customizer_font_file"><input type="hidden" id="hidden-name" name="font-name"></form>';

            flexDiv.append('&nbsp;&nbsp;', form, inputElement, '&nbsp;', confirmButton, '<br>');
        }
        else {
            flexDiv.append('&nbsp;', inputElement, '&nbsp;', confirmButton, '<br>');
        }
        confirmButton.on('click', function () {
            
            categoryTitle = categoryTitle.toLowerCase().replace(/\s+/g, '_');
            const propValue = $(this).prev('.new-input').val();
            addPropertyValue(categoryTitle, propValue);
            $(this).prev('.new-input').remove();
            $(this).remove();
            if (file.length > 0) {
                file.remove();
                $('#plate_customizer_font_form').submit();
            }
        });
    });

    //* Clearup modal before it appear
    $(".add-plate-style").on("click", function() {
        // Select the form within the modal-body and reset it
        $(".modal-body form")[0].reset();
        $('.plate_style_fonts').val([]).trigger('change');
        $('.plate_style_border').val([]).trigger('change');
        $('.plate_style_bottom_text_color').val([]).trigger('change');
        $('.plate_style_bottom_text_font').val([]).trigger('change');
        $('.plate_style_bottom_text_size').val([]).trigger('change');
        $('.plate_style_badge_type').val([]).trigger('change');
        $('.plate_style_badge_colors').val([]).trigger('change');
        $('.plate_style_badge_images').val([]).trigger('change');
        $('.plate_style_imgs').val([]).trigger('change');
        $('.plate_style_imgs').hide();
        $('.plate_style_imgs').next('span').hide();
    });

    $('.plate_style_fonts').select2();
    $('.plate_style_border').select2();
    $('.plate_style_bottom_text_color').select2();
    $('.plate_style_bottom_text_font').select2();
    $('.plate_style_bottom_text_size').select2();
    $('.plate_style_badge_type').select2();
    $('.plate_style_badge_colors').select2();
    $('.plate_style_badge_images').select2();
    $('.plate_style_imgs').select2();


$('.plate_style_badge_type').on('change', function() {
    var selectedOptions = $(this).val();
    var formattedSelectedOptions = selectedOptions.map(function(value) {
        return value.toLowerCase().replace(/\s+/g, '_');
    });
    var regexPattern = new RegExp(formattedSelectedOptions.join('|'), 'i');
    $('.plate_style_badge_images option').each(function(index, option) {
        var optionValue = $(option).val();
        var formattedOptionValue = optionValue.toLowerCase().replace(/\s+/g, '_');
        
        if (regexPattern.test(formattedOptionValue)) {
            $(option).prop('selected', true);
        }
    });
    $('.plate_style_badge_images').trigger('change');
});


// ------------------------------------------------
function addPropertyValue(property, value) {
    const data = {
        action: 'save_new_value',
        property: property,
        value: value,
        // processData: false,
        // contentType: false,
    };

    jQuery.post(plate_customizer_ajax.ajax_url, data, function (response) {
        if (response === 'success') {
            alert(value + ' Added Successfully.');
            window.location.reload();
        } else {
            alert('Failed to Add the value. Error: ' + response);
        }
    });
}

function removePropertyValue(property, value) {
    const data = {
        action: 'remove_value',
        property: property,
        value: value
    };

    jQuery.post(plate_customizer_ajax.ajax_url, data, function (response) {
        if (response === 'success') {
            alert(value + ' removed successfully.');
        } else {
            alert('Failed to remove the value. Error: ' + response);
        }
    });
}

// ---------------------------------------------------------

});

