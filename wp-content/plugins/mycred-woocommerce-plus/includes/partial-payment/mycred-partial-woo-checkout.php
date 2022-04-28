<?php
// No dirrect access
if ( ! defined( 'MYCRED_WOOPLUS_VERSION' ) ) exit;

/**
 * Before Order Review
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_before_order_review' ) ) :
	function mycred_part_woo_before_order_review() {

		global $mycred_partial_payment;

		if ( $mycred_partial_payment['position'] !== 'before' ) return;

		wc_get_template( 'checkout/mycred-partial-payments.php', array( 'checkout' => WC()->checkout() ) );

	}
endif;
add_action( 'woocommerce_checkout_before_order_review', 'mycred_part_woo_before_order_review' );

/**
 * After Order Review
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_after_order_review' ) ) :
	function mycred_part_woo_after_order_review() {

		global $mycred_partial_payment;

		if ( $mycred_partial_payment['position'] !== 'after' ) return;

		wc_get_template( 'checkout/mycred-partial-payments.php', array( 'checkout' => WC()->checkout() ) );

	}
endif;
add_action( 'woocommerce_checkout_order_review', 'mycred_part_woo_after_order_review', 15 );

/**
 * Insert Total Cost
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_insert_total_cost' ) ) :
	function mycred_part_woo_insert_total_cost() {

		global $mycred_partial_payment;

		$mycred       = mycred( $mycred_partial_payment['point_type'] );

		$show_total         = $mycred_partial_payment['checkout_total'];

		if ( ( $show_total == 'both' ) 
			|| ( $show_total == 'cart' && is_cart() ) 
			|| ( $show_total == 'checkout' && is_checkout() ) 
		) {

			$the_cart       = WC()->cart;
			$the_cart_total = $the_cart->total;
			$balance        = ( is_user_logged_in() ) ? $mycred->get_users_balance( get_current_user_id() ) : 0;

			$cost           = $mycred->number( $the_cart_total );
			if ( $mycred_partial_payment['exchange'] != 1 ) {
				$cost = $mycred->number( ( $the_cart_total / $mycred_partial_payment['exchange'] ) );
				$cost = apply_filters( 'mycred_woo_order_cost', $cost, $the_cart, true, $mycred );
			}

			if($mycred_partial_payment['free_shipping'] == 'yes'){
				$payment_made = mycred_get_users_incomplete_partial_payment( get_current_user_id() );
				$amount_paid = 0;
				if(count(WC()->cart->get_applied_coupons()) > 1){
				foreach ( WC()->cart->get_applied_coupons() as $code ) {
					$coupon = new WC_Coupon( $code );
					$amount_paid+= $coupon->amount;
				}

			}else{
				$amount_paid =abs((float)$payment_made->creds);				
			}
				if ($payment_made != false && $amount_paid >= ((float)$the_cart->subtotal + $the_cart->shipping_total)  ) {
						$cost = $amount_paid - ($the_cart->subtotal + $the_cart->shipping_total);
				}

				if ($payment_made != false && $amount_paid <= ((float)$the_cart->subtotal + $the_cart->shipping_total)  ) {
						$cost = ($the_cart->subtotal + $the_cart->shipping_total) - $amount_paid;
				}

		}


?>
<tr class="total">
	<th><strong><?php echo $mycred->template_tags_general( $mycred_partial_payment['checkout_total_label'] ); ?></strong></th>
	<td>
		<div class="current-balance order-total-in-points">
			<strong class="<?php if ( $balance < $cost ) echo 'mycred-low-funds'; else echo 'mycred-funds'; ?>"<?php if ( $balance < $cost ) echo ' style="color:red;"'; ?>><?php echo $mycred->format_creds( $cost ); ?></strong> 
		</div>
	</td>
</tr>
<?php

		}

	}
endif;
add_action( 'woocommerce_review_order_after_order_total', 'mycred_part_woo_insert_total_cost', 40 );
add_action( 'woocommerce_cart_totals_after_order_total',  'mycred_part_woo_insert_total_cost', 40 );


/**
 * Insert shipping cost in order view and cart if paid
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_insert_shipping_price' ) ) :
	function mycred_part_woo_insert_shipping_price() {

		global $mycred_partial_payment;

		$mycred       = mycred( $mycred_partial_payment['point_type'] );

		$show_total         = $mycred_partial_payment['checkout_total'];

		if ( ( $show_total == 'both' ) 
			|| ( $show_total == 'cart' && is_cart() ) 
			|| ( $show_total == 'checkout' && is_checkout() ) 
		) {

			$the_cart       = WC()->cart;
			
			if($mycred_partial_payment['free_shipping'] == 'yes'){
				
				$payment_made = mycred_get_users_incomplete_partial_payment( get_current_user_id() );
				$shipping_cost = $the_cart->shipping_total;
			$amount_paid = 0;
			if(count(WC()->cart->get_applied_coupons()) > 1){
				foreach ( WC()->cart->get_applied_coupons() as $code ) {
					$coupon = new WC_Coupon( $code );
					$amount_paid+= $coupon->amount;
				}
			}else{
				$amount_paid = abs((float)$payment_made->creds);
			}
				

				if ($payment_made != false && $amount_paid >= (float)$the_cart->subtotal ) {
						$amount_left = $amount_paid - ($the_cart->subtotal + $the_cart->shipping_total);
						if ($amount_left == 0) {
							$shipping_cost = 0 - $the_cart->shipping_total; 
						}else{
							$shipping_cost = abs($amount_left) - $the_cart->shipping_total ;
						}
				}

				if ($shipping_cost > 0) {
							return false;
				}
?>

<tr class="total">
	<th><strong><?php echo  __( 'Shipping Payment Paid', 'mycredpartwoo' );?></strong></th>
	<td>
		<div class="current-balance order-total-in-points">
			<strong ><?php echo $shipping_cost; ?></strong> 
		</div>
	</td>
</tr>
<?php

		}




		}

	}
endif;

add_action( 'woocommerce_review_order_before_order_total', 'mycred_part_woo_insert_shipping_price', 40 );
add_action( 'woocommerce_cart_totals_before_order_total',  'mycred_part_woo_insert_shipping_price', 40 );


/**
 * Insert Total Cost
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_insert_total_order_cost' ) ) :
	function mycred_part_woo_insert_total_order_cost() {

		global $mycred_partial_payment;

		$mycred       = mycred( $mycred_partial_payment['point_type'] );

		$show_total         = $mycred_partial_payment['checkout_total'];

		if ( ( $show_total == 'both' ) 
			|| ( $show_total == 'cart' && is_cart() ) 
			|| ( $show_total == 'checkout' && is_checkout() ) 
		) {

			$the_cart       = WC()->cart;
			$the_cart_total = $the_cart->total;

			$balance        = ( is_user_logged_in() ) ? $mycred->get_users_balance( get_current_user_id() ) : 0;

			$cost           = $mycred->number( $the_cart_total );

			if ( $mycred_partial_payment['exchange'] != 1 ) {
				$cost = $mycred->number( ( $the_cart_total / $mycred_partial_payment['exchange'] ) );
				$cost = apply_filters( 'mycred_woo_order_cost', $cost, $the_cart, true, $mycred );
			}

			if($mycred_partial_payment['free_shipping'] == 'yes'){
				$payment_made = mycred_get_users_incomplete_partial_payment( get_current_user_id() );
				$amount_paid = 0;
				if(count(WC()->cart->get_applied_coupons()) > 1){
				foreach ( WC()->cart->get_applied_coupons() as $code ) {
					$coupon = new WC_Coupon( $code );
					$amount_paid+= $coupon->amount;
				}

			}else{
				$amount_paid =abs((float)$payment_made->creds);				
			}
				
				if ($payment_made != false && $amount_paid >= ((int)$the_cart->subtotal + $the_cart->shipping_total)  ) {
						$cost = $amount_paid - ($the_cart->subtotal + $the_cart->shipping_total);
				}else {
						$cost = ($the_cart->subtotal + $the_cart->shipping_total) - $amount_paid;
				}

		}
		$installed_payment_methods = WC()->payment_gateways->payment_gateways();
		if ( isset($installed_payment_methods['mycred']->enabled) && $installed_payment_methods['mycred']->enabled == 'yes'){
			 

?>
<style type="text/css">
 	.order-total{
		display: none;
	}
</style>
<tr class="total">
	<th><strong><?php echo  __( 'Total', 'mycredpartwoo' );?></strong></th>
	<td>
		<div class="current-balance order-total-in-points">
			<strong ><?php echo $mycred->format_creds( $cost ); ?></strong> 
		</div>
	</td>
</tr>
<?php
		}
		}

	}
endif;
add_action( 'woocommerce_review_order_before_order_total', 'mycred_part_woo_insert_total_order_cost', 40 );
add_action( 'woocommerce_cart_totals_before_order_total',  'mycred_part_woo_insert_total_order_cost', 40 );


/**
 * Insert Total Balance
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_insert_total_balance' ) ) :
	function mycred_part_woo_insert_total_balance() {

		if ( ! is_user_logged_in() ) return;

		global $mycred_partial_payment;

		$user_id      = get_current_user_id();
		$mycred       = mycred( $mycred_partial_payment['point_type'] );

		if ( $mycred->exclude_user( $user_id ) ) return;

		$show_balance = $mycred_partial_payment['checkout_balance'];

		if ( ( $show_balance == 'both' ) 
			|| ( $show_balance == 'cart' && is_cart() ) 
			|| ( $show_balance == 'checkout' && is_checkout() ) 
		) {

			$balance = $mycred->get_users_balance( $user_id );
	
		

?>
<tr class="total">
	<th><?php echo $mycred->template_tags_general( $mycred_partial_payment['checkout_balance_label'] ); ?></th>
	<td>
		<div class="current-balance order-total-in-points">
			<?php echo $mycred->format_creds( $balance ) ; ?> 
		</div>
	</td>
</tr>
<?php

		}

	}
endif;
add_action( 'woocommerce_review_order_after_order_total', 'mycred_part_woo_insert_total_balance', 50 );
add_action( 'woocommerce_cart_totals_after_order_total',  'mycred_part_woo_insert_total_balance', 50 );

/**
 * AJAX Call Handler
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_ajax_handler' ) ) :
	function mycred_part_woo_ajax_handler() {
		global $mycred_partial_payment;

		if ( is_page( (int) get_option( 'woocommerce_checkout_page_id' ) ) 
			&& isset( $_POST['action'] ) 
			&& isset( $_POST['token'] ) 
			&& $_POST['action'] === 'mycred-new-partial-payment' 
			&& wp_verify_nonce( $_POST['token'], 'mycred-partial-payment-new' )
		) {
			//check if any coupon is applied before so then return error only if max is less then 100
			if ($mycred_partial_payment['max'] < 100 && count(WC()->cart->get_coupons()) >= 1) {
				# code...
				wp_send_json_error( __( 'Please remove previous coupon to apply new discount.', 'mycredpartwoo' ) );
			}

		$settings      = mycred_part_woo_settings();
		$user_id       = get_current_user_id();
		$mycred        = mycred( $settings['point_type'] );

		// Excluded from usage
		if ( $mycred->exclude_user( $user_id ) ) wp_send_json_error( __( 'You are not allowed to use this feature.', 'mycredpartwoo' ) );

		$balance       = $mycred->get_users_balance( $user_id );

		// ************************************ //
		//DEFAULT TO USER BALANCE INSTEAD OF ZERO
		if ( $_POST['amount'] == 0 ) {
			$amount = $mycred->get_users_balance( $user_id );
		} else {
			$amount = $mycred->number( abs( $_POST['amount'] ) );
		}
		//END OF EDIT
		// ************************************ //

		// Invalid amount
		if ( $amount == $mycred->zero() ) wp_send_json_error( __( 'Amount can not be zero.', 'mycredpartwoo' ) );

		// Too high amount
		if ( $balance < $amount ) wp_send_json_error( __( 'Insufficient Funds.', 'mycredpartwoo' ) );

		$total         = mycred_part_woo_get_total();

		$value         = number_format( ( $settings['exchange'] * $amount ), 2, '.', '' );
		
		// ************************************ //
		//IF USER BALANCE IS HIGHER THAN TOTAL, DEFAULT TO TOTAL
		if ( $value > $total ) {
			$amount = number_format( ( $total / $settings['exchange'] ), 0, '.', '');
			$value = $total;
		}
		//END OF EDIT
		// ************************************ //

		if ( $value > ( ( $total/ 100 ) * $mycred_partial_payment['max'] ) )
			wp_send_json_error( __( 'The amount can not be greater than the maximum amount.', 'mycredpartwoo' ) );

		// Create a Woo Coupon
		$coupon_code   = $user_id . time();
		$new_coupon_id = wp_insert_post( array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'shop_coupon'
		) );

		if ( $new_coupon_id === NULL || is_wp_error( $new_coupon_id ) )
			wp_send_json_error( __( 'Failed to complete transaction. Error 1. Please contact support.', 'mycredpartwoo' ) );

		// Update Coupon details
		update_post_meta( $new_coupon_id, 'discount_type', 'fixed_cart' );
		update_post_meta( $new_coupon_id, 'coupon_amount', $value );
		update_post_meta( $new_coupon_id, 'individual_use', 'no' );
		update_post_meta( $new_coupon_id, 'product_ids', '' );
		update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );

		// Make sure you set usage_limit to 1 to prevent duplicate usage!!!
		update_post_meta( $new_coupon_id, 'usage_limit', 1 );
		update_post_meta( $new_coupon_id, 'usage_limit_per_user', 1 );
		update_post_meta( $new_coupon_id, 'limit_usage_to_x_items', '' );
		update_post_meta( $new_coupon_id, 'usage_count', '' );
		update_post_meta( $new_coupon_id, 'expiry_date', '' );
		update_post_meta( $new_coupon_id, 'apply_before_tax', ( ( $settings['before_tax'] == 'no' ) ? 'yes' : 'no' ) ); // setting
		update_post_meta( $new_coupon_id, 'free_shipping', ( ( $settings['free_shipping'] == 'no' ) ? 'no' : 'yes' ) ); // setting
		update_post_meta( $new_coupon_id, 'product_categories', array() );
		update_post_meta( $new_coupon_id, 'exclude_product_categories', array() );
		update_post_meta( $new_coupon_id, 'exclude_sale_items', ( ( $settings['sale_items'] == 'no' ) ? 'yes' : 'no' ) ); // setting
		update_post_meta( $new_coupon_id, 'minimum_amount', '' );
		update_post_meta( $new_coupon_id, 'customer_email', array() );

		$applied = WC()->cart->add_discount( $coupon_code );

		if ( $applied === true ) {
			
			if($settings['log'] == '') 
			$settings['log'] = 'Partial Payment';
			
			
			
			// Deduct amount only if coupon was successfully applied
			$mycred->add_creds(
				'partial_payment',
				$user_id,
				0 - $amount,
				$settings['log'],
				$new_coupon_id,
				'',
				$settings['point_type']
			);

			wc_clear_notices();
			wc_add_notice( __( 'Votre rabais en points a été appliqué', 'mycredpartwoo' ) );

			wp_send_json_success();

		}

		// Delete the coupon
		wp_trash_post( $new_coupon_id );

		wp_send_json_error( __( 'Failed to complete transaction. Error 2. Please contact support.', 'mycredpartwoo' ) );

		}

	}
endif;
add_action( 'template_redirect', 'mycred_part_woo_ajax_handler', 15 );

/**
 * AJAX Reload Handler
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_ajax_reload' ) ) :
	function mycred_part_woo_ajax_reload() {

		if ( is_page( (int) get_option( 'woocommerce_checkout_page_id' ) ) 
			&& isset( $_POST['action'] ) 
			&& isset( $_POST['token'] ) 
			&& $_POST['action'] === 'mycred-partial-payment-reload' 
			&& wp_verify_nonce( $_POST['token'], 'mycred-partial-payment-reload' )
		) {

		// Define so Woo will calculate the grand total
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) define( 'WOOCOMMERCE_CHECKOUT', true );

		// Calculate totals
		WC()->cart->calculate_totals();

		// Load template
		wc_get_template( 'checkout/mycred-partial-payments.php', array( 'checkout' => WC()->checkout() ) );

		die;

		}

	}
endif;
add_action( 'template_redirect', 'mycred_part_woo_ajax_reload', 10 );

/**
 * WooCommerce Coupon Label
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_coupon_label' ) ) :
	function mycred_part_woo_coupon_label( $label, $coupon ) {

		global $mycred_partial_payment;

		$partial_payment = mycred_get_partial_payment( $coupon->get_id() );

		if ( isset( $partial_payment->user_id ) ) {

			$mycred = mycred( $mycred_partial_payment['point_type'] );
			$label  = $mycred->template_tags_general( _x( '%singular% Payment', 'Discount applied to cart label', 'mycredpartwoo' ) );

		}

		return $label;

	}
endif;
add_action( 'woocommerce_cart_totals_coupon_label', 'mycred_part_woo_coupon_label', 10, 2 );

/**
 * Remove Coupon Option
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_remove_coupon_option' ) ) :
	function mycred_part_woo_remove_coupon_option( $html, $coupon ) {

		global $mycred_partial_payment;

		$partial_payment = mycred_get_partial_payment( $coupon->get_id() );
		if ( isset( $partial_payment->user_id ) && $mycred_partial_payment['undo'] === 'no' ) {

			// Mimic what WooCommerce does but without the removal link
			$value   = array();

			if ( $amount = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax ) ) {
				$discount_html = '-' . wc_price( $amount );
			} else {
				$discount_html = '';
			}

			$value[] = apply_filters( 'woocommerce_coupon_discount_amount_html', $discount_html, $coupon );

			if ( $coupon->get_free_shipping() )
				$value[] = __( 'Free shipping coupon', 'mycredpartwoo' );

			// get rid of empty array elements
			$value   = array_filter( $value );
			$html    = implode( ', ', $value );

		}

		return $html;

	}
endif;
add_filter( 'woocommerce_cart_totals_coupon_html', 'mycred_part_woo_remove_coupon_option', 10, 2 );

/**
 * Remove Coupon Action
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_remove_coupon_action' ) ) :
	function mycred_part_woo_remove_coupon_action( $coupon = '' ) {
		
		$settings        = mycred_part_woo_settings();
		if ( $settings['undo'] != 'yes' ) return;
	 
		$coupon          = get_page_by_title( $coupon, OBJECT, 'shop_coupon' );
		if ( $coupon === NULL ) return;
		$coupon_post_id  = $coupon->ID;

		global $wpdb, $mycred_partial_payment;

		$partial_payment = mycred_get_partial_payment( $coupon_post_id );
		if ( $partial_payment !== false ) {

			$mycred = mycred( $partial_payment->ctype );

			// Refund payment
			$mycred->add_creds(
				'partial_payment_refund',
				$partial_payment->user_id,
				abs( $partial_payment->creds ),
				$settings['log_refund'],
				$partial_payment->ref_id,
				'',
				$partial_payment->ctype
			);

			// Update partial payment in log to prevent re-use
			$wpdb->update(
				$mycred->log_table,
				array( 'ref_id' => 0 ),
				array( 'id' => $partial_payment->id ),
				array( '%d' ),
				array( '%d' )
			);

			// Trash coupon post object
			wp_trash_post( $coupon_post_id );
			// DEBUG _doing_it_wrong( 'mycred_part_woo_remove_coupon_action', 'Removed partial payment since coupon was removed.', '1.0' );

		}

	}
endif;
add_action( 'woocommerce_removed_coupon', 'mycred_part_woo_remove_coupon_action' );

/**
 * Remove Cart Items
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_remove_cart_items' ) ) :
	function mycred_part_woo_remove_cart_items( $cart_item_key, $cart ) {

		if ( $cart->get_cart_contents_count() == 0 && is_user_logged_in() ) {

			$settings        = mycred_part_woo_settings();
			if ( $settings['undo'] != 'yes' ) return;

			$partial_payment = mycred_get_users_incomplete_partial_payment( get_current_user_id() );
			if ( $partial_payment !== false ) {

				$mycred = mycred( $partial_payment->ctype );

				// Refund payment
				$mycred->add_creds(
					'partial_payment_refund',
					$partial_payment->user_id,
					abs( $partial_payment->creds ),
					$settings['log_refund'],
					0,
					$partial_payment->ref_id,
					$partial_payment->ctype
				);

				global $wpdb;

				// Update partial payment in log to prevent re-use
				$wpdb->update(
					$mycred->log_table,
					array( 'ref_id' => 0 ),
					array( 'id' => $partial_payment->id ),
					array( '%d' ),
					array( '%d' )
				);

				// Remove coupon
				// Prevent our own function from running
				remove_action( 'woocommerce_removed_coupon', 'mycred_part_woo_remove_coupon_action' );

				$cart->remove_coupon( get_the_title( $partial_payment->ref_id ) );

				add_action( 'woocommerce_removed_coupon', 'mycred_part_woo_remove_coupon_action' );

				// Trash coupon post object
				wp_trash_post( $partial_payment->ref_id );

				// DEBUG _doing_it_wrong( 'mycred_part_woo_remove_cart_items', 'Removed partial payment since cart is now empty.', '1.0' );

				if ( $settings['refund_message'] != '' ) {

					$message = $mycred->template_tags_amount( $settings['refund_message'], abs( $partial_payment->creds ) );
					wc_add_notice( $message, 'success' );

				}

			}

		}

	}
endif;
add_action( 'woocommerce_cart_item_removed', 'mycred_part_woo_remove_cart_items', 10, 2 );

/**
 * Prevent to increase total balance for partial payment refund
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_prevent_increase_total_balance' ) ) :
	function mycred_prevent_increase_total_balance( $value, $data ) {

		if ( $data['ref'] == 'partial_payment_refund' ) return false;

		return $value;
	
	}
endif;
add_filter( 'mycred_update_total_balance', 'mycred_prevent_increase_total_balance', 10, 2 );
