jQuery(document).ready(function ($) {

  //Adapt missing product form to the missing product type
  $('#missing-product-type').change( function() {
    const missingProductType = $(this).val();

    switch (missingProductType) {
      case 'replacement':
        $('#replacement-product-wrapper').show();
        $('#is-refund-needed-wrapper').show();
        $('#manual-refund-amount-wrapper').show();
        break;
      case 'organic-replacement':
          $('#replacement-product-wrapper').hide();
          $('#is-refund-needed-wrapper').show();
          $('#manual-refund-amount-wrapper').show();
        break;
      case 'refund':
        $('#replacement-product-wrapper').hide();
        $('#is-refund-needed-wrapper').hide();
        $('#manual-refund-amount-wrapper').hide();
        break;
    }
  });

  //Get search results from AJAX
  $('.pq-short-name-search-box').keyup( function() {
    var inputValue = this.value;
    var searchResults = $(this).siblings('.pq-search-results');

    if ( inputValue.length > 3 ) {
      var nonce = $('#pq_missing_products_search_nonce');

      var data = {
        'action': 'pq_get_products_short_names',
        'short_name_input': inputValue,
        'nonce': nonce[0].value,
      }

      searchResults.addClass('pq-loading');

      $.post( pq_missing_products_variables.ajax_url, data, function(response) {
        if (response) {
          searchResults.removeClass('pq-loading');
          searchResults.show();
          searchResults.html(response);
        } else {
          searchResults.html('error');
        }
      });
    } else {
      searchResults.hide();
    }
  });

  //Select a search result
  $('.pq-search-results').on( 'click', '.parent-product', function(e) {
    e.stopPropagation();
    $(this).parents('.product-selection-wrapper').find('.pq-short-name-search-box').val( '(TOUS) ' + $(this).siblings('.variation-name').text() );
    $(this).parents('.product-selection-wrapper').find('.selected-product').val( $(this).attr("pq-parent-data") );
    $(this).parents('.pq-search-results').hide();
  });

  $('.pq-search-results').on( 'click', '.pq-product-search-result', function(e) {
    e.stopPropagation();
    $(this).parents('.product-selection-wrapper').find('.pq-short-name-search-box').val( $(this).find('.variation-name').text() );
    $(this).parents('.product-selection-wrapper').find('.selected-product').val( $(this).attr("pq-data") );
    $(this).parents('.pq-search-results').hide();
  });

  $(document).click(function() {
    $('.pq-search-results').hide();
  });

  //Submit missing products for review
  $('#review-missing-product').click(function(e) {
    e.preventDefault();
    e.stopPropagation();

    $('#submit-missing-product').show();

    var formData = $('#missing-product-form').serializeArray();
    var nonce = $('#pq_missing_products_submit_nonce');

    var data = {
      'action': 'pq_review_missing_product',
      'missing_products_form_data': formData,
      'nonce': nonce[0].value,
    }

    $.post( pq_missing_products_variables.ajax_url, data, function(response) {
			if (response) {
        $('#review-missing-product-popup-wrapper').css('display', 'flex');
        $('#review-missing-product-content-wrapper').html(response);
      } else {
        $('#review-missing-product-content-wrapper').html('error');
      }
    });
  });

  $('#review-missing-product-popup-wrapper, .pq-close').click(function(){
    $('#review-missing-product-popup-wrapper').hide();
  });
  $('#review-missing-product-popup').click(function(e){
    e.stopPropagation();
  });

  //Submit missing products to send to clients
  $('#submit-missing-product').click(function(e) {
    e.preventDefault();
    e.stopPropagation();

    var formData = $('#missing-product-form').serializeArray();
    var nonce = $('#pq_missing_products_submit_nonce');

    var data = {
      'action': 'pq_send_missing_product',
      'missing_products_form_data': formData,
      'nonce': nonce[0].value,
    }

    $.post( pq_missing_products_variables.ajax_url, data, function(response) {
			if (response) {
        $('#review-missing-product-content-wrapper').html(response);
        $('#submit-missing-product').hide();
      } else {
        $('#review-missing-product-content-wrapper').html('error');
        $('#submit-missing-product').hide();
      }
    });
  });
});