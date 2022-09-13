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
    foreach ( $order->get_items() as $item_id => $item ) {
      $product_id = $item->get_product_id();
      if ( $product_id == $missing_product_id ) {
        $order_is_concerned = true;
        $missing_item_id = $item_id;
      } else {
        $variation_id = $item->get_variation_id();
        if ( !empty($variation_id) && $variation_id == $missing_product_id ) {
          $order_is_concerned = true;
          $missing_item_id = $item_id;
        }
      }
    }

    $existing_order_refunds = $order->get_refunds();
    foreach ( $existing_order_refunds as $existing_order_refund ) {
      if( $existing_order_refund->get_reason() == 'missing_product_' . $missing_product_id ) {
        $order_is_concerned = false;
      }
    }

    if ( $order_is_concerned ) {
      $order_to_replace = array( array(
        'order_id' => $order_id,
        'item_id' => $missing_item_id,
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
 * Get price difference between 2 products
 */
function pq_get_price_difference_for_refund( $missing_product, $replacement_product ) {
  $missing_product_price = $missing_product->get_price();
  $replacement_product_price = $replacement_product->get_price();

  $refund_amount = max( $missing_product_price - $replacement_product_price, 0 );

  return $refund_amount;
}


/**
 * Get refund amount
 */
function pq_get_refund_amount( $missing_products_form_data, $missing_product, $replacement_product ) {
  $manual_refund_amount = pq_get_js_form_field_value( $missing_products_form_data, 'manual-refund-amount' );
  if ( ! empty($manual_refund_amount) ) {
    $refund_amount = $manual_refund_amount;
  } else {
    $refund_amount = pq_get_price_difference_for_refund( $missing_product, $replacement_product );
  }

  return $refund_amount;
}


/**
 * Check if refund is needed
 */
function pq_is_refund_needed( $missing_products_form_data, $refund_amount ) {
  $is_refund_needed = pq_get_js_form_field_value( $missing_products_form_data, 'is-refund-needed' ); //Returns false if empty
  
  if ( ! $refund_amount > 0 && $is_refund_needed ) {
    $is_refund_needed = false;
  }

  return $is_refund_needed;
}


/**
 * Review email and number of customers before sending 
 */
add_action( 'wp_ajax_pq_review_missing_product', 'pq_review_missing_product_with_ajax' );

function pq_review_missing_product_with_ajax() {

  $missing_products_form_data = $_POST['missing_products_form_data'];
  $missing_product_type = pq_get_js_form_field_value( $missing_products_form_data, 'missing-product-type' );

  switch ( $missing_product_type ) {
    case 'replacement':
      pq_review_replacement_product( $missing_products_form_data );
      break;
    case 'organic-replacement':
      pq_review_replacement_organic_product( $missing_products_form_data );
      break;
    case 'refund':
      pq_review_refunded_product( $missing_products_form_data );
      break;
  }

  wp_die();
}


/**
 * Review content for product replacement
 */
function pq_review_replacement_product( $missing_products_form_data ) {
  $missing_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-missing-product' );
  $missing_product = wc_get_product( $missing_product_id );
  $missing_product_name = $missing_product->get_name();
  
  $replacement_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-replacement-product' );
  $replacement_product = wc_get_product( $replacement_product_id );
  $replacement_product_name = $replacement_product->get_name();

  $refund_amount = pq_get_refund_amount( $missing_products_form_data, $missing_product, $replacement_product );
  $is_refund_needed = pq_is_refund_needed( $missing_products_form_data, $refund_amount );

  $args = array( 
    'missing_product_name' => $missing_product_name,
    'replacement_product_name' => $replacement_product_name,
    'billing_first_name' => 'Arthuro',
    'billing_language' => 'francais',
    'is_refund_needed' => $is_refund_needed,
    'refund_amount' => $refund_amount,
  );
  ob_start();
  wc_pq_get_template( 'email/pq-replace-product-email.php', $args );
  $email_content = ob_get_clean();

  $orders_to_replace = pq_get_missing_product_orders ( $missing_product_id );

  echo "<h3>Nombre de clients concernés: " . count($orders_to_replace) . "</h3>";
  echo "<h3>Remboursement: " . wc_price($refund_amount) . "</h3>";

  echo "<h3>Contenu de l'email:</h3>";
  echo $email_content;
}


/**
 * Review content for organic product replacement
 */
function pq_review_replacement_organic_product( $missing_products_form_data ) {
  $missing_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-missing-product' );
  $missing_product = wc_get_product( $missing_product_id );
  $missing_product_name = $missing_product->get_name();

  $refund_amount = pq_get_js_form_field_value( $missing_products_form_data, 'manual-refund-amount' );
  $is_refund_needed = pq_is_refund_needed( $missing_products_form_data, $refund_amount );

  $args = array( 
    'missing_product_name' => $missing_product_name,
    'billing_first_name' => 'Arthuro',
    'billing_language' => 'francais',
    'is_refund_needed' => $is_refund_needed,
    'refund_amount' => $refund_amount,
  );
  ob_start();
  wc_pq_get_template( 'email/pq-replace-organic-product-email.php', $args );
  $email_content = ob_get_clean();

  $orders_to_replace = pq_get_missing_product_orders ( $missing_product_id );

  echo "<h3>Nombre de clients concernés: " . count($orders_to_replace) . "</h3>";
  echo "<h3>Remboursement: " . wc_price($refund_amount) . "</h3>";

  echo "<h3>Contenu de l'email:</h3>";
  echo $email_content;
}


/**
 * Review content for product refund
 */
function pq_review_refunded_product( $missing_products_form_data ) {
  $missing_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-missing-product' );
  $missing_product = wc_get_product( $missing_product_id );
  $missing_product_name = $missing_product->get_name();

  $refund_amount = $missing_product->get_price();

  $args = array( 
    'missing_product_name' => $missing_product_name,
    'billing_first_name' => 'Arthuro',
    'billing_language' => 'francais',
  );
  ob_start();
  wc_pq_get_template( 'email/pq-refund-product-email.php', $args );
  $email_content = ob_get_clean();

  $orders_to_replace = pq_get_missing_product_orders ( $missing_product_id );

  echo "<h3>Nombre de clients concernés: " . count($orders_to_replace) . "</h3>";
  echo "<h3>Remboursement: " . wc_price($refund_amount) . "</h3>";

  echo "<h3>Contenu de l'email:</h3>";
  echo $email_content;
}


/**
 * Send missing products emails to customers
 */
add_action( 'wp_ajax_pq_send_missing_product', 'pq_send_missing_product_with_ajax' );

function pq_send_missing_product_with_ajax() {

  $missing_products_form_data = $_POST['missing_products_form_data'];
  $missing_product_type = pq_get_js_form_field_value( $missing_products_form_data, 'missing-product-type' );

  switch ( $missing_product_type ) {
    case 'replacement':
      pq_send_replacement_product( $missing_products_form_data );
      break;
    case 'organic-replacement':
      pq_send_replacement_organic_product( $missing_products_form_data );
      break;
    case 'refund':
      pq_send_refunded_product( $missing_products_form_data );
      break;
  }

  wp_die();
}


/**
 * Send email and refund for product replacement
 */
function pq_send_replacement_product( $missing_products_form_data ) {
  $missing_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-missing-product' );
  $missing_product = wc_get_product( $missing_product_id );
  $missing_product_name = $missing_product->get_name();
  
  $replacement_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-replacement-product' );
  $replacement_product = wc_get_product( $replacement_product_id );
  $replacement_product_name = $replacement_product->get_name();

  $refund_amount = pq_get_refund_amount( $missing_products_form_data, $missing_product, $replacement_product );
  $is_refund_needed = pq_is_refund_needed( $missing_products_form_data, $refund_amount );

  $orders_to_replace = pq_get_missing_product_orders ( $missing_product_id );

  foreach ( $orders_to_replace as $order_to_replace ) {
    $args = array( 
      'missing_product_name' => $missing_product_name,
      'replacement_product_name' => $replacement_product_name,
      'billing_first_name' => $order_to_replace['billing_first_name'],
      'billing_language' => $order_to_replace['billing_language'],
      'is_refund_needed' => $is_refund_needed,
      'refund_amount' => $refund_amount,
    );
    ob_start();
    wc_pq_get_template( 'email/pq-replace-product-email.php', $args );
    $email_content = ob_get_clean();

    $headers = array(
      'Content-Type: text/html; charset=UTF-8', 
      'Reply-To: Panier Québécois <commandes@panierquebecois.ca>',
    );

    wp_mail( $order_to_replace['billing_email'], 'Produit remplacé', $email_content, $headers);

    if ( $is_refund_needed ) {
      $item_id = $order_to_replace['item_id'];
      $order_id = $order_to_replace['order_id'];
      $order = wc_get_order( $order_id );
      $item_quantity = pq_get_item_qty_after_refunds( $order, $item_id );

      $refund_amount = $refund_amount * $item_quantity;

      $line_items = array();
      $line_items[$item_id] = array(
        'refund_total' => $refund_amount,
      );

      wc_create_refund( array(
        'amount' => $refund_amount,
        'reason' => 'missing_product_' . $missing_product_id,
        'order_id' => $order_id,
        'line_items' => $line_items,
        'refund_payment' => false, //Switch to true for production
      ));
    }
  }

  echo '<h3>' . count($orders_to_replace) . ' commande(s) traitée(s)</h3>';
}


/**
 * Send email and refund for organic product replacement
 */
function pq_send_replacement_organic_product( $missing_products_form_data ) {
  $missing_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-missing-product' );
  $missing_product = wc_get_product( $missing_product_id );
  $missing_product_name = $missing_product->get_name();  
  
  $refund_amount = pq_get_js_form_field_value( $missing_products_form_data, 'manual-refund-amount' );
  $is_refund_needed = pq_is_refund_needed( $missing_products_form_data, $refund_amount );

  $orders_to_replace = pq_get_missing_product_orders ( $missing_product_id );

  foreach ( $orders_to_replace as $order_to_replace ) {
    $args = array( 
      'missing_product_name' => $missing_product_name,
      'billing_first_name' => $order_to_replace['billing_first_name'],
      'billing_language' => $order_to_replace['billing_language'],
      'is_refund_needed' => $is_refund_needed,
      'refund_amount' => $refund_amount,
    );
    ob_start();
    wc_pq_get_template( 'email/pq-replace-organic-product-email.php', $args );
    $email_content = ob_get_clean();

    $headers = array(
      'Content-Type: text/html; charset=UTF-8', 
      'Reply-To: Panier Québécois <commandes@panierquebecois.ca>',
    );

    wp_mail( $order_to_replace['billing_email'], 'Produit remplacé', $email_content, $headers);

    if ( $is_refund_needed ) {

      $order_id = $order_to_replace['order_id'];
      $order = wc_get_order( $order_id );
      $item_id = $order_to_replace['item_id'];
  
      //Get item quantity to refund
      $item_quantity = pq_get_item_qty_after_refunds( $order, $item_id );
      $refund_amount = $refund_amount * $item_quantity;

      $line_items = array();
      $line_items[$order_to_replace['item_id']] = array(
        'refund_total' => $refund_amount,
      );
  
      wc_create_refund( array(
        'amount' => $refund_amount,
        'reason' => 'missing_product_' . $missing_product_id,
        'order_id' => $order_id,
        'line_items' => $line_items,
        'refund_payment' => false, //Switch to true for production
      ));
    }
  }

  echo '<h3>' . count($orders_to_replace) . ' commande(s) traitée(s)</h3>';
}

/**
 * Send email and refund for product refund
 */
function pq_send_refunded_product( $missing_products_form_data ) {
  $missing_product_id = pq_get_js_form_field_value( $missing_products_form_data, 'selected-missing-product' );
  $missing_product = wc_get_product( $missing_product_id );
  $missing_product_name = $missing_product->get_name();

  $orders_to_replace = pq_get_missing_product_orders ( $missing_product_id );

  foreach ( $orders_to_replace as $order_to_replace ) {
    $args = array( 
      'missing_product_name' => $missing_product_name,
      'billing_first_name' => $order_to_replace['billing_first_name'],
      'billing_language' => $order_to_replace['billing_language'],
    );
    ob_start();
    wc_pq_get_template( 'email/pq-refund-product-email.php', $args );
    $email_content = ob_get_clean();

    $headers = array(
      'Content-Type: text/html; charset=UTF-8', 
      'Reply-To: Panier Québécois <commandes@panierquebecois.ca>',
    );

    wp_mail( $order_to_replace['billing_email'], 'Produit remplacé', $email_content, $headers);

    $order_id = $order_to_replace['order_id'];
    $order = wc_get_order( $order_id );
    $item_id = $order_to_replace['item_id'];
    $item = $order->get_item($item_id);

    //Get item quantity to refund
    $item_quantity = pq_get_item_qty_after_refunds( $order, $item_id );

    //Get items total to refund (before refunds already made)
    $item_total = $item->get_total();
    $item_tax_total = $item->get_total_tax();

    //Get taxes already refunded and what is left to refund per tax id
    $item_taxes = $item->get_taxes();
    $item_tax_total_already_refunded = 0;
    $item_taxes_to_refund = array();
    foreach ( $item_taxes['total'] as $tax_id => $tax_total ) {
      $tax_amount_already_refunded = $order->get_tax_refunded_for_item( $item_id, $tax_id );
      $item_tax_total_already_refunded += $tax_amount_already_refunded;
      $item_taxes_to_refund[$tax_id] = $tax_total - $tax_amount_already_refunded;
    }

    //Calculate final refund amounts
    $item_total_already_refunded = $order->get_total_refunded_for_item( $item_id );
    $item_total_refund_amount = $item_total - $item_total_already_refunded;
    $refund_amount = $item_total_refund_amount + $item_tax_total - $item_tax_total_already_refunded;

    $line_items = array();
    $line_items[$order_to_replace['item_id']] = array(
      'qty' => $item_quantity,
      'refund_total' => $item_total_refund_amount,
      'refund_tax' => $item_taxes_to_refund,
    );

    wc_create_refund( array(
      'amount' => $refund_amount,
      'reason' => 'missing_product_' . $missing_product_id,
      'order_id' => $order_id,
      'line_items' => $line_items,
      'refund_payment' => false, //Switch to true for production
    ));
  }

  echo '<h3>' . count($orders_to_replace) . ' commande(s) traitée(s)</h3>';
}