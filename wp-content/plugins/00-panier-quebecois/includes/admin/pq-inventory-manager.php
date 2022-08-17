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
		$products = pq_get_products_array_for_inventory( $orders );

		$args = array( 'products' => $products );
		ob_start();
		wc_pq_get_template( 'pq-inventory-manager-table.php', $args );
		return ob_get_clean();
	}
}


/**
 * Get array of products to keep in inventory
 */
function pq_get_products_array_for_inventory( $orders, $need_variations = false ) {
    $products = array();
    foreach ( $orders as $order ) {
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = wc_get_product( $item->get_product_id() );
    
            if ( myfct_is_relevant_product( $product ) ) {
                if ( $item->get_variation_id() !== 0 && $need_variations ) {
                    $new_id = $item->get_variation_id();
                } else {
                    $new_id = $product->get_id();
                }
        
                $is_new_id = true;
                
                foreach ( $products as $key => $product_arr ) {
                    if ( $new_id == $product_arr['product_id'] ) {
                        $is_new_id = false;
                    }
                }
        
                if ( $is_new_id ) {
                    $short_name = get_post_meta( $new_id, '_short_name', true);
                    $operation_stock = get_post_meta( $new_id, '_pq_operation_stock', true);
                    $packing_priority = get_post_meta( $new_id, '_packing_priority', true);
                    $lot_quantity = get_post_meta( $new_id, '_lot_quantity', true);
                    $lot_unit = get_post_meta( $new_id, '_lot_unit', true);
                    $weight = get_post_meta( $new_id, '_pq_weight', true);
                    $new_product = array( array( 
                        'product_id' => $new_id,
                        '_short_name' => $short_name,
                        '_pq_operation_stock' => $operation_stock,
                        '_packing_priority' => $packing_priority,
                        '_lot_quantity' => $lot_quantity,
                        '_lot_unit' => $lot_unit,
                        '_pq_weight' => $weight,
                    ));
                    $products = array_merge($products, $new_product);
                }
            }
        }
    }

    $short_name_column = array_column($products, '_short_name');
    $packing_priority_column = array_column($products, '_packing_priority');
    array_multisort($packing_priority_column, SORT_ASC, SORT_STRING, $short_name_column, $products);

    return $products;
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