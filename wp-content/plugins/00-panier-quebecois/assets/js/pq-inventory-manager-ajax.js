jQuery(document).ready(function ($) {
    $('td input').change( function() {
        var data = {
            'action': 'pq_update_product_meta',
						'product_id': 45968,
						'meta_key': '_pq_operation_stock',
						'meta_value': 11,
        };

				$.post( pq_inventory_manager_variables.ajax_url, data, function(response) {
					console.log(response);
				});
    });
});