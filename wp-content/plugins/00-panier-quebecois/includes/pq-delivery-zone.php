<?php
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Add a popup to select the delivery zone
 */
add_action( 'wp_footer', 'pq_delivery_zone_popup' );

function pq_delivery_zone_popup() {
  if ( !isset( $_COOKIE['pq_delivery_zone'] ) || $_COOKIE['pq_delivery_zone'] == '0' ) {
    $args = array();
    wc_pq_get_template( 'popup/delivery-zone-select.php', $args );
  }
}


/**
 * Get delivery zone from postal code in AJAX
 */
add_action( 'wp_ajax_pq_get_delivery_zone', 'pq_get_delivery_zone_with_ajax' );
add_action( 'wp_ajax_nopriv_pq_get_delivery_zone', 'pq_get_delivery_zone_with_ajax' );

function pq_get_delivery_zone_with_ajax() {
	if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pq-delivery-zone-nonce')) wp_die();

  $postal_code = sanitize_text_field( $_POST['postal_code'] );
  $all_zones = WC_Shipping_Zones::get_zones();
  // $delivery_zone = pq_get_delivery_zone( $postal_code );

  echo $postal_code;
  wp_die();
}