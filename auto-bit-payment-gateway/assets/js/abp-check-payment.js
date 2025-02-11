jQuery(document).ready(function($) {
    var orderKey = abp_params.order_key;
    
    if (orderKey) {
        function checkPaymentStatus() {
            $.ajax({
                type: 'POST',
                url: abp_params.ajax_url,
                data: {
                    action: 'abp_check_payment',
                    order_key: orderKey
                },
                success: function(response) {
                    // console.log(response)
                    if (response.success) {
                        window.location.href = response.data.redirect;
                    }
                },
                error: function(response) {
                    // console.log(response);
                }
            });
        }

        // Check every 5 seconds
        setInterval(checkPaymentStatus, 5000);
    }
});
