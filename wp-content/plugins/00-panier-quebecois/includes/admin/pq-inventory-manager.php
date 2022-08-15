<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Add shortcode to display inventory manager
 */
add_shortcode( 'pq_inventory_manager', 'pq_inventory_manager_fct' );

function pq_inventory_manager_fct() {
	if ( current_user_can( 'pq_see_operations' ) ) {

		$export_start_date_obj = new DateTime( '- 2 weeks ' );
		$export_start_date = $export_start_date_obj->format( 'y-m-d' );
		$export_end_date_obj = new DateTime( 'tomorrow' );
		$export_end_date = $export_end_date_obj->format( 'y-m-d' );
	
		$query = array(
			'type' => 'shop_order',
			'status' => array('wc-processing', 'wc-completed'),
			'limit' => -1,
			'date_created' => $export_start_date . '...' . $export_end_date,
		);
	
		$orders = wc_get_orders( $query );
		$products = myfct_get_products_quantities( $orders );

		$args = array( 'orders' => $products );
		ob_start();
		wc_pq_get_template( 'pq-inventory-manager-table.php', $args );
		return ob_get_clean();
	}
}