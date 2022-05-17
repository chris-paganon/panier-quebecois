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
    $is_to_weight = get_post_meta( $product_id, '_is_to_wheight', true );
    if ( $is_to_weight == 1 ) {
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
		'status' => 'wc-processing',
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

  $timezone = new DateTimeZone( get_option( 'timezone_string' ) );
  $now = new DateTime( '', $timezone );
  $filename = 'Liste Achats ' . $now->format( 'Y-m-d G:i:s' ) . '.csv';

  $orders = myfct_get_relevant_orders( $delivery_date_raw, $import_after_order );
  $orders_count = count( $orders );
  $last_order = reset($orders);
  $last_order_number = $last_order->get_id();
  $products = myfct_get_products_quantities( $orders );

  $csv = array( array( 'Zone', 'Marchand', 'SKU', 'Nom Court', 'Référence fournisseur', 'Quantité', 'Quantité par lot', 'Quantité totale', 'Unité', 'ordre de Priorité', '', 'No de commandes:', $orders_count, 'Derniere commande:', $last_order_number ) );

  foreach ( $products as $product_id => $quantity ) {
    $product = wc_get_product( $product_id );

    $tags = $name = $short_name = $lot_unit = '';
    $lot_quantity = $packing_priority = 0;

    $short_name = get_post_meta( $product_id, '_short_name', true );
    $lot_quantity = get_post_meta( $product_id, '_lot_quantity', true );
    $weight = get_post_meta( $product_id, '_pq_weight', true );
    $unit = get_post_meta( $product_id, '_lot_unit', true );
	$weight_with_unit = $weight . $unit;

    if ( $product->get_type() == 'variation' ) {
      $product_id = $product->get_parent_id();
    }

    $tags = wp_get_post_terms( $product_id, 'pq_distributor', array( 'fields' => 'names' ) );
	
    if ( empty( $tags ) ) {
	    $tags = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
      if ( empty( $tags ) ) {
        $tags = wp_get_post_terms( $product_id, 'pq_producer', array( 'fields' => 'names' ) );
      }
    }

    $tags_string = implode( ', ', $tags );

	$sku = $product->get_sku();
	
    $commercial_zone = wp_get_post_terms( $product_id, 'pq_commercial_zone', array( 'fields' => 'names' ) );
    $commercial_zone_string = implode( ', ', $commercial_zone );
	
    $reference_name = get_post_meta( $product_id, '_pq_reference', true );
    $packing_priority = get_post_meta( $product_id, '_packing_priority', true );

    $total_quantity = $quantity * $lot_quantity;

    $product_line = array( array( $commercial_zone_string, $tags_string, $sku, $short_name, $reference_name, $quantity, $lot_quantity, $total_quantity, $weight_with_unit, $packing_priority ) );
    $csv = array_merge( $csv, $product_line );
  }

  myfct_export_csv( $filename, $csv );
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

	//Pickup locations array
	$pickup_location_addresses = array (
		'Jean-Talon: Dépanneur Amitié, 421 rue Bélanger, Montréal, QC, H2S 1G3, Canada'               =>   '421 rue Bélanger, Montréal, QC H2S 1G3',
		'Westmount: Evelyne Boutique, 5127 rue Sherbrooke O, Montréal, QC, H4A 1T3, Canada'           =>   '5127 rue Sherbrooke O, Montréal, QC, H4A 1T3, Canada',
		'Villeray: Dépanneur Varin, 302 rue Faillon E, Montréal, QC, H2R 1K9, Canada'                 =>   '302 rue Faillon E, Montréal, QC, H2R 1K9, Canada',
		'Plaza St-Hubert: Dépanneur De La Plaza, 7355 rue St Hubert, Montréal, QC, H2R 2N4, Canada'   =>   '7355 rue St Hubert, Montréal, QC, H2R 2N4, Canada',
		'Laurier: Super Depanneur Bon-Air Enr, 4918 rue St Denis, Montréal, QC, H2J 2L6, Canada'      =>   '4918 rue St Denis, Montréal, QC, H2J 2L6, Canada',
		'Plateau: Dépanneur Lily, 4348 rue Rivard, Montréal, QC, H2J 2M8, Canada'                     =>   '4348 rue Rivard, Montréal, QC, H2J 2M8, Canada',
		'Verdun: Dépanneur Wu, 410 rue Caisse, Verdun, QC, H4G 2C7, Canada'                           =>   '410 rue Caisse, Verdun, QC, H4G 2C7, Canada',
		'Marché Jean-Talon, Entrée principale, Avenue Henri-Julien'                                   =>   '7070 Avenue Henri Julien, Montreal, QC, H2S 3S3, Canada',
		'Laval: Dépanneur Saint Hubert, 175 rue St Hubert, Laval, QC, H7G 2Y3, Canada'                =>   '175 rue St Hubert, Laval, QC, H7G 2Y3, Canada',
	);

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
		$pickup_location_meta =  get_post_meta($order_id, 'Point de collecte', true);

		if ( empty($pickup_location_meta) ) {
			$delivery_address = $full_delivery_address;
		} else {

			$special_delivery_number = 1;

			foreach ( $pickup_location_addresses as $pickup_location_raw => $pickup_location_adress ) {
				if ( $pickup_location_meta == $pickup_location_raw) {
					$delivery_address = $pickup_location_adress;
				}
			}
		}

		//Get delivery priority according to timeslots
		$delivery_timeslot_string = get_post_meta($order_id, '_orddd_time_slot', true);
		$delivery_start_hour = floatval( substr($delivery_timeslot_string, 0, 2) );
		$delivery_start_minutes = floatval( substr($delivery_timeslot_string, 3, 2) ) / 60;
		$delivery_start_time = $delivery_start_hour + $delivery_start_minutes;

		$cutoff_time_to_split = 16;

		if ( $delivery_start_time < $cutoff_time_to_split ) {
			$delivery_priority = 1;
		} else {
			$delivery_priority = 2;
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
			));
	
			$csv = array_merge($csv, $product_line);
	
			$excel_row_no++;
		}
	}

	myfct_export_csv($filename, $csv);
}