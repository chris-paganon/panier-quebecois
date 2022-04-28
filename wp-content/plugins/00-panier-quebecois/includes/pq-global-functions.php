<?php
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function myfct_return_true_if_has_category( $myCategory ) {
  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		if ( has_term( $myCategory, 'product_cat', $cart_item[ 'product_id' ] ) ) {
			return true;
		}
	}
	return false;
}

function myfct_return_true_if_has_category_from_order( $order, $myCategory ) {
	$items = $order->get_items();
	foreach ( $items as $item ) {
		$categories = get_the_terms( $item[ 'product_id' ], 'product_cat' );
		foreach ( $categories as $categorie ) {
			if ( $categorie->slug == $myCategory ) {
				return true;
			}
		}
	}
	return false;
}


/**
 * Display next delivery day
 */
add_shortcode( 'pq_next_delivery_date', 'pq_next_delivery_date_fct' );

function pq_next_delivery_date_fct() {

	$next_delivery_day = PQ_delivery_days::pq_get_next_delivery_day_fr();
	$deadline = PQ_delivery_days::pq_get_delivery_deadline_fr();

	$next_delivery_date_html = '<p class="pq-delivery-date">' . esc_html__('Commandez', 'panier-quebecois') . ' ' . esc_html__($deadline, 'panier-quebecois') . ' ' . esc_html__('et recevez votre commande', 'panier-quebecois') . ' ' . esc_html__($next_delivery_day, 'panier-quebecois') . '</p>';
	return $next_delivery_date_html;
}


/**
 * Display next delivery day for the coutdown timer
 */
add_shortcode( 'pq_next_delivery_date_for_countdown', 'pq_next_delivery_date_for_countdown_fct' );

function pq_next_delivery_date_for_countdown_fct() {

	$next_delivery_day = PQ_delivery_days::pq_get_next_delivery_day_fr();

	$next_delivery_date_html = '<p class="pq-countdown-intro">' . esc_html__('Pour être livré', 'panier-quebecois') . ' <u>' . esc_html__($next_delivery_day, 'panier-quebecois') . '</u>' . esc_html__(', il vous reste:', 'panier-quebecois') . '</p>';
	return $next_delivery_date_html;
}


/**
 * Modify translatepress hreflang tags
 */
add_filter('trp_hreflang', 'pq_trpc_change_hreflang', 10, 2 );
 function pq_trpc_change_hreflang( $hreflang, $language ){
    if ($language == 'fr_FR') {  // language code that you want to alter

		// the modified hreflang code you want to display in the page 
		$hreflang = 'fr-CA';
	} elseif ($language == 'en_US') {
		$hreflang = 'en-CA';
	}
	return $hreflang;
}


/**
 * Display countdown wrapper
 */
add_shortcode( 'pq_delivery_countdown', 'pq_delivery_countdown_fct' );

function pq_delivery_countdown_fct() {
	ob_start(); ?>

	<div class="pq_countdown_wrapper">
		<div class="pq_days_wrapper pq_countdown_item">
			<span class="pq_days_digit pq_countdown_digit"></span>
			<span class="pq_days_label pq_countdown_label">J</span>
		</div>
		<div class="pq_hours_wrapper pq_countdown_item">
			<span class="pq_hours_digit pq_countdown_digit"></span>
			<span class="pq_hours_label pq_countdown_label">H</span>
		</div>
		<div class="pq_minutes_wrapper pq_countdown_item">
			<span class="pq_minutes_digit pq_countdown_digit"></span>
			<span class="pq_minutes_label pq_countdown_label">Min</span>
		</div>
		<div class="pq_seconds_wrapper pq_countdown_item">
			<span class="pq_seconds_digit pq_countdown_digit"></span>
			<span class="pq_seconds_label pq_countdown_label">Sec</span>
		</div>
	</div>
  
	<?php
	$countdown_wrapper = ob_get_clean();
	return $countdown_wrapper;
}