jQuery(document).ready(function ($) {

  $('.delivery-zone-select-popup__close').click(function () {
    $('.delivery-zone-select-popup-wrapper').hide()
  });

  $('#pq-postal-code-submit').click(function () {
    $('.pq-postal-code-response').html('')
    const postalCode = $('#pq-postal-code').val().replace(/\s+/g, '').toUpperCase() // remove spaces and make uppercase
    const postalCodeIsValid = validatePostalCodeString(postalCode)
    const postalCodeIsFromQC = isPostalCodeFromQC(postalCode)

    if (!postalCodeIsValid) {
      $('.pq-postal-code-response').html('Le code postal est invalide')
      return
    } else if (!postalCodeIsFromQC) {
      $('.pq-postal-code-response').html('Le code postal n\'est pas du Québec')
      return
    } else {
      $('.pq-postal-code-response').html('')

      const data = {
			  'action': 'pq_get_delivery_zone',
        'nonce': pq_delivery_zone_variables.nonce,
        'postal_code': postalCode,
      }

      $.post( pq_delivery_zone_variables.ajax_url, data, function(response) {
        if (response === 'MTL') {
          $('.pq-postal-code-response').html(response)
          $('.delivery-zone-select-popup-wrapper').hide()
        } else {
          $('.pq-postal-code-response').html(response)
          const url = new URL(window.location.href)
          window.location.reload()
        }
      });
    }
  });

  function validatePostalCodeString(postalCode) {
    const postalCodeRegex = /[ABCEGHJ-NPRSTVXYabceghj-nprstvxy]\d[ABCEGHJ-NPRSTV-Zabceghj-nprstv-z][ -]?\d[ABCEGHJ-NPRSTV-Zabceghj-nprstv-z]\d/
    return postalCode.match(postalCodeRegex) === null ? false : true
  }

  function isPostalCodeFromQC(postalCode) {
    return postalCode[0] !== 'J' && postalCode[0] !== 'G' && postalCode[0] !== 'H' ? false : true;
  }
});