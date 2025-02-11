jQuery(document).ready(function ($) {
    $('#kesher_credit_card_number').mask('0000 0000 0000 0000');
    $('#kesher_expiry_date').mask('00/00');
    $('#kesher_cvv').mask('000');
    jQuery(document).on('focus', '#kesher_credit_card_number', function () {
        $('#kesher_credit_card_number').mask('0000 0000 0000 0000');
    });
    jQuery(document).on('focus', '#kesher_cvv', function () {
        $('#kesher_cvv').mask('000');
    });
    jQuery(document).on('focus', '#kesher_expiry_date', function () {
        $('#kesher_expiry_date').mask('00/00');
    });

});