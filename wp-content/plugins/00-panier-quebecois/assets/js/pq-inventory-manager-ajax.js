jQuery(document).ready(function ($) {
	$('tr.inventory-product-row input').change( function() {

		var productData = JSON.parse( $(this).attr("product-data") );
		var inputValue = this.value;

		var data = {
			'action': 'pq_update_product_meta',
			'product_id': productData.product_id,
			'meta_key': '_pq_operation_stock',
			'meta_value': inputValue,
		};

		$.post( pq_inventory_manager_variables.ajax_url, data, function(response) {
		});
	});
});