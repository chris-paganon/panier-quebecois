<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Make stock notifications html instead of plain
 */

add_filter( 'woocommerce_email_headers', 'pq_email_stock_headers', 10, 3 );

function pq_email_stock_headers( $headers, $email, $product ) {
  if ( !$email == 'low_stock' || !$email == 'no_stock' ) return;

  $headers = 'Content-Type: text/html' . "\r\n";

  return $headers;
}


/**
 * Add email recipient for some product categories only
 */
add_filter( 'woocommerce_email_recipient_low_stock', 'pq_add_stock_notif_recipient', 10, 3 );
add_filter( 'woocommerce_email_recipient_no_stock', 'pq_add_stock_notif_recipient', 10, 3 );

function pq_add_stock_notif_recipient( $emails, $product, $null_variable ) {

  if ( has_term( 171, 'product_cat', $product->get_id()) ) {
    $emails .= ', julie@panierquebecois.ca';
  }

  return $emails;
}

/**
 * Add all low stock info in the subject
 */

add_filter( 'woocommerce_email_subject_low_stock', 'pq_email_subject_low_stock', 10, 2 );

function pq_email_subject_low_stock( $subject, $product ) {
  $product_id = $product->get_id();
  $short_name = get_post_meta( $product_id, '_short_name', true );

  $stock = wp_strip_all_tags( $product->get_stock_quantity() );

  $subject = 'Stock faible: ' . $short_name . ' (' . $stock . ')';

  return $subject;
}

/**
 * Add all no stock info in the subject
 */

add_filter( 'woocommerce_email_subject_no_stock', 'pq_email_subject_no_stock', 10, 2 );

function pq_email_subject_no_stock( $subject, $product ) {
  $product_id = $product->get_id();
  $short_name = get_post_meta( $product_id, '_short_name', true );

  $stock = wp_strip_all_tags( $product->get_stock_quantity() );

  $subject = 'Stock rupture: ' . $short_name . ' (' . $stock . ')';

  return $subject;
}

/**
 * Add short name to stock notif content
 */

add_filter( 'woocommerce_email_content_low_stock', 'pq_email_stock_content', 10, 2 );
add_filter( 'woocommerce_email_content_no_stock', 'pq_email_stock_content', 10, 2 );

function pq_email_stock_content( $message, $product ) {
  $product_id = $product->get_id();
  $short_name = get_post_meta( $product_id, '_short_name', true );

  $pq_message = '<h1>Bonjour,</h1><p>' . $message . '</p><p> Nom court: ' . $short_name . '</p><p>Bonne journée,</p><br/><p>Panier Québécois</p>';

  return $pq_message;
}