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
 * Add a popup to select the delivery zone & set the delivery zone cookie
 */
add_action( 'wp_footer', 'pq_delivery_zone_popup' );

function pq_delivery_zone_popup() {

  $postal_code = pq_get_postal_code_from_user();
  if ( !empty($postal_code) ) {
    if ( is_postcode_in_mtl($postal_code) === true ) {
      $delivery_zone_cookie = 'MTL';
    } else {
      $delivery_zone_cookie = 'QC';
    }
    setcookie( 'pq_delivery_zone', $delivery_zone_cookie, time() + (86400 * 30), '/' );
    // If we have a postal code and the delivery zone cookie is not set or is not up to date, refresh if user is on a shop page to make sure they see the right products
    if ( (!isset( $_COOKIE['pq_delivery_zone'] ) || $_COOKIE['pq_delivery_zone'] != $delivery_zone_cookie) && (is_archive() || get_the_ID() == 6720) ) {
      header("refresh: 0;");
    }
  }

  // Show the popup if we are on a shop page and the cookie is not set or was just set above
  if ( (!isset( $_COOKIE['pq_delivery_zone'] ) || $_COOKIE['pq_delivery_zone'] == '0') && empty($postal_code) && (is_archive() || get_the_ID() == 6720) ) {
    $args = array();
    wc_pq_get_template( 'popup/delivery-zone-select.php', $args );
  }
}


/**
 * Get the postal code from the checkout form or from the user if not set at checkout
 */
function pq_get_postal_code_from_user() {
  $postal_code = '';
  if ( WC()->customer->get_billing_postcode() ) {
    $postal_code = WC()->customer->get_billing_postcode();
  } elseif ( is_user_logged_in() ) {
    $user = wp_get_current_user();
    if ( get_user_meta( $user->ID, 'billing_postcode', true ) ) {
      $postal_code = get_user_meta( $user->ID, 'billing_postcode', true );
    }
  }
  $postal_code = strtoupper( str_replace(' ', '', $postal_code) );
  return $postal_code;
}


/**
 * Set the delivery zone cookie when postal code is updated at checkout
 */
add_action( 'woocommerce_checkout_update_order_review', 'pq_update_postal_code_cookie_at_checkout', 10, 1 );

function pq_update_postal_code_cookie_at_checkout( $post_data ) {
  parse_str( $post_data, $checkout_data );
  if ( pq_is_checkout_in_mtl($checkout_data) === true) {
    setcookie( 'pq_delivery_zone', 'MTL', time() + (86400 * 30), '/' );
  } else {
    setcookie( 'pq_delivery_zone', 'QC', time() + (86400 * 30), '/' );
    add_filter( 'wc_od_checkout_delivery_details_args', 'pq_delivery_outside_mtl_args', 10, 1);
  }
}

/**
 * Check if delivery in MTL from the checkout post_data
 */
function pq_is_checkout_in_mtl($checkout_data) {
  if ( ! empty( $checkout_data['billing_postcode'] ) ) {
    if ( is_postcode_in_mtl($checkout_data['billing_postcode']) === true ) {
      return true;
    } else {
      return false;
    }
  }
}

/**
 * Overwrite the delivery dates plugin args for the form-delivery-date checkout template to display delivery estimate instead of delivery date datepicker
 */
function pq_delivery_outside_mtl_args($args) {
  $args['delivery_option'] = 'shipping_date';
  $args['delivery_range']['min'] = 4;
  $args['delivery_range']['max'] = 7;
  return $args;
}

/**
 * Delivery date is not mandatory outside of MTL
 */
add_action( 'woocommerce_after_checkout_validation', 'pq_delivery_date_not_mandatory', 10, 2 );

function pq_delivery_date_not_mandatory($checkout_data, $errors) {
  if ( pq_is_checkout_in_mtl($checkout_data) !== true) {
    $errors->remove('delivery_date_required');
    $errors->remove('delivery_time_frame_required');
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

  if ( $matched_zone_id === true ) {
    setcookie( 'pq_delivery_zone', 'MTL', time() + (86400 * 30), '/' );
    echo 'MTL';
  } else {
    setcookie( 'pq_delivery_zone', 'QC', time() + (86400 * 30), '/' );
    echo 'QC';
  }
  wp_die();
}


/**
 * Check if postal code is in a delivery zone in Montréal
 */
function is_postcode_in_mtl($postal_code) {
  $shipping_zones = WC_Shipping_Zones::get_zones();

  $is_postcode_in_mtl = false;
  foreach ($shipping_zones as $zone) {
    $zone_locations = $zone['zone_locations'];
    foreach ($zone_locations as $location) {
      if ($location->type === 'postcode' && is_postcode_match( $postal_code, $location->code )) {
        $is_postcode_in_mtl = true;
      }
    }
  }

  return $is_postcode_in_mtl;
}


/**
 * Check if postal code matches a zone postal code
 */
function is_postcode_match($user_postcode, $zone_postcode) {
  $user_postcode = strtoupper( str_replace(' ', '', $user_postcode) );
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