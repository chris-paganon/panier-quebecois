jQuery(document).ready(function ($) {

	$('#has-stock').change( function() {

		var isChecked = $(this).is(":checked");
		$('tr.inventory-product-row').each(function() {
			
			var productData = JSON.parse( $(this).attr("product-data") );

			if (isChecked && productData._pq_operation_stock == '') {
				$(this).hide();
			}
			if (!isChecked && productData._pq_operation_stock == '') {
				$(this).show();
			}
		});
	});

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
			console.log(response);
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