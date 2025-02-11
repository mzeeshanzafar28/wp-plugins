jQuery(document).ready(function($) {
    // When the Buy Now button is clicked
    $('a.buy-now-button').on('click', function(e) {
        e.preventDefault(); // Prevent default link behavior

        var $button = $(this); // Get the button clicked
        var productId = $button.data('product-id');
        var quantity = $button.data('quantity');
        var variationId = $button.data('variation-id');

        // Disable the button and change the text to "Processing..."
        $button.prop('disabled', true).html('Processing...');

        // Use AJAX to add the product to the cart
        var data = {
            action: 'add_to_cart',
            product_id: productId,
            quantity: quantity,
            variation_id: variationId
        };

        $.post(wc_add_to_cart_params.ajax_url, data, function(response) {
            // Debugging the response to check the data
            console.log('AJAX response:', response);

            if (response.success) {
                // Redirect to checkout if response is successful
                if (response.data.checkout_url) {
                    window.location.href = response.data.checkout_url;
                } else {
                    console.error('Checkout URL is not provided');
                    alert('Error: Checkout URL is missing');
                }
            } else {
                alert('Error: Could not add product to cart');
            }
        }).fail(function() {
            // In case of failure, reset the button and show an error message
            $button.prop('disabled', false).html('Buy Now');
            alert('There was an error. Please try again.');
        });
    });

jQuery(document).ready(function($) {

    // Increase quantity
    jQuery(document).on('click', '.nm-qty-plus', function() {
        var quantity = $('a.buy-now-button').data('quantity');
        quantity = quantity + 1;
        $('a.buy-now-button').data('quantity', quantity);
    });

    // Decrease quantity
    jQuery(document).on('click', '.nm-qty-minus', function() {
        var quantity = $('a.buy-now-button').data('quantity');
        quantity = quantity - 1;
        if (quantity <= 0) return; // Prevent quantity from going below 1
        $('a.buy-now-button').data('quantity', quantity);
    });
});

});