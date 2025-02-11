jQuery(document).ready(function ($) {
    $('#add_buy_now_button').change(function () {
        $('#enable_shake_animation_group').toggle(this.checked);
        $('#enable_color_change_animation_group').toggle(this.checked);

    });

    $('#add_whatsapp_button').change(function () {
        $('#whatsapp_number_group').toggle(this.checked);
        $('#whatsapp_shake_animation_group').toggle(this.checked);
    });
});
