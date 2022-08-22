<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * 
 * Get relevant products and orders 
 *
 */

 /**
 * Get all the product rows in an array
 */
function pq_get_product_rows($orders) {

  $product_rows = array();
    
  foreach ( $orders as $order ) {
    foreach ( $order->get_items() as $item_id => $item ) {
      $product = wc_get_product( $item->get_product_id() );

      if ( myfct_is_relevant_product( $product ) ) {

        $has_distinct_variations = false;
        if ( $item->get_variation_id() !== 0 ) {
          $parent_id = $product->get_id();
          $product_id = $item->get_variation_id();

          $variation_ids = $product->get_children();

          $previous_variation_short_name = '';
          foreach ($variation_ids as $key => $variation_id) {
            $variation_short_name = get_post_meta( $variation_id, '_short_name', true );
            if ($key !== 0 && $variation_short_name != $previous_variation_short_name) {
              $has_distinct_variations = true;
            }
            $previous_variation_short_name = $variation_short_name;
          }
        } else {
          $product_id = $parent_id = $product->get_id();
        }

        $product_quantity_before_refund = $item->get_quantity();
        $product_quantity_refunded = $order->get_qty_refunded_for_item( $item_id );
        $product_quantity = $product_quantity_before_refund + $product_quantity_refunded;
        $lot_quantity = get_post_meta( $product_id, '_lot_quantity', true );
        $total_quantity = $product_quantity * $lot_quantity;

        $short_name = get_post_meta( $product_id, '_short_name', true );

        $requires_new_row = true;

        foreach ( $product_rows as $key => $product_row ) {

          $existing_short_name = $product_row['_short_name'];

          if ( $short_name == $existing_short_name ) {
            $product_rows[$key]['total_quantity'] += $total_quantity;
            $requires_new_row = false;
          }
        }

        if ( $requires_new_row ) {
          $weight = get_post_meta( $product_id, '_pq_weight', true );
          $unit = get_post_meta( $product_id, '_lot_unit', true );
          $weight_with_unit = $weight . $unit;
          $packing_priority = get_post_meta( $parent_id, '_packing_priority', true );
          $commercial_zone = wp_get_post_terms( $parent_id, 'pq_commercial_zone', array( 'fields' => 'names' ) );
          $commercial_zone_string = implode( ', ', $commercial_zone );
          $reference_name = get_post_meta( $parent_id, '_pq_reference', true );
      		$sku = $product->get_sku();


          if ($has_distinct_variations) {
            $product_id_to_display = $product_id;
          } else {
            $product_id_to_display = $parent_id;
          }
          $operation_stock = get_post_meta( $product_id_to_display, '_pq_operation_stock', true);

          $suppliers = wp_get_post_terms( $parent_id, 'pq_distributor' );
          if ( empty( $suppliers ) ) {
            $suppliers = wp_get_post_terms( $parent_id, 'product_tag' );
            if ( empty( $suppliers ) ) {
              $suppliers = wp_get_post_terms( $parent_id, 'pq_producer' );
            }
          }

          $suppliers_names = array();
          $supplier_auto_order_string = '';
          foreach ($suppliers as $supplier) {
            array_push($suppliers_names, $supplier->name);
            $supplier_email = get_term_meta ( $supplier->term_id, 'pq_seller_email', true );
            $supplier_sms = get_term_meta ( $supplier->term_id, 'pq_seller_sms', true );
            if ( ! empty($supplier_email) ) {
              $supplier_auto_order_string .= $supplier_email . ', ';
            }
            if ( ! empty($supplier_sms) ) {
              $supplier_auto_order_string .= $supplier_sms;
            }
          }

          $suppliers_string = implode( ', ', $suppliers_names );

          $inventory_type = wp_get_post_terms( $parent_id, 'pq_inventory_type', array( 'fields' => 'slugs' ) );

          $new_product_row = array(array(
            'product_id' => $product_id_to_display,
            'pq_commercial_zone' => $commercial_zone_string,
            'supplier' => $suppliers_string,
            'sku' => $sku,
            '_short_name' => $short_name,
            '_pq_reference' => $reference_name,
            'total_quantity' => $total_quantity,
            '_lot_unit' => $unit,
            'weight' => $weight_with_unit,
            '_pq_operation_stock' => $operation_stock,
            '_packing_priority' => $packing_priority,
            'pq_inventory_type' => $inventory_type,
            'supplier_auto_order_string' => $supplier_auto_order_string,
          ));

          $product_rows = array_merge($product_rows, $new_product_row);
        }
      }
    }
  }

  return $product_rows;
}


/**
 * Add quantity to buy to products rows
 */
function pq_add_quantity_to_buy_to_products($products) {
  foreach ( $products as $key => $product ) {
		$operation_stock = $product['_pq_operation_stock'];
		$total_quantity = $product['total_quantity'];

		if ( is_numeric($operation_stock) ) {
			$products[$key]['quantity_to_buy'] = max( $total_quantity - $operation_stock, 0 );
		} else {
			$products[$key]['quantity_to_buy'] = '';
		}
	}

  return $products;
}

/**
 *  Check if product should be counted based on its categories
 */
function myfct_is_relevant_product( $product, $to_weight_only = false ) {
  $category_ids = $product->get_category_ids();
  $product_id = $product->get_id();
  $is_product_to_count = false;

  $categories_to_count = array( 153, 234 );

  foreach ( $category_ids as $category_id ) {
    foreach ( $categories_to_count as $category_id_to_count ) {
      if ( $category_id == $category_id_to_count ) {
        $is_product_to_count = true;
      }
    }
  }

  if ( $to_weight_only && $is_product_to_count ) {

    if ( has_term( 715, 'pq_inventory_type', $product_id) ) {
      $is_product_to_count = true;
    } else {
      $is_product_to_count = false;
    }
  }

  return $is_product_to_count;
}


/**
 * Enable orddd timestamp for wc_get_orders query 
 */
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'handle_custom_query_var', 10, 2 );

function handle_custom_query_var( $query, $query_vars ) {
  if ( !empty( $query_vars[ '_shipping_date' ] ) ) {
	  
	$delivery_date_obj = new DateTime( esc_attr($query_vars[ '_shipping_date' ]) );
	$delivery_timestamp = $delivery_date_obj->getTimestamp();

    $query[ 'meta_query' ] = array(
		'relation' => 'OR',
		array(
			'key' => '_orddd_timestamp',
			'value' => $delivery_timestamp,
		),
		array(
			'key' => '_shipping_date',
			'value' => esc_attr( $query_vars[ '_shipping_date' ] ),
		),
	);
  }

  return $query;
}

/**
 * Get order created date +1 second
 */
function pq_get_order_created_date($import_after_order) {
	$order = wc_get_order( $import_after_order );

	$date_created = new DateTime( $order->get_date_created() );
	$date_created_timestamp = $date_created->format( 'U' );
	$import_after_timestamp = $date_created_timestamp + 1;

	return $import_after_timestamp;
}

/**
 * Get relevant orders
 */
function myfct_get_relevant_orders( $delivery_date_raw, $import_after_order = "" ) {
	$delivery_date_obj = new DateTime( $delivery_date_raw );
	if ( empty($import_after_order) ) {
		$export_start_date_obj = new DateTime( '- 30 weeks ' );
		$export_start_date = $export_start_date_obj->format( 'y-m-d' );
	} else {
		$export_start_date = pq_get_order_created_date($import_after_order);
	}
	
	$export_end_date_obj = new DateTime( 'tomorrow' );

	$delivery_date = $delivery_date_obj->format('Y-m-d');
	$export_end_date = $export_end_date_obj->format( 'y-m-d' );

	$query = array(
		'status' => 'wc-processing',
		'limit' => -1,
		'date_created' => $export_start_date . '...' . $export_end_date,
		'_shipping_date' => $delivery_date,
	);

	$orders = wc_get_orders( $query );

	return $orders;
}


/**
 * Get relevant orders for today delivery
 */
function pq_get_relevant_orders_today() {
  $timezone = new DateTimeZone( get_option( 'timezone_string' ) );
  $default_date_obj = new DateTime( 'today', $timezone );
  $default_date = $default_date_obj->format( 'Y-m-d' );
  $orders = myfct_get_relevant_orders( $default_date );

  return $orders;
}


/**
 * 
 * Get specific information from order or product
 * 
 */

/**
 * Get the special delivery number
 */
function myfct_get_special_product_number( $category_ids ) {
  $special_product_categories = array(
    381 => 2, //Plantes
    382 => 2, //Bouquets
    398 => 2, //Potager et jardin
    234 => 3, //Paniers cadeaux
  );

  //Set to empty if no match found
  $special_delivery_number_output = '';

  //Loop through products category ids	
  foreach ( $category_ids as $category_id ) {
    foreach ( $special_product_categories as $special_category_id => $special_delivery_number )
      if ( $category_id == $special_category_id ) {
        $special_delivery_number_output = $special_delivery_number;
      }
  }

  return $special_delivery_number_output;
}


/**
 * Check if is first order
 */
function pq_is_first_order( $billing_email, $order_date ) {
  $deadline_difference_days = 180;

  $order_timestamp = $order_date->format( 'U' );

  $deadline_difference_seconds = $deadline_difference_days * 24 * 60 * 60;
  $deadline_timestamp = time() - $deadline_difference_seconds;
  $ten_minutes_before_order = $order_timestamp - 600;

  $last_order = wc_get_orders( array(
    'billing_email' => $billing_email,
    'date_created' => $deadline_timestamp . '...' . $ten_minutes_before_order,
    'limit' => 1,
  ) );

  if ( empty( $last_order ) ) {
    return 1;
  } else {
    return '';
  }
}

/**
 * Get route numbers with sequence from TrackPOD
 */
function pq_get_route_no( $order_id ) {
  $url = 'https://api.track-pod.com/Order/Number/' . $order_id;
  
  $response = wp_remote_get( $url, array(
    'method' => 'GET',
    'httpversion' => '1.0',
    'headers' => array(
      'Content-Type' => 'application/json',
      'X-API-KEY' => '534f2b64-1171-40a2-9942-b4a6c2c8e61b',
    ),
  ));

  $trackpod_data = json_decode($response['body']);

  $route_no = $trackpod_data->RouteNumber;
  $order_sequence = $trackpod_data->SeqNumber;
  $route_no_full = $route_no . '--' . sprintf("%02d", $order_sequence);

  return $route_no_full;
}

/**
 * 
 * Get list of info from order or product
 * 
 */

/**
 * Get all orders info for labels
 */
function pq_get_orders_info_array( $order ) {

  $order_id = $order->get_id();
  $order_meta = array();

  $route_no_full = pq_get_route_no( $order_id );

  if ( ! empty($order->get_shipping_address_2()) ) { //If there is apt number
    $full_delivery_address = $order->get_shipping_address_1() . ', ' . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode() . ', ' . $order->get_shipping_country();
  } else { //Without apt number
    $full_delivery_address = $order->get_shipping_address_1() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode() . ', ' . $order->get_shipping_country();
  }

  $pickup_location_meta =  get_post_meta($order_id, 'pq_pickup_datetime', true);
  if ( empty($pickup_location_meta) ) {
    $delivery_address = $full_delivery_address;
    if ( empty($order->get_billing_company()) ) {
      $order_meta['delivery_type'] = 'delivery';
    } else {
      $order_meta['delivery_type'] = 'business';
    }
  } else {
    $shipping_items = $order->get_items( 'shipping' );
    $shipping_item = reset($shipping_items);
    
    $pickup_location_adress = $shipping_item->get_meta( '_pickup_location_address' );
    $delivery_address = $pickup_location_adress['address_1'] . ', ' . $pickup_location_adress['city'] . ', ' . $pickup_location_adress['state'] . ', ' . $pickup_location_adress['postcode'] . ', ' . $pickup_location_adress['country'];

    $order_meta['delivery_type'] = 'pickup';
  }

  $client_name = $order->get_formatted_shipping_full_name();
  $phone = $order->get_billing_phone();
  $delivery_note = sanitize_text_field( $order->get_customer_note() );

  $order_date = $order->get_date_created();
  $email = $order->get_billing_email();
  $is_first_order = pq_is_first_order($email, $order_date);

  if ($is_first_order) {
    $order_meta['is_first_order'] = true;
  } else {
    $order_meta['is_first_order'] = false;
  }

  $order_meta['has_special_product'] = false;
  foreach( $order->get_items() as $item_id => $item ) {
    $product_id = $item->get_product_id();
    $product = wc_get_product( $product_id );

    //Get only products to add to export
    if ( myfct_is_relevant_product($product) ) {
      $is_special_item = get_post_meta($product_id, '_pq_special_delivery', true);
      if ( ! empty($is_special_item) ) {
        $order_meta['has_special_product'] = true;
      }
    }
  }

  $order_array = array(
    'route_no_full' => $route_no_full,
    'order_id' => '#' . $order_id,
    'client_name' => utf8_decode($client_name),
    'phone' => utf8_decode($phone),
    'full_delivery_address' => utf8_decode($delivery_address),
    'delivery_note' => utf8_decode($delivery_note),
    'order_meta' => $order_meta,
  );

  return $order_array;
}


/**
 * Get list of products in an array for each order
 */
function pq_get_product_lines_array( $order ) {

  $product_lines = array();

  foreach( $order->get_items() as $item_id => $item ) {
    $product_id = $item->get_product_id();
    $product = wc_get_product( $product_id );

    //Get only products to add to export
    if ( myfct_is_relevant_product($product) ) {
              
      //Get item info
      $item_quantity_before_refund = $item->get_quantity();
      $item_quantity_refunded = $order->get_qty_refunded_for_item( $item_id );
      $item_quantity = $item_quantity_before_refund + $item_quantity_refunded;

      if ( $item->get_variation_id() !== 0 ) {
        $variation_id = $item->get_variation_id();
        $product_short_name = get_post_meta($variation_id, '_short_name', true);
        $product_lot_quantity = get_post_meta($variation_id, '_lot_quantity', true);
        $product_weight = get_post_meta( $variation_id, '_pq_weight', true );
        $product_unit = get_post_meta( $variation_id, '_lot_unit', true );
      } else {
        $product_short_name = get_post_meta($product_id, '_short_name', true);
        $product_lot_quantity = get_post_meta($product_id, '_lot_quantity', true);
        $product_weight = get_post_meta( $product_id, '_pq_weight', true );
        $product_unit = get_post_meta( $product_id, '_lot_unit', true );
      }

      $product_packing_priority = get_post_meta($product_id, '_packing_priority', true);

      if ( $product_lot_quantity == 1 ) {
        $product_lot_quantity = '';
      }

      $product_line = array( array( 
        'product_short_name' => utf8_decode($product_short_name),
        'item_quantity' => $item_quantity,
        'product_lot_quantity' => $product_lot_quantity,
        'packing_priority' => $product_packing_priority,
      ));

      $product_lines = array_merge($product_lines, $product_line);
    }
  }

  $product_short_name_columns = array_column($product_lines, 'product_short_name');
  $packing_priority_columns = array_column($product_lines, 'packing_priority');
  array_multisort($packing_priority_columns, SORT_ASC, SORT_NUMERIC, $product_short_name_columns, $product_lines);

  return $product_lines;
}


/**
 * 
 * Print content onto a worksheet
 * 
 */
function pq_print_on_sheet( $sheet, $product_rows, $low_priority, $high_priority, $to_print, $products_to_print = '', $commercial_zone_to_print = '' ) {

  $row = 2;
  foreach ( $product_rows as $product_row ) {
    $column = 1;
    $packing_priority = $product_row['_packing_priority'];
    $inventory_type = $product_row['pq_inventory_type'];
    $commercial_zone_string = $product_row['pq_commercial_zone'];

    if ( $packing_priority >= $low_priority && $packing_priority <= $high_priority 
    && ((empty($inventory_type) && empty($products_to_print)) 
    || in_array($products_to_print, $inventory_type)) 
    && (empty($commercial_zone_to_print) || empty($commercial_zone_string) || (!empty($commercial_zone_to_print) && strpos($commercial_zone_string, $commercial_zone_to_print) !== false)) ) {
      foreach ( $to_print as $to_print_key ) {
        $sheet->setCellValueByColumnAndRow($column, $row, $product_row[$to_print_key]);
        $column++;
      }
      $row++;
    }
  }
}