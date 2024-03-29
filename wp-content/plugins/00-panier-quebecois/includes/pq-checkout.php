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
    echo '<h3 class="pq-delivery-selection-title">Date de livraison</h3>';
    echo '<p class="custom_orddd_delivery_method_msg"t>Entrez votre adresse complète pour voir toutes les méthodes de livraison disponibles:</p>';
  }
}

// ------- Select first delivery time frame by default ------- //
add_filter( 'wc_od_get_time_frames_choices', 'pq_default_delivery_time_frame', 10, 3 );

function pq_default_delivery_time_frame($choices, $time_frames, $context) {

  foreach ( $choices as $key => $choice ) {
    if ( strpos($choice, 'Choose a time frame') !== false ) {
      unset($choices[$key]);
    }
  }

  return $choices;
}


// ------ ------- //
add_action( 'wc_od_checkout_after_delivery_details', 'pq_timeslot_info', 10, 1);

function pq_timeslot_info($args) {

  $checkout_fields = $args['checkout']->checkout_fields;
  $delivery_time_slots = $checkout_fields['delivery']['delivery_time_frame']['options'];
  $first_delivery_time_slot = reset($delivery_time_slots);

  ?>

  <div class="pq-timeslot-info-wrapper">
    <p class="pq-timeslot-info"><span class="pq-timeslot-info-text"><?php echo esc_html__('Livraison éco-responsable entre', 'panier-quebecois'). ' ' . $first_delivery_time_slot; ?> <span class="dashicons dashicons-editor-help"></span><span></p>
    <div class="pq-timeslot-additional-info"><?php echo esc_html__('Sélectionnez le créneau horaire entre', 'panier-quebecois') . ' ' . $first_delivery_time_slot . ' ' . esc_html__('afin de nous aider à optimiser nos trajets! Vous recevrez un courriel lorsque votre livreur commence sa route avec un lien pour le suivre en temps réel. Vous verrez également le nombre de commandes à livrer avant vous.', 'panier-quebecois'); ?> </div>
  </div>

  <?php
}


// ------- Move delivery date selection ------- //
add_filter( 'wc_od_checkout_location', 'pq_move_delivery_date_selection', 10, 2);

function pq_move_delivery_date_selection($location, $key ) {

  $location = array(
    'hook'     => 'woocommerce_after_checkout_billing_form',
    'priority' => 10,
  );

  return $location;
}

/**
 * Pickup lead time shown at checkout fix
 */
add_filter('wc_local_pickup_plus_get_package_pickup_appointment_field_html', 'pq_fix_pickup_lead_time', 10, 3);

function pq_fix_pickup_lead_time($field_html, $package_id, $package) {

  $chosen_date = $package['pickup_date'];
  $pickup_date_obj = new DateTime( $chosen_date );
  $day = $pickup_date_obj->format('w');

  $chosen_location = wc_local_pickup_plus_get_pickup_location( $package['pickup_location_id'] );

  if (!empty($chosen_location)) {
    $schedule = $chosen_location->get_business_hours()->get_value();
    $opening_hours = (array) $schedule[ (int) $day ];
  
    $start_time_seconds = reset(array_keys($opening_hours));
    $start_time = pq_convert_seconds_to_time( $start_time_seconds );
  
    //Get the wrong time from the plugin
    $chosen_datetime = ! empty( $chosen_date ) && is_string( $chosen_date ) ? new \DateTime( $chosen_date, $chosen_location->get_address()->get_timezone() ) : null;
    $minimum_hours = ! empty( $chosen_datetime ) ? $chosen_location->get_appointments()->get_schedule_minimum_hours( $chosen_datetime ) : null;
    $minimum_hours_time = pq_convert_seconds_to_time( $minimum_hours );
  
    if ( $minimum_hours_time != $start_time ) {
      $field_html = str_replace($minimum_hours_time, $start_time, $field_html);
    }
  }

  return $field_html;
}


/**
 * Add order meta with delivery date for pickups
 */
add_action( 'woocommerce_checkout_update_order_meta', 'pq_add_pickup_date_meta', 10, 2 );

function pq_add_pickup_date_meta( $order_id, $data ) {
  if ( in_array('local_pickup_plus', $data['shipping_method']) ) {
    $pickup_date = reset($_POST['_shipping_method_pickup_date']);
    $location_id = reset($_POST['_shipping_method_pickup_location_id']);

    update_post_meta($order_id, '_shipping_date', $pickup_date);

    $pickup_date_obj = new DateTime( $pickup_date );
    $day = $pickup_date_obj->format('w');

    $chosen_location = wc_local_pickup_plus_get_pickup_location( $location_id );
    $schedule = $chosen_location->get_business_hours()->get_value();
    $opening_hours = (array) $schedule[ (int) $day ];

    $start_time_seconds = reset(array_keys($opening_hours));
    $start_time = pq_convert_seconds_to_time( $start_time_seconds );

    $end_time_seconds = reset($opening_hours);
    $end_time = pq_convert_seconds_to_time( $end_time_seconds );

    $wordpress_timezone = new DateTimeZone( get_option( 'timezone_string' ) );
    $pickup_datetime_obj = new DateTime( $pickup_date . ' ' . $start_time, $wordpress_timezone );
    $pickup_deadline_obj = new DateTime( $pickup_date . ' ' . $end_time, $wordpress_timezone );

    update_post_meta($order_id, 'pq_pickup_datetime', $pickup_datetime_obj->format('Y-m-d H:i'));
    update_post_meta($order_id, 'pq_pickup_deadline', $pickup_deadline_obj->format('Y-m-d H:i'));
  }
}

function pq_convert_seconds_to_time( $time_in_seconds ) {
  $time_format = wc_time_format();
  $time = date_i18n( $time_format, $time_in_seconds );

  return $time;
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
  $minimum = 50;

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
  <h4> Livraison gratuite à partir de $150 </h4>
  </span></div>
<?php
}

// -------- Change continue shopping button to link to products page -------- //
add_filter( 'woocommerce_continue_shopping_redirect', 'bbloomer_change_continue_shopping' );
add_filter( 'woocommerce_return_to_shop_redirect', 'bbloomer_change_continue_shopping' );

function bbloomer_change_continue_shopping() {
  return get_permalink( 6720 );
}

// -------- Hide shipping on the cart page ------- //
add_filter( 'woocommerce_cart_ready_to_calc_shipping', 'pq_hide_cart_shipping', 10, 1 );

function pq_hide_cart_shipping( $show_shipping ) {

  if ( get_the_ID() == get_option( 'woocommerce_cart_page_id' ) ){
    $show_shipping = false;
  }
  return $show_shipping;
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
