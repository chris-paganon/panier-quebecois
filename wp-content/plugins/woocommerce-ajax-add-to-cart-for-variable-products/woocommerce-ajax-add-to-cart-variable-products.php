<?php
/*
Plugin Name: Woocommerce Add to cart Ajax for variable products
Plugin URI: http://www.rcreators.com/woocommerce-ajax-add-to-cart-variable-products
Description: Ajax based add to cart for varialbe products in woocommerce.
Author: Rishi Mehta - Rcreators Websolutions
Version: 2.2
Author URI: http://rcreators.com
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
* Check if WooCommerce is active
**/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || ( get_site_option('active_sitewide_plugins') && array_key_exists( 'woocommerce/woocommerce.php', get_site_option('active_sitewide_plugins') ) ) ) {
	
	/**
	* Create the section beneath the products tab
	**/
	 
	add_filter( 'woocommerce_get_sections_products', 'wc_ajax_add_to_cart_variable_add_section' );
	function wc_ajax_add_to_cart_variable_add_section( $sections ) {
		
		$sections['wc_ajax_add_to_cart_variable'] = __( 'WC Ajax for Variable Products', 'rc_wc_variable_ajax' );
		return $sections;
		
	}
	
	add_filter( 'woocommerce_get_settings_products', 'wc_ajax_add_to_cart_variable_all_settings', 10, 2 );
	
	function wc_ajax_add_to_cart_variable_all_settings( $settings, $current_section ) {
	 
		/**
		 * Check the current section is what we want
		 **/
	 
		if ( $current_section == 'wc_ajax_add_to_cart_variable' ) {
	 
			$settings_slider = array();
	 
			// Add Title to the Settings
			$settings_slider[] = array( 'name' => __( 'WC Ajax for Variable Products Settings', 'rc_wc_variable_ajax' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure WC Ajax for Variable Products', 'rc_wc_variable_ajax' ), 'id' => 'wc_ajax_add_to_cart_variable' );
	 
			// Add first checkbox option
			$settings_slider[] = array(
	 
				'name'     => __( 'Add Selection option to Category Page', 'rc_wc_variable_ajax' ),
				'desc_tip' => __( 'This will automatically insert variable selection options on product Category Archive Page', 'rc_wc_variable_ajax' ),
				'id'       => 'wc_ajax_add_to_cart_variable_category_page',
				'type'     => 'checkbox',
				'css'      => 'min-width:300px;',
				'desc'     => __( 'Enable Varition select option on Category Archive page', 'rc_wc_variable_ajax' ),
	 
			);
			
			$settings_slider[] = array( 'type' => 'sectionend', 'id' => 'wc_ajax_add_to_cart_variable' );
	 
			return $settings_slider;
		
		/**
		 * If not, return the standard settings
		 **/
	 
		} else {
	 
			return $settings;
	 
		}
	 
	}
	
	$category_page = get_option( 'wc_ajax_add_to_cart_variable_category_page' );
	

	if(isset($category_page) && $category_page == "yes" ) {

		if ( ! function_exists( 'woocommerce_template_loop_add_to_cart' ) ) {
			
			function woocommerce_template_loop_add_to_cart( $args = array()) {
				
				global $product;

				$product_type = $product->get_type();
				
				if ( $product ) {
					$defaults = array(
						'quantity' => 1,
						'class'    => implode( ' ', array_filter( array(
							'button',
							'product_type_' . $product_type,
							$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
							$product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : ''
						) ) ),
						'attributes' => array('data-product_id' => $product->get_id(), 'data-product_sku' => $product->get_sku() ),
					);
				
					//$args = apply_filters( 'woocommerce_loop_add_to_cart_args', wp_parse_args( $args, $defaults ), $product );
		
					if ($product_type == "variable") {
						woocommerce_variable_add_to_cart();
					}
					else {
						wc_get_template( 'loop/add-to-cart.php', $args );
					}
				}
			}
		}
	}
	
	function ajax_add_to_cart_script() {
	  wp_enqueue_script( 'add-to-cart-variation_ajax', plugins_url() . '/woocommerce-ajax-add-to-cart-for-variable-products/js/add-to-cart-variation.js', array('jquery'), '', true );
	}
	add_action( 'wp_enqueue_scripts', 'ajax_add_to_cart_script',99 );
	
	/* AJAX add to cart variable added by Rishi Mehta - rishi@rcreators.com */
	add_action( 'wp_ajax_woocommerce_add_to_cart_variable_rc', 'woocommerce_add_to_cart_variable_rc_callback' );
	add_action( 'wp_ajax_nopriv_woocommerce_add_to_cart_variable_rc', 'woocommerce_add_to_cart_variable_rc_callback' );
	
	function woocommerce_add_to_cart_variable_rc_callback() {
		ob_start();
		
		$product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_POST['product_id'] ) );
		$quantity = empty( $_POST['quantity'] ) ? 1 : apply_filters( 'woocommerce_stock_amount', $_POST['quantity'] );
		$variation_id = $_POST['variation_id'];		

		$cart_item_data = $_POST;
		unset($cart_item_data['quantity']);
		
		$variation = array();

		foreach ($cart_item_data as $key => $value) {
			if (preg_match("/^attribute*/", $key)) {
				$variation[$key] = $value;
			}
		}
		
		foreach ($variation as $key=>$value) { $variation[$key] = stripslashes($value); }
		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
	
		if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data  ) ) {
			do_action( 'woocommerce_ajax_added_to_cart', $product_id );
			if ( get_option( 'woocommerce_cart_redirect_after_add' ) == 'yes' ) {
				wc_add_to_cart_message( $product_id );
			}
			global $woocommerce;
			$items = $woocommerce->cart->get_cart();
			wc_setcookie( 'woocommerce_items_in_cart', count( $items ) );
            wc_setcookie( 'woocommerce_cart_hash', md5( json_encode( $items ) ) );
            do_action( 'woocommerce_set_cart_cookies', true );
			// Return fragments
			WC_AJAX::get_refreshed_fragments();
		
		} else {
	
			// If there was an error adding to the cart, redirect to the product page to show any errors
			$data = array(
				'error' => true,
				'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id )
			);
			wp_send_json_error( $data );
		}
	}  
}
?>