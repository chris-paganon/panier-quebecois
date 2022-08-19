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
		$products = pq_get_product_rows( $orders );

        $short_name_columns = array_column($products, '_short_name');
        array_multisort($short_name_columns, SORT_ASC, SORT_STRING, $products);

		$args = array( 'products' => $products );
		ob_start();
		wc_pq_get_template( 'pq-inventory-manager-table.php', $args );
		return ob_get_clean();
	}
}


/**
 * Update product meta with AJAX
 */
add_action( 'wp_ajax_pq_update_product_meta', 'pq_update_product_meta_with_ajax' );
add_action( 'wp_ajax_nopriv_pq_update_product_meta', 'pq_update_product_meta_with_ajax' );

function pq_update_product_meta_with_ajax() {
	$product_id = sanitize_text_field( $_POST['product_id'] );
	$meta_key = sanitize_text_field( $_POST['meta_key'] );
	$meta_value = sanitize_text_field( $_POST['meta_value'] );

	if ( isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'pq_inventory_changed')) {
		$update_success = update_post_meta( $product_id, $meta_key, $meta_value );
		if ( $update_success !== true ) {
			echo 0;
		} else {
			echo 1;
		}
	} else {
		echo 0;	
	}

	wp_die();
}