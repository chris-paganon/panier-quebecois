jQuery(document).ready(function ($) {

  $('.delivery-zone-select-popup__close').click(function () {
    $('.delivery-zone-select-popup-wrapper').hide()
  });

  $('#pq-postal-code-submit').click(function () {
    const postalCode = $('#pq-postal-code').val();
    const postalCodeIsValid = validatePostalCodeString(postalCode)

    if (!postalCodeIsValid) {
      $('.pq-postal-code-response').html('Le code postal est invalide')
      return
    } else {
      $('.pq-postal-code-response').html('')

      const data = {
			  'action': 'pq_get_delivery_zone',
        'nonce': pq_delivery_zone_variables.nonce,
        'postal_code': postalCode,
      }

      $.post( pq_delivery_zone_variables.ajax_url, data, function(response) {
        if (response) {
          $('.pq-postal-code-response').html(response)
          $('.delivery-zone-select-popup-wrapper').hide()
          window.location.reload()
        } else {
          $('.pq-postal-code-response').html('nope')
        }
      });
    }
  });

  function validatePostalCodeString(postalCode) {
    const postalCodeRegex = /[ABCEGHJ-NPRSTVXYabceghj-nprstvxy]\d[ABCEGHJ-NPRSTV-Zabceghj-nprstv-z][ -]?\d[ABCEGHJ-NPRSTV-Zabceghj-nprstv-z]\d/
    return postalCode.match(postalCodeRegex) === null ? false : true
  }
});