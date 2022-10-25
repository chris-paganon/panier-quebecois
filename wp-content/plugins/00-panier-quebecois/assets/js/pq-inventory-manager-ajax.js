jQuery(document).ready(function ($) {

	/**
	 * Show products depending on options selected by user
	 */
	$('.pq-inventory-options').change( function() {

		var selectedCategory =  $('#product-categories').val();
		var selectedInventoryType =  $('#inventory-type').val();
		var hasStock = $('#has-stock').is(":checked");
		var minPriority = 0;
		var maxPriority = 100;

		switch (selectedCategory) {
			case 'all':
				minPriority = 0;
				maxPriority = 100;
				break;
			case 'epicerie':
				minPriority = 0;
				maxPriority = 9;
				break;
			case 'fruit-et-legumes':
				minPriority = 10;
				maxPriority = 19;
				break;
			case 'frais':
				minPriority = 20;
				maxPriority = 29;
				break;
			default:
				minPriority = 0;
				maxPriority = 100;
		}
		$('tr.inventory-product-row').each(function() {
			var productData = JSON.parse( $(this).attr("product-data") );

			if ( productData._packing_priority >= minPriority 
				&& productData._packing_priority <= maxPriority
				&& ( selectedInventoryType == 'all' || productData.pq_inventory_type.indexOf(selectedInventoryType) !== -1 ) ) {
				if ( hasStock && productData._pq_operation_stock == '' ) {
					$(this).hide();
				} else {
					$(this).show();
				}
			} else {
				$(this).hide();
			}
		});
	});


	/**
	 * Send inventory meta to database through AJAX
	 */
	$('tr.inventory-product-row input').change( function() {

		var productData = JSON.parse( $(this).parents('.inventory-product-row').attr("product-data") );
		var inputValue = this.value;
		var nonce = $(this).siblings('#pq_inventory_nonce');

		var data = {
			'action': 'pq_update_product_meta',
			'product_id': productData.product_id,
			'meta_key': '_pq_operation_stock',
			'meta_value': inputValue,
			'nonce': nonce[0].value,
		};

		var inputField = this;

		$(this).removeClass('pq-updated');
		$(this).removeClass('pq-error');
		$(this).addClass('pq-loading');

		$.post( pq_inventory_manager_variables.ajax_url, data, function(response) {
			if (response) {
				$(inputField).removeClass('pq-loading');
				$(inputField).removeClass('pq-error');
				$(inputField).addClass('pq-updated');
			} else {
				$(inputField).removeClass('pq-loading');
				$(inputField).removeClass('pq-updated');
				$(inputField).addClass('pq-error');
			}
		});
	});
});