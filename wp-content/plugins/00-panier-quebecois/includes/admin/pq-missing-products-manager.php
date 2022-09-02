<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Add shortcode to display missing products manager
 */
add_shortcode( 'pq_missing_products_manager', 'pq_missing_products_manager_fct' );

function pq_missing_products_manager_fct() {
  ob_start();
  wc_pq_get_template( 'admin/pq-missing-products-manager-content.php', '' );
  return ob_get_clean();
}