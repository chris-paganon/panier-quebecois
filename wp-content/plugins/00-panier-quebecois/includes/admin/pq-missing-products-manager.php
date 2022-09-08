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
 * Get missing product names from form data
 */
function pq_get_js_form_field_value( $form_data, $field_name_to_retrieve ) {

  foreach ( $form_data as $form_field ) {
    if ( $form_field['name'] == $field_name_to_retrieve ) {
      return $form_field['value'];
    }
  }

  //If no form field is found, return false
  return false;
}


/**
 * Get array of orders concerned by the missing product
 */
function pq_get_missing_product_orders ( $missing_product_id ) {

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
        'billing_first_name' => ucfirst(strtolower( $order->get_billing_first_name() )),
        'billing_language' => get_post_meta( $order_id, '_billing_language', true ),
      ));

      $orders_to_replace = array_merge( $orders_to_replace, $order_to_replace );
    }
  }

  return $orders_to_replace;
}


/**
 * Review email and number of customers before sending 
 */
add_action( 'wp_ajax_pq_review_missing_product', 'pq_review_missing_product_with_ajax' );

function pq_review_missing_product_with_ajax() {

  $missing_products_form_data = $_POST['missing_products_form_data'];

  $missing_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-missing-product' );
  $missing_product = wc_get_product( $missing_product_id );
  $missing_product_name = $missing_product->get_name();
  
  $replacement_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-replacement-product' );
  $replacement_product = wc_get_product( $replacement_product_id );
  $replacement_product_name = $replacement_product->get_name();

  $args = array( 
    'missing_product_name' => $missing_product_name,
    'replacement_product_name' => $replacement_product_name,
    'billing_first_name' => 'Arthuro',
    'billing_language' => 'francais',
  );
  ob_start();
  wc_pq_get_template( 'email/pq-replace-product-email.php', $args );
  $email_content = ob_get_clean();

  echo "<h3>Contenu de l'email:</h3>";
  echo $email_content;

  $orders_to_replace = pq_get_missing_product_orders ( $missing_product_id );

  echo "<h3>Nombre de clients concernés: " . count($orders_to_replace) . "</h3>";

  wp_die();
}


/**
 * Send missing products emails to customers
 */
add_action( 'wp_ajax_pq_send_missing_product', 'pq_send_missing_product_with_ajax' );

function pq_send_missing_product_with_ajax() {

  $missing_products_form_data = $_POST['missing_products_form_data'];

  $missing_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-missing-product' );
  $missing_product = wc_get_product( $missing_product_id );
  $missing_product_name = $missing_product->get_name();
  
  $replacement_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-replacement-product' );
  $replacement_product = wc_get_product( $replacement_product_id );
  $replacement_product_name = $replacement_product->get_name();

  $orders_to_replace = pq_get_missing_product_orders ( $missing_product_id );

  foreach ( $orders_to_replace as $order_to_replace ) {
    $args = array( 
      'missing_product_name' => $missing_product_name,
      'replacement_product_name' => $replacement_product_name,
      'billing_first_name' => $order_to_replace['billing_first_name'],
      'billing_language' => $order_to_replace['billing_language'],
    );
    ob_start();
    wc_pq_get_template( 'email/pq-replace-product-email.php', $args );
    $email_content = ob_get_clean();

    $headers = array(
      'Content-Type: text/html; charset=UTF-8', 
      'Reply-To: Panier Québécois <commandes@panierquebecois.ca>',
    );

    wp_mail( $order_to_replace['billing_email'], 'Produit remplacé', $email_content, $headers);
  }

  echo '<h3>' . count($orders_to_replace) . ' emails envoyés</h3>';

  wp_die();
}