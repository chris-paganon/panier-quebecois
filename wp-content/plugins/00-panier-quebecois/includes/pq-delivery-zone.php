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

  $matched_zone_id = is_postcode_in_mtl($postal_code);

  if ( $matched_zone_id === false ) {
    setcookie( 'pq_delivery_zone', 'QC', time() + (86400 * 30), '/' );
    echo 'QC';
  } else {
    setcookie( 'pq_delivery_zone', 'MTL', time() + (86400 * 30), '/' );
    echo 'MTL';
  }

  wp_die();
}


/**
 * Check if postal code is in a delivery zone in Montréal
 */
function is_postcode_in_mtl($postal_code) {
  $shipping_zones = WC_Shipping_Zones::get_zones();

  $matched_zone_id = false;
  foreach ($shipping_zones as $zone) {
    $zone_locations = $zone['zone_locations'];
    foreach ($zone_locations as $location) {
      if ($location->type === 'postcode' && is_postcode_match( $postal_code, $location->code )) {
        $matched_zone_id = $zone['id'];
      }
    }
  }

  return $matched_zone_id;
}


/**
 * Check if postal code matches a zone postal code
 */
function is_postcode_match($user_postcode, $zone_postcode) {
  $is_match = false;
  if ( strpos($zone_postcode, '*') !== false ) {
    $zone_postcode = str_replace('*', '', $zone_postcode);
    if ( strpos(substr($user_postcode, 0, 3), $zone_postcode) !== false ) {
      $is_match = true;
    }
  } else {
    if ( $user_postcode === $zone_postcode ) {
      $is_match = true;
    }
  }
  return $is_match;
}


/**
 * Check if a cookie is set for delivery zone outside Montréal
 */
function is_delivery_zone_outside_mtl() {
  if ( isset( $_COOKIE['pq_delivery_zone'] ) && $_COOKIE['pq_delivery_zone'] == 'QC' ) {
    return true;
  } else {
    return false;
  }
}


/**
 * Validate delivery zone in checkout
 */
add_action( 'woocommerce_checkout_process', 'pq_validate_products_shipping' );
add_action( 'woocommerce_before_cart', 'pq_validate_products_shipping' );

function pq_validate_products_shipping() {

  // Get the delivery zone selected by the customer in the cart data
  $shipping_packages = WC()->cart->get_shipping_packages();
  $shipping_zone = wc_get_shipping_zone( reset( $shipping_packages ) );

  if ( $shipping_zone->get_zone_name() !== 'Outside MTL' ) return;

  $cart_has_unavailable_product = false;
  $product_names = '';

  foreach ( WC()->cart->get_cart() as $cart_item ) {
    $product_id = $cart_item[ 'product_id' ];
    if ( get_post_meta($product_id, '_pq_available_long_distance', true) != true ) {
      $cart_has_unavailable_product = true;
      if ( ! empty($product_names) ) {
        $product_names .= ', ';
      }
      $product_names .= get_the_title($product_id);
    }
  }
  
  if ( $cart_has_unavailable_product ) {
    if ( is_cart() ) {
      wc_print_notice(
        sprintf(
          'Les produits suivants sont disponibles uniquement à Montréal, veuillez les retirer de votre panier ou insérer une adresse de livraison à Montréal: %s',
          $product_names
        ),
        'error'
      );
    } else {
      wc_add_notice(
        sprintf(
          'Les produits suivants sont disponibles uniquement à Montréal, veuillez les retirer de votre panier ou insérer une adresse de livraison à Montréal: %s',
          $product_names
        ),
        'error'
      );
    }
  }
}