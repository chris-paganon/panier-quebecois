<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Send SMS to sellers 
 */
function pq_send_seller_sms() {

  $suppliers = get_terms( array(
    'taxonomy' => 'product_tag',
    'hide_empty' => false,
  ));

  foreach ($suppliers as $supplier) {
    $supplier_nos = get_term_meta ( $supplier->term_id, 'pq_seller_sms', true );
    if ( ! empty($supplier_nos) ) {

      $delivery_date_raw = pq_get_current_delivery_date_for_supplier();
      $orders = myfct_get_relevant_orders( $delivery_date_raw, "" );

      $products = pq_get_products_array_for_supplier( $supplier, $orders );

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

  foreach ( $products as $product_id => $quantity ) {
    $product = wc_get_product( $product_id );
    $short_name = get_post_meta( $product_id, '_short_name', true);

    $supplier_order_sms .= "\n";
    $supplier_order_sms .= $short_name . " " . $quantity;

    if ($supplier_needs_units) {
      $weight = get_post_meta( $product_id, '_pq_weight', true );
      $unit = get_post_meta( $product_id, '_lot_unit', true );
      $weight_with_unit = $weight . $unit;

      $supplier_order_sms .= " " . $weight_with_unit;
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