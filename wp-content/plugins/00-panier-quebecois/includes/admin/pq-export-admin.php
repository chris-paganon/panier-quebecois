<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}


/**
 *  Export purchasing
 */
function myfct_purchasing_export( $delivery_date_raw, $import_after_order = "" ) {

  $orders = myfct_get_relevant_orders( $delivery_date_raw, $import_after_order );
  $orders_count = count( $orders );
  $last_order = reset($orders);
  $last_order_number = $last_order->get_id();
  $products = pq_get_product_rows( $orders );
	$products = pq_add_quantity_to_buy_to_products($products);

	$short_name_columns = array_column($products, '_short_name');
	$supplier_column = array_column($products, 'supplier');
	$commercial_zone_column = array_column($products, 'pq_commercial_zone');
	array_multisort($commercial_zone_column, SORT_ASC, SORT_STRING, $supplier_column, $short_name_columns, $products);

	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

	$to_print = array(
		'pq_commercial_zone', 
		'supplier', 
		'sku',
		'_short_name',
		'_pq_reference',
		'total_quantity',
		'_lot_unit',
		'_pq_operation_stock',
		'quantity_to_buy',
		'_packing_priority',
		'supplier_auto_order_string',
	);

	$products_to_print = 'all';

	$current_sheet = $spreadsheet->getActiveSheet();

	pq_set_purchasing_column_default_titles($current_sheet);
	$current_sheet->setCellValue('M1', 'No de commandes');
	$current_sheet->setCellValue('N1', $orders_count);
	$current_sheet->setCellValue('O1', 'Dernière commande:');
	$current_sheet->setCellValue('P1', $last_order_number);

	$commercial_zone_to_print_name = 'Jean-Talon';
	$current_sheet->setTitle($commercial_zone_to_print_name);
	pq_print_on_sheet( $current_sheet, $products, 1, 999, $to_print, $products_to_print, $commercial_zone_to_print_name );

	$commercial_zones_to_print = get_terms( array(
    'taxonomy' => 'pq_commercial_zone',
    'hide_empty' => false,
		'exclude' => array(504, 805), //Exclude Marché Jean-Talon and Pourtour Marché Jean-Talon, they are in the first sheet just above
  ));
	
	foreach ( $commercial_zones_to_print as $key => $commercial_zone_to_print ) {
		$commercial_zone_to_print_name = $commercial_zone_to_print->name;

		$new_sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $commercial_zone_to_print_name);
		$current_sheet = $spreadsheet->addSheet($new_sheet, 0);
		pq_set_purchasing_column_default_titles($current_sheet);
		$current_sheet->setTitle($commercial_zone_to_print_name);
	
		pq_print_on_sheet( $current_sheet, $products, 1, 999, $to_print, $products_to_print, $commercial_zone_to_print_name );
	}

	pq_style_sheets($spreadsheet);

	$file_name = 'listes-achats';
	pq_export_excel($spreadsheet, $file_name);
}


/**
 * Set purchasing export default column names
 */
function pq_set_purchasing_column_default_titles($current_sheet) {
	$current_sheet->setCellValue('A1', 'Zone');
	$current_sheet->setCellValue('B1', 'Marchand');
	$current_sheet->setCellValue('C1', 'SKU');
	$current_sheet->setCellValue('D1', 'Nom court');
	$current_sheet->setCellValue('E1', 'Référence fournisseur');
	$current_sheet->setCellValue('F1', 'Conso');
	$current_sheet->setCellValue('G1', 'Unité');
	$current_sheet->setCellValue('H1', 'Stock');
	$current_sheet->setCellValue('I1', 'Besoin');
	$current_sheet->setCellValue('J1', 'Ordre de prio');
	$current_sheet->setCellValue('K1', 'Commande');
}


/**
 *  Export orders to csv
 */
function myfct_orders_export($delivery_date_raw) {

	//Get the time for the filename
	$timezone = new DateTimeZone(get_option('timezone_string'));
	$now = new DateTime('', $timezone);
	$filename = 'Commandes ' . $now->format('Y-m-d G:i:s') . '.csv';

	$orders = myfct_get_relevant_orders( $delivery_date_raw );

	//csv headings (first line)
	$csv = array(array(
		'Date commandé',
		'No de commande',
		'Client',
		'Adresse livraison',
		'Téléphone',
		'Note',
		'Email',
		'Produit',
		'Nom court',
		'Quantité',
		'Priorité',
		'Quantité par lot',
		'Livraison spéciale',
		'Produit spéciale',
		'Priorité de livraison',
		'Première commande',
	));

	//Loop through orders
	foreach ( $orders as $order ) {

		//Initialize variables to 0 or empty string
		$order_date = $client_name = $phone = $phone_and_note = $product_tags = $product_name = $product_short_name = $product_weight_with_unit = $delivery_address = $pickup_location = $special_delivery_number = '';
		$order_id = $item_quantity = $product_lot_quantity = $product_packing_priority = 0;

		//Get order info
		$order_date = $order->get_date_created();
		$order_id = $order->get_id();
		$client_name = $order->get_formatted_shipping_full_name();
		$phone = $order->get_billing_phone();
		$email = $order->get_billing_email();
		$delivery_note = sanitize_text_field( $order->get_customer_note() );

		//Get notes and gift notes
		$is_gift = get_post_meta($order_id, 'is_gift', true);
		if ( ! empty($is_gift)) {

			$gift_note = sanitize_text_field( get_post_meta($order_id, 'gift_note', true) );

			if ( !empty($gift_note) && !empty($delivery_note) ) {
				$delivery_note = 'Note cadeau: ' . $gift_note . ' Note livraison: ' . $delivery_note;
			} elseif ( !empty($gift_note) && empty($delivery_note) ) {
				$delivery_note = 'Note cadeau: ' . $gift_note;
			} elseif ( empty($gift_note) && !empty($delivery_note) ) {
				$delivery_note = 'Note livraison: ' . $delivery_note;
			}
		}

		//Get shipping adress info
		if ( ! empty($order->get_shipping_address_2()) ) { //If there is apt number
			$full_delivery_address = $order->get_shipping_address_1() . ', ' . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode() . ', ' . $order->get_shipping_country();
		} else { //Without apt number
			$full_delivery_address = $order->get_shipping_address_1() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode() . ', ' . $order->get_shipping_country();
		}

		//Loop through items in each order to get special delivery number
		foreach( $order->get_items() as $item_id => $item ) {
			$product_id = $item->get_product_id();
			$is_special_product = get_post_meta($product_id, '_pq_special_delivery', true);

			if ( ! empty($is_special_product) ) {
				$special_delivery_number = 2;
			}
		}
		
		//Make delivery adress the pickup location if pickup was selected
		$pickup_location_meta =  get_post_meta($order_id, 'pq_pickup_datetime', true);

		if ( empty($pickup_location_meta) ) {
			$delivery_address = $full_delivery_address;
		} else {

			$special_delivery_number = 1;

			$shipping_items = $order->get_items( 'shipping' );
			$shipping_item = reset($shipping_items);
			
			$pickup_location_adress = $shipping_item->get_meta( '_pickup_location_address' );

			$delivery_address = $pickup_location_adress['address_1'] . ', ' . $pickup_location_adress['city'] . ', ' . $pickup_location_adress['state'] . ', ' . $pickup_location_adress['postcode'] . ', ' . $pickup_location_adress['country'];
		}

		//Special delivery number for B2B orders
		if ( ! empty($order->get_billing_company()) ) {
			$special_delivery_number = 3;
		}

		//Get delivery priority according to timeslots
		$delivery_timeslot_array = get_post_meta($order_id, '_delivery_time_frame', true);

		if ( empty( $delivery_timeslot_array ) ) {
			$delivery_priority = 1;
		} else {
			$delivery_timeslot_start_string = $delivery_timeslot_array['time_from'];
			$delivery_start_hour = floatval( substr($delivery_timeslot_start_string, 0, 2) );
			$delivery_start_minutes = floatval( substr($delivery_timeslot_start_string, 3, 2) ) / 60;
			$delivery_start_time = $delivery_start_hour + $delivery_start_minutes;
			
			$delivery_timeslot_end_string = $delivery_timeslot_array['time_to'];
			$delivery_end_hour = floatval( substr($delivery_timeslot_end_string, 0, 2) );
			$delivery_end_minutes = floatval( substr($delivery_timeslot_end_string, 3, 2) ) / 60;
			$delivery_end_time = $delivery_end_hour + $delivery_end_minutes;
	
			$cutoff_start_time_to_split = 16;
			$cutoff_end_time_to_split = 19.5; //19h30
	
			if ( $delivery_start_time < $cutoff_start_time_to_split && $delivery_end_time > $cutoff_end_time_to_split ) {
				$delivery_priority = 1; //Full ecoresponsible time slot
			} elseif ( $delivery_start_time < $cutoff_start_time_to_split && $delivery_end_time < $cutoff_end_time_to_split ) {
				$delivery_priority = 2; //Afternoon time slot
			}else {
				$delivery_priority = 3; //Evening time slot
			}
		}

		//Get a 1 if is first order
		$is_first_order = pq_is_first_order($email, $order_date);
		
		//Loop through items in each order to get products info
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
					$product_weight_with_unit = $product_weight . $product_unit;
				} else {
					$product_short_name = get_post_meta($product_id, '_short_name', true);
					$product_lot_quantity = get_post_meta($product_id, '_lot_quantity', true);
					$product_weight = get_post_meta( $product_id, '_pq_weight', true );
					$product_unit = get_post_meta( $product_id, '_lot_unit', true );
					$product_weight_with_unit = $product_weight . $product_unit;
				}

				$product_tags = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
				$product_tags_string = implode(', ', $product_tags);
				$product_name = $product->get_title();
				$product_packing_priority = get_post_meta($product_id, '_packing_priority', true);

				$is_special_product = get_post_meta($product_id, '_pq_special_delivery', true);

				if ( ! empty($is_special_product) ) {
					$special_product_number = 2;
				} else {
					$special_product_number = '';
				}

				//Print new line
				$product_line = array(array(
					$order_date,
					$order_id,
					$client_name,
					$delivery_address,
					$phone,
					$delivery_note,
					$email,
					$product_name,
					$product_short_name,
					$item_quantity,
					$product_packing_priority,
					$product_lot_quantity,
					$special_delivery_number,
					$special_product_number,
					$delivery_priority,
					$is_first_order,
				));

				$csv = array_merge($csv, $product_line);
			}
		}
	}

	myfct_export_csv($filename, $csv);	
}


/**
 * Export list of all the products with margins
 */
function pq_export_products() {

	//Get the time for the filename
	$timezone = new DateTimeZone(get_option('timezone_string'));
	$now = new DateTime('', $timezone);
	$filename = 'Produits ' . $now->format('Y-m-d G:i:s') . '.csv';

	$args = array(
		'limit'  => -1,
		'status' => 'publish',
		'type'   => array('simple', 'variable'),
	);

	$products = wc_get_products( $args );

	$csv = array(array(
		'SKU',
		'Marchand',
		'Produit',
		'Nom court',
		'Unite',
		'Prix achat au Kg',
		'Prix achat',
		'Prix marchand',
		'Prix PQ',
		'Quantité en stock',
		'Ordre de priorité',
		'Taux de marge (%)',
		'Rabais marchand (%)',
		'Écart marchand/PQ (%)',
		'Inactif?',
	));

	$excel_row_no = 2;

	foreach ( $products as $product ) {
		$product_id = $product->get_id();

		$product_sku = $product->get_sku();

		$product_tags = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
		if ( empty($product_tags) ) {
			$product_tags = wp_get_post_terms( $product_id, 'pq_producer', array( 'fields' => 'names' ) );
		}
		$product_tags_string = implode(', ', $product_tags);

		$product_name = $product->get_title();
		$product_short_name = get_post_meta($product_id, '_short_name', true);
		$product_weight = get_post_meta( $product_id, '_pq_weight', true );
		$product_unit = get_post_meta( $product_id, '_lot_unit', true );
		$product_weight_with_unit = $product_weight . $product_unit;
		$product_price_kg = get_post_meta($product_id, '_price_per_kg', true);
		$product_purchasing_price = get_post_meta($product_id, '_purchasing_price', true);
		$product_market_price = get_post_meta($product_id, '_market_price', true);
		$product_price = $product->get_regular_price();
		$product_stock_quantity = $product->get_stock_quantity();
		$product_packing_priority = get_post_meta($product_id, '_packing_priority', true);
		$product_inactive = get_post_meta($product_id, '_pq_inactive', true);

		$product_margin = ( $product_price - $product_purchasing_price ) / $product_purchasing_price;
		$product_margin_formula = '=(I'.$excel_row_no.'-G'.$excel_row_no.')/G'.$excel_row_no;

		$seller_discount = ( $product_market_price - $product_purchasing_price ) / $product_market_price;
		$seller_discount_formula = '=(H'.$excel_row_no.'-G'.$excel_row_no.')/H'.$excel_row_no;

		$seller_pq_diff = ( $product_price - $product_market_price ) / $product_market_price;
		$seller_pq_diff_formula = '=(I'.$excel_row_no.'-H'.$excel_row_no.')/H'.$excel_row_no;

		$product_variations = $product->get_children();

		if ( ! empty($product_variations) ) {
			foreach ( $product_variations as $variation_id ) {

				$variation = wc_get_product( $variation_id );

				$variation_short_name = get_post_meta($variation_id, '_short_name', true);
				$variation_weight = get_post_meta( $variation_id, '_pq_weight', true );
				$variation_unit = get_post_meta( $variation_id, '_lot_unit', true );
				$variation_weight_with_unit = $variation_weight . $variation_unit;
				$variation_price_kg = get_post_meta($variation_id, '_price_per_kg', true);
				$variation_purchasing_price = get_post_meta($variation_id, '_purchasing_price', true);
				$variation_market_price = get_post_meta($variation_id, '_market_price', true);
				$variation_price = $variation->get_regular_price();
				$variation_stock_quantity = $variation->get_stock_quantity();

				$product_margin_formula = '=(I'.$excel_row_no.'-G'.$excel_row_no.')/G'.$excel_row_no;
				$seller_discount_formula = '=(H'.$excel_row_no.'-G'.$excel_row_no.')/H'.$excel_row_no;
				$seller_pq_diff_formula = '=(I'.$excel_row_no.'-H'.$excel_row_no.')/H'.$excel_row_no;
				
				$product_line = array(array(
					$product_sku,
					$product_tags_string,
					$product_name,
					$variation_short_name,
					$variation_weight_with_unit,
					$variation_price_kg,
					$variation_purchasing_price,
					$variation_market_price,
					$variation_price,
					$variation_stock_quantity,
					$product_packing_priority,
					$product_margin_formula,
					$seller_discount_formula,
					$seller_pq_diff_formula,
					$product_inactive,
				));
		
				$csv = array_merge($csv, $product_line);
		
				$excel_row_no++;
			}
		} else {
			$product_line = array(array(
				$product_sku,
				$product_tags_string,
				$product_name,
				$product_short_name,
				$product_weight_with_unit,
				$product_price_kg,
				$product_purchasing_price,
				$product_market_price,
				$product_price,
				$product_stock_quantity,
				$product_packing_priority,
				$product_margin_formula,
				$seller_discount_formula,
				$seller_pq_diff_formula,
				$product_inactive,
			));
	
			$csv = array_merge($csv, $product_line);
	
			$excel_row_no++;
		}
	}

	myfct_export_csv($filename, $csv);
}