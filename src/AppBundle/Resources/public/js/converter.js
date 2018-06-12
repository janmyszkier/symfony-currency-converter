jQuery(document).on('click', '#currency_submit', function () {
    convertCurrency();
});

function convertCurrency() {
    jQuery('#conversionResult').html($('<img>', {id: 'theImg', src: '/loading.gif'}));
    jQuery.ajax({
        type: "POST",
        url: '/query',
        data: jQuery('.form').serialize(),
        success: function (response) {
            jQuery('#conversionResult').val(response.afterConversion);
        },
        dataType: 'json'
    });
}
