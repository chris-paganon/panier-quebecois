<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/* ------ Add wp-rocket cache bypass for backend users on login ------ */
add_action( 'wp_login', 'pq_set_wp_rocket_cache_bypass_cookie', 10, 2 );

function pq_set_wp_rocket_cache_bypass_cookie( $user_login, $user ) {
  $user_roles = ( array )$user->roles;

  if ( !( in_array( 'customer', $user_roles ) || in_array( 'subscriber', $user_roles ) ) ) {
    setcookie( 'pq-wp-rocket-bypass', 'bypass', time() + 2 * 24 * 60 * 60, COOKIEPATH, COOKIE_DOMAIN );
  }
}

/* ------ Reset wp-rocket cache bypass for backend users on admin pages ------ */
add_action( 'admin_init', 'pq_reset_wp_rocket_cache_bypass_cookie' );

function pq_reset_wp_rocket_cache_bypass_cookie() {
  $user = wp_get_current_user();
  $user_roles = ( array )$user->roles;

  if ( !( in_array( 'customer', $user_roles ) || in_array( 'subscriber', $user_roles ) ) ) {
    setcookie( 'pq-wp-rocket-bypass', 'bypass', time() + 2 * 24 * 60 * 60, COOKIEPATH, COOKIE_DOMAIN );
  }
}

/* ------ Remove wp-rocket cache bypass for backend users on logout ------ */
add_action( 'wp_logout', 'pq_unset_wp_rocket_cache_bypass_cookie', 10, 1 );

function pq_unset_wp_rocket_cache_bypass_cookie( $user_id ) {
  $user = get_user_by( 'id', $user_id );
  $user_roles = ( array )$user->roles;

  if ( !( in_array( 'customer', $user_roles ) || in_array( 'subscriber', $user_roles ) ) ) {
    setcookie( 'pq-wp-rocket-bypass', 'bypass', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
  }
}