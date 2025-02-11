jQuery(document).ready(function($) {
    $('.woocommerce-review__verified').each(function() {
        $(this).text('');
        $(this).append('<img src="' + reviewman_vars.tick_image_url + '" class="verified-tick" title="Verified Owner">');
    });

    $(document).on('mouseover', '.verified-tick', function() {
        $(this).attr('title', 'Verified Owner');
    });
});
