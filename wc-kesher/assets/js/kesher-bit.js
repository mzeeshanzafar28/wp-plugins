jQuery(document).ready(function ($) {
    $('#kesher_bit_credit_card_number').mask('0000 0000 0000 0000');
    $('#kesher_bit_expiry_date').mask('00/00');
    $('#kesher_bit_cvv').mask('000');
    jQuery(document).on('focus', '#kesher_bit_credit_card_number', function () {
        $('#kesher_bit_credit_card_number').mask('0000 0000 0000 0000');
    });
    jQuery(document).on('focus', '#kesher_bit_cvv', function () {
        $('#kesher_bit_cvv').mask('000');
    });
    jQuery(document).on('focus', '#kesher_bit_expiry_date', function () {
        $('#kesher_bit_expiry_date').mask('00/00');
    });
});