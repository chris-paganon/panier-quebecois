<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

function pq_send_seller_emails() {
  
  $headers = array('Content-Type: text/html; charset=UTF-8');

  $suppliers = get_terms( array(
    'taxonomy' => 'product_tag',
    'hide_empty' => false,
  ));

  foreach ($suppliers as $supplier) {
    $supplier_email = get_term_meta ( $supplier->term_id, 'pq_seller_email', true );
    if ( ! empty($supplier_email) ) {

      $delivery_date_raw = pq_get_current_delivery_date_for_supplier();
      $orders = myfct_get_relevant_orders( $delivery_date_raw, "" );

      $products = pq_get_products_array_for_supplier( $supplier, $orders );

      if ( ! empty($products) ) {
        $supplier_order_html = pq_get_supplier_order_table( $products );
        wp_mail( $supplier_email, $supplier->name, $supplier_order_html, $headers);
      }
    }
  }
}