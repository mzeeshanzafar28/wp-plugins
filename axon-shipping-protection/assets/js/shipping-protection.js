jQuery(document).ready(function ($) {
    // function updateToggleStateBasedOnFeeRow() {
    //     var $toggle = $('#shipping_protection');
    //     var $feeRow = $('tr.fee').filter(function () {
    //         return $(this).find('th').text().trim() === 'Shipping Protection';
    //     });
    //     var isFeeAdded = $feeRow.length > 0;
    //     $toggle.prop('checked', isFeeAdded);
    // }

    // updateToggleStateBasedOnFeeRow();

    if (!$('#shipping_protection').is(':checked')) {
        setTimeout(function () {
            $('.protection-carbon').css('color', '#6a6a6d');
            $('.protection-carbon img').css('filter', 'contrast(0.1)');
        }, 2000);
        // console.log('Protection not checked');
    }

    if ($('#billing_email').length) {
        if ($('#shipping_protection_hidden').length === 0) {
            var hiddenInput = $('<input>', {
                type: 'hidden',
                id: 'shipping_protection_hidden',
                name: 'shipping_protection_hidden',
                value: $('#shipping_protection').is(':checked') ? 'on' : 'off'
            });

            $('#billing_email').after(hiddenInput);
            console.log('Hidden input added');
        } else {
            $('#shipping_protection_hidden').val($('#shipping_protection').is(':checked') ? 'on' : 'off');
            console.log('Hidden input value updated');

        }
    }



    $(document).on('change', '#shipping_protection', function () {
        var $toggle = $(this);
        var $popup = $('#shipping-protection-popup');
        var $feeDisplay = $('.protection-fee');
        var isChecked = $toggle.is(':checked');

        // console.log('Toggle changed, isChecked:', isChecked);

        if (!isChecked) {
            $popup.show();
        } else {
            // console.log('Toggle Turned On from Off');
            // $toggle.attr('checked', true);
            $toggle.prop('checked', true);
            // console.log('Toggle state after On from Off:', $toggle.is(':checked'));
            $('body').trigger('update_checkout');
            $('#shipping_protection_hidden').val('on');
            setTimeout(function () {
                $('.protection-carbon').css('color', 'black');
                $('.protection-carbon img').css('filter', 'contrast(1)');
            }, 1000);
            // updateCart(true);
        }
    });

    $(document).on('click', '#accept-decline', function () {
        var $popup = $('#shipping-protection-popup');
        var $toggle = $('#shipping_protection');

        // console.log('Accept decline clicked');
        $popup.hide();
        $toggle.prop('checked', false);
        $('#shipping_protection_hidden').val('off');

        // $toggle.attr('checked', false);
        // console.log('Toggle state after accept-decline:', $toggle.is(':checked'));
        $('body').trigger('update_checkout');
        setTimeout(function () {
            $('.protection-carbon').css('color', '#6a6a6d');
            $('.protection-carbon img').css('filter', 'contrast(0.1)');
        }, 1000);
        // updateCart(false);
    });

    $(document).on('click', '#cancel-decline', function () {
        var $popup = $('#shipping-protection-popup');
        var $toggle = $('#shipping_protection');

        // console.log('Cancel decline clicked');
        $toggle.off('change'); // Temporarily disable the change event
        $toggle.prop('checked', true); // Set the toggle back to checked
        $toggle.on('change');
        $popup.hide();
        // $toggle.prop('checked', true);
        // $toggle.attr('checked', true);
        console.log('Toggle state after cancel-decline:', $toggle.is(':checked'));
        // $('body').trigger('update_checkout');
        // updateCart(true);
    });

    // function updateCart(protectionActive) {
    //     var $feeDisplay = $('.protection-fee');
    //     // console.log('Updating cart with protection active:', protectionActive);

    //     var originalFeeText = $feeDisplay.text().replace(/[^0-9.]/g, '');
    //     var originalFee = parseFloat(originalFeeText) || 0;
    //     var currencySymbol = $('.woocommerce-Price-currencySymbol').val();
    //     var newFee = protectionActive ? originalFee : 0;

    //     $.ajax({
    //         url: axon_shipping.ajax_url,
    //         type: 'POST',
    //         data: {
    //             action: 'update_shipping_protection',
    //             nonce: axon_shipping.nonce,
    //             protection_active: protectionActive
    //         },
    //         success: function (response) {
    //             // console.log('AJAX response:', response);
    //             if (response.success) {
    //                 $feeDisplay.html(protectionActive ? (currencySymbol + originalFee.toFixed(2)) : (currencySymbol + '0.00'));

    //                 updateToggleStateBasedOnFeeRow();

    //                 if (response.data && response.data.fragments) {
    //                     $.each(response.data.fragments, function (key, value) {
    //                         $(key).replaceWith(value);
    //                         // console.log('Updated fragment:', key);
    //                     });
    //                 }

    //                 $('body').trigger('update_checkout');

    //                 $('body').trigger('updated_checkout');
    //             } else {
    //                 console.log('AJAX success false:', response);
    //             }
    //         },
    //         error: function (xhr, status, error) {
    //             console.error('Error updating shipping protection:', error);
    //             console.error('Status:', status);
    //             console.error('Response:', xhr.responseText);
    //         }
    //     });
    // }


    // $('body').on('updated_checkout', function () {
    //     console.log('updated_checkout event triggered');
    //     updateToggleStateBasedOnFeeRow();
    // });

    // console.log('Initial toggle state:', $('#shipping_protection').length);
    // console.log('Initial popup state:', $('#shipping-protection-popup').length);
    // console.log('Initial fee display state:', $('.protection-fee').length);
    // console.log('Axon shipping object:', axon_shipping);
});