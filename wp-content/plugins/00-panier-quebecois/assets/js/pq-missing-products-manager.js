jQuery(document).ready(function ($) {
  $('#pq-short-name-search-box').keyup( function() {
    var inputValue = this.value;
    var data = {
			'action': 'pq_get_products_short_names',
      'short_name_input': inputValue,
    }

    $.post( pq_missing_products_variables.ajax_url, data, function(response) {
			if (response) {
        $('#pq-search-results').html(response);
			} else {
        $('#pq-search-results').html('error');
			}
		});
  });
});