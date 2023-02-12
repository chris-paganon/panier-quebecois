<?php
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Add a cookie to store the delivery zone
 */
// add_action( 'init', 'pq_set_delivery_zone_cookie' );

// function pq_set_delivery_zone_cookie() {
//   if ( !isset( $_COOKIE['pq_delivery_zone'] ) ) {
//     setcookie( 'pq_delivery_zone', '0', time() + 3600, COOKIEPATH, COOKIE_DOMAIN );
//   }
// }


/**
 * Add a popup to select the delivery zone
 */
add_action( 'init', 'pq_delivery_zone_popup' );

function pq_delivery_zone_popup() {
  if ( !isset( $_COOKIE['pq_delivery_zone'] ) || $_COOKIE['pq_delivery_zone'] == '0' ) {
    $args = array();
    wc_pq_get_template( 'popup/delivery-zone-select.php', $args );
  }
}