jQuery(document).ready(function ($) {
    function updateToggleStateBasedOnFeeRow() {
        var $toggle = $('#shipping_protection');
        var $feeRow = $('tr.fee').filter(function () {
            return $(this).find('th').text().trim() === 'Shipping Protection';
        });
        var isFeeAdded = $feeRow.length > 0;
        $toggle.prop('checked', isFeeAdded);
    }

    updateToggleStateBasedOnFeeRow();

    $(document).on('change', '#shipping_protection', function () {
        var $toggle = $(this);
        var $popup = $('#shipping-protection-popup');
        var $feeDisplay = $('.protection-fee');
        var isChecked = $toggle.is(':checked');

        // console.log('Toggle changed, isChecked:', isChecked);

        if (!isChecked) {
            $popup.show();
        } else {
            updateCart(true);
        }
    });

    $(document).on('click', '#accept-decline', function () {
        var $popup = $('#shipping-protection-popup');
        var $toggle = $('#shipping_protection');

        // console.log('Accept decline clicked');
        $popup.hide();
        $toggle.prop('checked', false);
        // console.log('Toggle state after accept-decline:', $toggle.is(':checked'));
        updateCart(false);
    });

    $(document).on('click', '#cancel-decline', function () {
        var $popup = $('#shipping-protection-popup');
        var $toggle = $('#shipping_protection');

        // console.log('Cancel decline clicked');
        $popup.hide();
        $toggle.prop('checked', true);
        // console.log('Toggle state after cancel-decline:', $toggle.is(':checked'));
        updateCart(true);
    });

    function updateCart(protectionActive) {
        var $feeDisplay = $('.protection-fee');
        // console.log('Updating cart with protection active:', protectionActive);

        var originalFeeText = $feeDisplay.text().replace(/[^0-9.]/g, '');
        var originalFee = parseFloat(originalFeeText) || 0;
        var currencySymbol = $('.woocommerce-Price-currencySymbol').val();
        var newFee = protectionActive ? originalFee : 0;

        $.ajax({
            url: axon_shipping.ajax_url,
            type: 'POST',
            data: {
                action: 'update_shipping_protection',
                nonce: axon_shipping.nonce,
                protection_active: protectionActive
            },
            success: function (response) {
                // console.log('AJAX response:', response);
                if (response.success) {
                    $feeDisplay.html(protectionActive ? (currencySymbol + originalFee.toFixed(2)) : (currencySymbol + '0.00'));

                    updateToggleStateBasedOnFeeRow();

                    if (response.data && response.data.fragments) {
                        $.each(response.data.fragments, function (key, value) {
                            $(key).replaceWith(value);
                            // console.log('Updated fragment:', key);
                        });
                    }

                    $('body').trigger('update_checkout');

                    $('body').trigger('updated_checkout');
                } else {
                    console.log('AJAX success false:', response);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error updating shipping protection:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
            }
        });
    }

    $('body').on('update_checkout', function () {
        // console.log('update_checkout event triggered');
    });

    $('body').on('updated_checkout', function () {
        // console.log('updated_checkout event triggered');
        updateToggleStateBasedOnFeeRow();
    });

    // console.log('Initial toggle state:', $('#shipping_protection').length);
    // console.log('Initial popup state:', $('#shipping-protection-popup').length);
    // console.log('Initial fee display state:', $('.protection-fee').length);
    // console.log('Axon shipping object:', axon_shipping);
});