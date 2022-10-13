<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Send SMS to sellers 
 */
function pq_send_seller_sms($suppliers) {

  foreach ($suppliers as $supplier) {
    $supplier_nos = get_term_meta ( $supplier->term_id, 'pq_seller_sms', true );
    if ( ! empty($supplier_nos) ) {

      $orders = pq_get_relevant_orders_today();
      $products = pq_get_product_rows( $orders, $supplier );
      $products = pq_add_quantity_to_buy_to_products($products);

      $short_name_column = array_column($products, '_short_name');
      $packing_priority_column = array_column($products, '_packing_priority');
      array_multisort($packing_priority_column, SORT_ASC, SORT_STRING, $short_name_column, $products);

      if ( ! empty($products) ) {
        $supplier_needs_units = get_term_meta ( $supplier->term_id, 'pq_seller_needs_units', true );
        $fmt_fr = new IntlDateFormatter( 'fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, NULL, IntlDateFormatter::GREGORIAN, 'EEEE dd MMMM y' );
        $full_date = $fmt_fr->format( time() );
        $supplier_order_sms = pq_get_supplier_sms_message( $products, $full_date, $supplier_needs_units );

        $supplier_nos_arr = explode(',', trim($supplier_nos));
        foreach ( $supplier_nos_arr as $supplier_no ) {
          pq_twilio_send_sms( $supplier_no, $supplier_order_sms );
        }
      }
    }
  }
}


/**
 * Build the supplier SMS message
 */
function pq_get_supplier_sms_message( $products, $full_date, $supplier_needs_units ) {

  $supplier_order_sms = "";
  $supplier_order_sms .= "Bon matin, voici la commande pour " . $full_date;

  foreach ( $products as $product_arr ) {
    $quantity = ! array_key_exists('crates_to_order', $product_arr) ? $product_arr['quantity_to_buy'] : $product_arr['crates_to_order'];
    $short_name = $product_arr['_short_name'];

    if ( $quantity > 0 ) {
      $supplier_order_sms .= "\n";
      $supplier_order_sms .= $short_name . " " . $quantity;
  
      if ($supplier_needs_units) {
        $weight_with_unit = $product_arr['weight'];
        $supplier_order_sms .= " " . $weight_with_unit;
      }
    }
  }

  $supplier_order_sms .= "\n";
  $supplier_order_sms .= "Merci";

  return $supplier_order_sms;
}


/**
 * Send a SMS through Twilio API
 */
function pq_twilio_send_sms( $to, $message ) {
  $sid = TWILIO_ACCOUNT_SID;
  $token = TWILIO_AUTH_TOKEN;
  $from_no = TWILIO_FROM_NO;

  $twilio = new Twilio\Rest\Client($sid, $token);

  $send = $twilio->messages->create($to, array(
    'body' => $message,
    'from' => $from_no,
  ));
}