jQuery(document).ready(function ($) {

  $('.pq-short-name-search-box').keyup( function() {
    var inputValue = this.value;
    var searchResults = $(this).siblings('.pq-search-results');
    var data = {
			'action': 'pq_get_products_short_names',
      'short_name_input': inputValue,
    }

    $.post( pq_missing_products_variables.ajax_url, data, function(response) {
			if (response) {
        searchResults.show();
        searchResults.html(response);
			} else {
        searchResults.html('error');
			}
		});
  });

  $('.pq-search-results').on( 'click', '.pq-product-search-result', function() {
    $(this).parent().siblings('.pq-short-name-search-box').val( $(this).text() );
    $(this).parent().siblings('.selected-product').val( $(this).attr("pq-data") );
    $(this).parent().hide();
  });
});