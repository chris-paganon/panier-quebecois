<?php
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Early enable customer WC_Session in case it is not enabled yet
 */ 
add_action( 'init', 'wc_session_enabler' );
function wc_session_enabler() {
    if ( ! is_admin() && ! is_user_logged_in() && (isset(WC()->session) && WC()->session != null && ! WC()->session->has_session()) ) {
        WC()->session->set_customer_session_cookie( true );
    }
}

/**
 * Add a popup to select the delivery zone
 */
add_action( 'wp_footer', 'pq_delivery_zone_popup' );

function pq_delivery_zone_popup() {
  if ( !isset( $_COOKIE['pq_delivery_zone'] ) || $_COOKIE['pq_delivery_zone'] == '0' ) {
    // todo: do not show if customer is logged in & has a postal code set
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
  WC()->customer->set_shipping_postcode($postal_code);
  WC()->customer->set_billing_postcode($postal_code);

  setcookie( 'pq_delivery_zone', preg_replace('/\s+/', '', $postal_code), time() + (86400 * 30), '/' );

  echo $postal_code;
  wp_die();
}

add_action('wp_footer', 'pq_test_cart');

function pq_test_cart() {

  // Use this to match the shipping zone to the postal code & display the products accordingly
  $shipping_postcode = WC()->customer->get_shipping_postcode();
  $billing_postcode = WC()->customer->get_billing_postcode();

  $postcode = ! empty($shipping_postcode) ? $shipping_postcode : $billing_postcode;
	$shipping_zones = WC_Shipping_Zones::get_zones();

  error_log('postcode:' . print_r($postcode, true));
  error_log('shipping_zones:' . print_r($shipping_zones, true));
}