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
    $product_html = '<li class="pq-product-search-result" pq-data="' . esc_attr($product_id) . '">' . esc_html( $short_name ) . '</li>';
    echo $product_html;
  }

  wp_die();
}

/**
 * Get products short name list with AJAX 
 */
add_action( 'wp_ajax_pq_review_missing_product', 'pq_review_missing_product_with_ajax' );

function pq_review_missing_product_with_ajax() {

  $missing_products_form_data = $_POST['missing_products_form_data'];

  foreach ( $missing_products_form_data as $missing_products_form_field ) {
    $missing_products_form_field_name = $missing_products_form_field['name'];
    $missing_products_form_field_value = $missing_products_form_field['value'];

    if ( $missing_products_form_field_name == 'selected-missing-product' ) {
      $missing_product_id = $missing_products_form_field_value;
    } elseif ( $missing_products_form_field_name == 'selected-replacement-product' ) {
      $replacement_product_id = $missing_products_form_field_value;
    }
  }

  $missing_product = wc_get_product( $missing_product_id );
  $missing_product_name = $missing_product->get_name();
  $replacement_product = wc_get_product( $replacement_product_id );
  $replacement_product_name = $replacement_product->get_name();

  $email_content = '<p>Bonjour,</p> 
  <p>En achetant les produits ce matin, notre marchand nous a informé ne plus avoir de ' . $missing_product_name . ' en stock. Nous avons donc décidé de le remplacer par ' . $replacement_product_name . '. Si le produit de remplacement ne vous convient pas, laissez-le nous savoir et nous nous ferons un plaisir de vous rembourser (même si vous avez déjà reçus votre commande). Nous faisons toujours notre possible pour vous fournir les meilleurs produits du marché en fonction des stocks disponibles. Nous nous excusons pour ce changement de dernière minute, et vous remercions pour votre confiance dans notre service!</p> 
  <p>Bonne journée,</p>';

  echo $email_content;

  $orders_today = pq_get_relevant_orders_today();
  $orders_to_replace = array();

  foreach ( $orders_today as $order ) {
    $order_id = $order->get_id();
    $order_is_concerned = false;
    foreach ( $order->get_items() as $item ) {
      $product_id = $item->get_product_id();
      if ( $product_id == $missing_product_id ) {
        $order_is_concerned = true;
      } else {
        $variation_id = $item->get_variation_id();
        if ( !empty($variation_id) && $variation_id == $missing_product_id ) {
          $order_is_concerned = true;
        }
      }
    }

    if ( $order_is_concerned ) {
      $order_to_replace = array( array(
        'billing_email' => $order->get_billing_email(),
        'billing_first_name' => $order->get_billing_first_name(),
        'billing_language' => get_post_meta( $order_id, '_billing_language', true ),
      ));

      $orders_to_replace = array_merge( $orders_to_replace, $order_to_replace );
    }
  }

  echo '</br>';
  echo count($orders_to_replace);

  wp_die();
}