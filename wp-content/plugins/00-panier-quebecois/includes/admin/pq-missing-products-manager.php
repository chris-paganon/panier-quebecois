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


/**
 * Handle short_name meta in wc_get_products
 */
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'pq_handle_short_name_query_var', 10, 2 );

function pq_handle_short_name_query_var( $query, $query_vars ) {
	if ( ! empty( $query_vars['pq_short_name'] ) ) {
		$query['meta_query'][] = array(
			'key' => '_short_name',
			'value' => esc_attr( $query_vars['pq_short_name'] ),
      'compare' => 'LIKE',
		);
	}

	return $query;
}


/**
 * Get products short name list with AJAX 
 */
add_action( 'wp_ajax_pq_get_products_short_names', 'pq_get_products_short_names_with_ajax' );
add_action( 'wp_ajax_nopriv_pq_get_products_short_names', 'pq_get_products_short_names_with_ajax' );

function pq_get_products_short_names_with_ajax() {
	$short_name_input = sanitize_text_field( $_POST['short_name_input'] );
  $products_query_arg = array(
    'limit' => 10,
    'status' => 'publish',
		'pq_short_name' => $short_name_input,
		'post_type' => 'product',
  );

  $products = wc_get_products( $products_query_arg );
 
  foreach ( $products as $product ) {
    $product_id = $product->get_id();
    $short_name = get_post_meta($product_id, '_short_name', true);
    $product_html = '<li class="pq-product-search-result" pq-data="' . $product_id . '">' . $short_name . '</li>';
    echo $product_html;
  }

  wp_die();
}