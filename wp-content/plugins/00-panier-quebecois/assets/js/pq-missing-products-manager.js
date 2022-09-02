jQuery(document).ready(function ($) {
  var searchBox = $('#pq-short-name-search-box');
  var searchResults = $('#pq-search-results');

  searchBox.keyup( function pq_search_products_short_name_with_ajax () {
    var inputValue = $('#pq-short-name-search-box').val();
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

  searchResults.on( 'click', '.pq-product-search-result', function() {
    console.log($(this).attr("pq-data"));
    searchBox.val( $(this).text() );
    $('#selected-product').val( $(this).attr("pq-data") );
    searchResults.hide();
  });
});