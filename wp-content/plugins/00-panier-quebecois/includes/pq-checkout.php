<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

// --------------------------- CHECKOUT --------------------------- //

// --------- Move shipping method on checkout page ------------ //

//AJAX fragments
add_filter( 'woocommerce_update_order_review_fragments', 'my_custom_shipping_table_update' );

function my_custom_shipping_table_update( $fragments ) {
  ob_start(); ?>
  <table class="my-custom-shipping-table">
    <tbody>
      <?php wc_cart_totals_shipping_html(); ?>
    </tbody>
  </table><?php
  $woocommerce_shipping_methods = ob_get_clean();

  $fragments[ '.my-custom-shipping-table' ] = $woocommerce_shipping_methods;

  return $fragments;
}

//Custom text above shipping method table
add_action( 'woocommerce_review_order_before_shipping', 'myfct_custom_delivery_method_msg' );

function myfct_custom_delivery_method_msg() {
  if ( !myfct_return_true_if_has_category( 'entreprise' ) ) {
    echo '<h5 class="custom_orddd_delivery_method_msg"t>Entrez votre adresse complète pour voir toutes les méthodes de livraison disponibles:</h5>';
  }
}

//-------- Remove product added to cart notice -------- //
add_filter( 'wc_add_to_cart_message_html', 'myfct_remove_add_to_cart_message' );

function myfct_remove_add_to_cart_message() {
  return;
}

// -------- Move login and coupon codes on the checkout page ---------- //

//Add tips to checkout
add_action( 'woocommerce_review_order_before_payment', 'pq_tip_shortcode_fonction' );

function pq_tip_shortcode_fonction() {
  do_shortcode( '[order_tip_form]' );
}

/**
 * Replace tipping rates at checkout
 */
add_filter( 'wc_order_tip_rates', 'pq_wc_custom_order_tip_rates', 10, 1 );

function pq_wc_custom_order_tip_rates( $rates ) {
  $rates = array(
    10,
    12,
    15,
  );

  return $rates;
}

//Make custom tip string label translatable
add_filter( 'wc_order_tip_custom_label', 'pq_make_custom_tip_label_translatable' );

function pq_make_custom_tip_label_translatable( $tip_label ) {
  return esc_html__( $tip_label );
}

// ------ Login redirects to checkout if login from checkout ------ //
add_filter( 'woocommerce_login_redirect', 'myfct_redirect_checkout_login', 10, 1 );

function myfct_redirect_checkout_login( $redirect ) {
  $checkout_page_url = wc_get_checkout_url();

  if ( is_checkout() ) {
    return $checkout_page_url;
  } else {
    return $redirect;
  }
}

// ------ Minimum order amount ------ //
add_action( 'woocommerce_checkout_process', 'wc_minimum_order_amount' );
add_action( 'woocommerce_before_cart', 'wc_minimum_order_amount' );

function wc_minimum_order_amount() {
  // Set this variable to specify a minimum order value
  $minimum = 30;

  if ( WC()->cart->subtotal < $minimum ) {

    $exception_product_id = 26211;
    $cart_has_exception = false;

    foreach ( WC()->cart->get_cart() as $cart_item ) {
      $product_in_cart = $cart_item[ 'product_id' ];
      if ( $product_in_cart === $exception_product_id )$cart_has_exception = true;
    }

    if ( !$cart_has_exception ) {
      if ( is_cart() ) {

        wc_print_notice(
          sprintf(
            'Votre commande doit avoir une valeur minimum de %s',
            wc_price( $minimum )
          ),
          'error'
        );
      } else {

        wc_add_notice(
          sprintf(
            'Votre commande doit avoir une valeur minimum de %s',
            wc_price( $minimum )
          ),
          'error'
        );
      }
    }
  }
}


// --------------------------- CART --------------------------- //

// ------- Add notification text above cart ------- //
add_action( 'woocommerce_before_cart', 'myfct_cart_notification' );

function myfct_cart_notification() {
  ?>
<div id='cart-notification-wrapper'><span id='cart-notification'>
  <h4> Livraison gratuite à partir de $100 </h4>
  </span></div>
<?php
}

// -------- Change continue shopping button to link to products page -------- //
add_filter( 'woocommerce_continue_shopping_redirect', 'bbloomer_change_continue_shopping' );
add_filter( 'woocommerce_return_to_shop_redirect', 'bbloomer_change_continue_shopping' );

function bbloomer_change_continue_shopping() {
  return get_permalink( 6720 );
}

// -------- Add empty CArt button -------- //
add_action( 'woocommerce_cart_coupon', 'custom_woocommerce_empty_cart_button' );
function custom_woocommerce_empty_cart_button() {
	echo '<a id="btn_empty_cart" data-msg-fr="' .  esc_attr('Etes vous sur de vouloir vider votre panier ?', 'panierquebecois' ) . '" data-msg-en="' .  esc_attr('Are you sure you want to empty the cart?', 'panierquebecois' ) . '" href="' . esc_url( add_query_arg( 'empty_cart', 'yes' ) ) . '" class="button" title="' . esc_attr(__( 'Vider le panier', 'panierquebecois' )) . '">' . esc_html(__( 'Vider le panier', 'panierquebecois' )) . '</a>';
}

add_action( 'wp_loaded', 'custom_woocommerce_empty_cart_action', 20 );
function custom_woocommerce_empty_cart_action() {
	if ( isset( $_GET['empty_cart'] ) && 'yes' === esc_html( $_GET['empty_cart'] ) ) {
		WC()->cart->empty_cart();

		$referer  = wp_get_referer() ? esc_url( remove_query_arg( 'empty_cart' ) ) : wc_get_cart_url();
		wp_safe_redirect( $referer );
	}
}

// --------------------------- THANK YOU --------------------------- //

/* ------ Add Net Promoter Score on thank you page ------ */
add_action( 'woocommerce_before_thankyou', 'pq_add_nps_to_thankyou_page', 20 );

function pq_add_nps_to_thankyou_page( $order_id ) {
  $user_id = get_current_user_id();

  if ( !empty( $user_id ) ) { ?>
    <div id="pq-thankyou-nps">
      <h2 id="my-thankyou-intro"><?php echo esc_html__('Donnez-nous votre avis!') ?></h2>
      <?php echo do_shortcode('[formidable id=2]'); ?>
    </div><?php
  }

  //Introduce rest of the thank you page
  echo '<h2 id="my-thankyou-intro">' . esc_html__( 'Votre commande:' ) . '</h2>';
}
