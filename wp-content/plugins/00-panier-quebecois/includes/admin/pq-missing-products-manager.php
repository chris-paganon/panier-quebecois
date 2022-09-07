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

function pq_get_products_short_names_with_ajax() {
	$short_name_input = sanitize_text_field( $_POST['short_name_input'] );
  $products_query_arg = array(
		'posts_per_page' => 10,
		'meta_query'    => array( array(
			'key'     => '_short_name',
			'value'   => $short_name_input,
      'compare' => 'LIKE',
		)),
		'post_type' => array( 'product', 'product_variation' ),
	);
  $products_query = new WP_Query( $products_query_arg );
 
  foreach ( $products_query->posts as $product_post ) {
    $product_id = $product_post->ID;
    $short_name = get_post_meta($product_id, '_short_name', true);
    $product_html = '<li class="pq-product-search-result" pq-data="' . $product_id . '">' . $short_name . '</li>';
    echo $product_html;
  }

  wp_die();
}

/**
 * Get products short name list with AJAX 
 */
add_action( 'wp_ajax_pq_review_missing_product', 'pq_review_missing_product_with_ajax' );

function pq_review_missing_product_with_ajax() {
  echo print_r($_POST['missing_products_form_data'], true);

  wp_die();
}