<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/*
 *
 *
 * Export functions helpers
 *
 *
 */

/* ----- Validate delivery date before exporting ----- */
function myfct_validate_delivery_date( $delivery_date_raw ) {
  $error = '';

  if ( empty( $delivery_date_raw ) ) {
    $error = esc_html__( 'Erreur: Entrez une date de livraison' );
  } else {
    $delivery_date_raw_year = substr( $delivery_date_raw, 0, 4 );
    $delivery_date_raw_month = substr( $delivery_date_raw, 5, 2 );
    $delivery_date_raw_day = substr( $delivery_date_raw, 8, 2 );
    $delivery_date_raw_length = strlen( $delivery_date_raw );

    if ( !( is_numeric( $delivery_date_raw_year ) && is_numeric( $delivery_date_raw_month ) && is_numeric( $delivery_date_raw_day ) && $delivery_date_raw_length == 10 ) ) {
      $error = esc_html__( 'Erreur: Entrez un format de date valide: AAAA-MM-JJ. Exemple: 2020-12-24' );
    } elseif ( $delivery_date_raw_month > 12 || $delivery_date_raw_month < 1 ) {
      $error = esc_html__( 'Le mois doit être compris entre 1 et 12' );
    } elseif ( $delivery_date_raw_day > 31 || $delivery_date_raw_day < 1 ) {
      $error = esc_html__( 'Le jour doit être compris entre 1 et 31' );
    }
  }

  return $error;
}

/**
 * Validate order number
 */
function pq_validate_order_number( $import_after_order ) {
	$error = '';

	$order_number_length = strlen((string) $import_after_order);
	if ( filter_var($import_after_order, FILTER_VALIDATE_INT) === false ) {
		$error = "Le numéro de commande n'est pas un nombre entier";
	} elseif ( $order_number_length !== 5 ) {
		$error = "Le numéro de commande n'est pas un nombre à 5 chiffres";
	}

	return $error;
}

/* ----- Check if product should be counted based on its categories ----- */
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


/* ----- Enable orddd timestamp for wc_get_orders query ------ */
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

/* ----- Get relevant orders ----- */
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
		'status' => array( 'wc-processing', 'wc-completed' ),
		'limit' => -1,
		'date_created' => $export_start_date . '...' . $export_end_date,
		'_shipping_date' => $delivery_date,
	);

	$orders = wc_get_orders( $query );

	return $orders;
}


/* ----- Get products SKUs and QTYs of relevant orders ----- */
function myfct_get_products_quantities( $orders, $to_weight_only = false ) {
  $products = array();

  foreach ( $orders as $order ) {
    foreach ( $order->get_items() as $item_id => $item ) {
      $product = wc_get_product( $item->get_product_id() );

      if ( myfct_is_relevant_product( $product, $to_weight_only ) ) {

        if ( $item->get_variation_id() !== 0 ) {
          $new_id = $item->get_variation_id();
        } else {
          $new_id = $product->get_id();
        }

        $new_quantity_before_refund = $item->get_quantity();
        $quantity_refunded = $order->get_qty_refunded_for_item( $item_id );
        $new_quantity = $new_quantity_before_refund + $quantity_refunded;

        $is_new_id = true;

        foreach ( $products as $id => $quantity ) {
          if ( $new_id == $id ) {
            $products[ $id ] += $new_quantity;
            $is_new_id = false;
          }
        }

        if ( $is_new_id ) {
          $new_product = array( $new_id => $new_quantity );
          $products += $new_product;
        }
      }
    }
  }

  return $products;
}


/* ----- Get the special delivery number ------ */
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

/* ----- Export the csv ----- */
function myfct_export_csv( $filename, $csv ) {
  header( 'Content-Type: application/csv;charset=UTF-8' );
  header( 'Content-Disposition: attachment; filename=' . $filename );
  header( 'Cache-Control: no-cache' );
  header( "Expires: 0" );

  ob_end_clean();

  //echo "\xEF\xBB\xBF"; (solves characters issues on windows)
  //echo "sep=,\n";  (solves seperator issues on MAC OS)

  $csv_string = '';

  foreach ( $csv as $line ) {
    $csv_string .= implode( "\t", $line ) . "\n";
  }

  $csv_encoded = mb_convert_encoding( $csv_string, 'UTF-16LE', 'UTF-8' );
  echo chr( 255 ) . chr( 254 ) . $csv_encoded;

  exit;
}


/*
 *
 *
 * Exporting functions
 *
 *
 */

/* ----- Export purchasing to csv ------ */
function myfct_purchasing_export( $delivery_date_raw, $import_after_order = "" ) {

  $orders = myfct_get_relevant_orders( $delivery_date_raw, $import_after_order );
  $orders_count = count( $orders );
  $last_order = reset($orders);
  $last_order_number = $last_order->get_id();
  $products = pq_get_product_rows( $orders );

	$short_name_columns = array_column($products, '_short_name');
	$supplier_column = array_column($products, 'supplier');
	$commercial_zone_column = array_column($products, 'pq_commercial_zone');
	array_multisort($commercial_zone_column, SORT_ASC, SORT_STRING, $supplier_column, $short_name_columns, $products);

	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

	$commercial_zones_to_print = get_terms( array(
    'taxonomy' => 'pq_commercial_zone',
    'hide_empty' => false,
  ));
	
	foreach ( $commercial_zones_to_print as $key => $commercial_zone_to_print ) {
		$commercial_zone_to_print_name = $commercial_zone_to_print->name;
		if ( $key === 0 ) {
			$to_purchase_sheet = $spreadsheet->getActiveSheet();
		} else {
  		$new_sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $commercial_zone_to_print_name);
			$to_purchase_sheet = $spreadsheet->addSheet($new_sheet, 0);
		}
		$to_purchase_sheet->setTitle($commercial_zone_to_print_name);
	
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
	
		$to_purchase_sheet->setCellValue('A1', 'Zone');
		$to_purchase_sheet->setCellValue('B1', 'Marchand');
		$to_purchase_sheet->setCellValue('C1', 'SKU');
		$to_purchase_sheet->setCellValue('D1', 'Nom court');
		$to_purchase_sheet->setCellValue('E1', 'Référence fournisseur');
		$to_purchase_sheet->setCellValue('F1', 'Conso');
		$to_purchase_sheet->setCellValue('G1', 'Unité');
		$to_purchase_sheet->setCellValue('H1', 'Stock');
		$to_purchase_sheet->setCellValue('I1', 'Ordre de prio');
		$to_purchase_sheet->setCellValue('J1', 'Auto email/SMS');
		$to_purchase_sheet->setCellValue('L1', 'No de commandes');
		$to_purchase_sheet->setCellValue('M1', $orders_count);
		$to_purchase_sheet->setCellValue('N1', 'Dernière commande:');
		$to_purchase_sheet->setCellValue('O1', $last_order_number);
	
		pq_print_on_sheet( $to_purchase_sheet, $products, 1, 999, $to_print, '', $commercial_zone_to_print_name );
	}

	pq_style_sheets($spreadsheet);

	$file_name = 'listes-achats';
	pq_export_excel($spreadsheet, $file_name);
}

/* ----- Export products to weight to csv ------ */

function myfct_products_to_weight_export( $delivery_date_raw ) {
  //Get the time for the filename
  $timezone = new DateTimeZone( get_option( 'timezone_string' ) );
  $now = new DateTime( '', $timezone );
  $filename = 'Peser ' . $now->format( 'Y-m-d G:i:s' ) . '.csv';

  $orders = myfct_get_relevant_orders( $delivery_date_raw );
  $products = myfct_get_products_quantities( $orders, true );

  $csv = array( array( 'Nom Court', 'Quantité totale', 'Unité' ) );

  foreach ( $products as $product_id => $quantity ) {
    $product = wc_get_product( $product_id );

    $short_name = $lot_unit = '';
    $lot_quantity = 0;

    $short_name = get_post_meta( $product_id, '_short_name', true );
    $weight = get_post_meta( $product_id, '_pq_weight', true );
    $unit = get_post_meta( $product_id, '_lot_unit', true );
		$weight_with_unit = $weight . $unit;
    $lot_quantity = get_post_meta( $product_id, '_lot_quantity', true );

    if ( $product->get_type() == 'variation' ) {
      $product_id = $product->get_parent_id();
    }

    $total_quantity = $quantity * $lot_quantity;

    $product_line = array( array( $short_name, $total_quantity, $weight_with_unit ) );
    $csv = array_merge( $csv, $product_line );
  }

  myfct_export_csv( $filename, $csv );
}

/* ----- Export orders to csv ------ */
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