<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
Plugin Name: Klaviyo Panier Québécois Addon
Description: Modify optin checkbox
Version: 1.0
Author: Christophe Paganon
*/

/**
 * 
 * Adjust Klaviyo checkout consent checkbox
 * 
 */


// -------- Move Klaviyo checkbox button ---------- //
//Remove Klaviyo checkbox from billing fields
add_action('woocommerce_before_checkout_form', 'pq_move_klaviyo_checkbox');

function pq_move_klaviyo_checkbox() {
	remove_filter('woocommerce_checkout_fields', 'kl_checkbox_custom_checkout_field', 11);
}

//Add Klaviyo checkbox above the "place order" button
add_action('woocommerce_review_order_before_submit', 'pq_kl_checkbox_custom_checkout_field');


//Modify checkbox code to fit outside billing fields
function pq_kl_checkbox_custom_checkout_field() {
	$klaviyo_settings = get_option('klaviyo_settings');

	if (
		isset( $klaviyo_settings['klaviyo_subscribe_checkbox'] )
    	&& $klaviyo_settings['klaviyo_subscribe_checkbox']
    	&& !empty( $klaviyo_settings['klaviyo_newsletter_list_id'] )
	) {

		woocommerce_form_field( 'kl_newsletter_checkbox', array(
		'type'          => 'checkbox',
		'class'         => array('kl_newsletter_checkbox_field'),
		'label'         => $klaviyo_settings['klaviyo_newsletter_text'],
		'value'  => true,
		'default' => 1,
		'required'  => false,
		));
	}
}


//Replace Klaviyo add-to-list by our variation to enable SMS and email consent from same checkbox
add_action( 'woocommerce_checkout_update_order_meta', 'pq_replace_klaviyo_add_to_list', 1 );

function pq_replace_klaviyo_add_to_list() {
	remove_action( 'woocommerce_checkout_update_order_meta', 'kl_add_to_list' );
	add_action( 'woocommerce_checkout_update_order_meta', 'pq_kl_add_to_list' );
}


//Modify add-to-list original code to enable SMS and email consent from same checkbox
function pq_kl_add_to_list() {

    $klaviyo_settings = get_option( 'klaviyo_settings' );
    $email = $_POST['billing_email'];
    $phone = $_POST['billing_phone'];
    $country = $_POST['billing_country'];
    $url = 'https://www.klaviyo.com/api/webhook/integration/woocommerce?c=' . $klaviyo_settings['klaviyo_public_api_key'];
    $body = array(
        'data' => array(),
    );

    if ( isset( $_POST['kl_newsletter_checkbox'] ) && $_POST['kl_newsletter_checkbox'] ) {
        array_push( $body['data'], array(
            'customer' => array(
                'email' => $email,
                'country' => $country,
                'phone' => $phone,
            ),
            'consent' => true,
            'updated_at' => gmdate( DATE_ATOM, date_timestamp_get( date_create() ) ),
            'consent_type' => 'sms',
            'group_id' => $klaviyo_settings['klaviyo_sms_list_id'],
        ) );
    }

    if ( isset( $_POST['kl_newsletter_checkbox'] ) && $_POST['kl_newsletter_checkbox'] ) {
        array_push( $body['data'], array(
            'customer' => array(
                'email' => $email,
                'phone' => $phone,
            ),
            'consent' => true,
            'updated_at' => gmdate( DATE_ATOM, date_timestamp_get( date_create() ) ),
            'consent_type' => 'email',
            'group_id' => $klaviyo_settings['klaviyo_newsletter_list_id'],
        ) );
    }

    wp_remote_post( $url, array(
            'method' => 'POST',
            'httpversion' => '1.0',
            'blocking' => false,
            'headers' => array(
                'X-WC-Webhook-Topic' => 'custom/consent',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $body ),
            'data_format' => 'body',
        )
    );
}


/**
 * 
 * Klaviyo connector
 * 
 */

 /**
 * Send identify data to Klaviyo
 */
function pq_send_identify_to_klaviyo ( $properties ) {
    $url = 'https://a.klaviyo.com/api/identify';
    $klaviyo_settings = get_option( 'klaviyo_settings' );

    $arg = array(
        'token' => $klaviyo_settings['klaviyo_public_api_key'],
        'properties' => $properties,
    );

    $response = wp_remote_post( $url, array(
        'method' => 'POST',
        'httpversion' => '1.0',
        'blocking' => false,
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode( $arg ),
        'data_format' => 'body',
    ));
}

/**
 * Get orders count and account info properties
 */
function pq_get_orders_count_properties( $user_id, $email ) {

    $user_orders = wc_get_orders( array(
        'limit' => -1,
        'customer_id' => $user_id,
    ));
    $count_user_orders = count( $user_orders );
    
    $properties = array( 
        'email' => $email,
        'pq_has_account' => 'yes',
        'pq_orders_count' => $count_user_orders,
    );

    return $properties;
}


/**
 * Send loyalty points info after any points modification
 */
add_filter( 'mycred_add_finished', 'pq_kl_add_loyalty_points', 10, 3 );

function pq_kl_add_loyalty_points( $execute, $add_creds_variables, $mycred_settings ) {

    if ( ! function_exists( 'mycred' ) ) return;
    if ( ! $execute ) return $execute;

    $user_id = $add_creds_variables['user_id'];
    $user = get_user_by('id', $user_id);

    if ( empty( $user_id ) ) return;

    $loyalty_points = mycred_get_users_cred( $user_id );

    if ( $loyalty_points === 0 ) return;

    global $mycred_partial_payment;
    $loyalty_dollars = round ($loyalty_points * $mycred_partial_payment['exchange'], 2);

    $email = $user->user_email;

    $properties = array( 
        'email' => $email,
        'loyalty_points' => $loyalty_points,
        'loyalty_dollars' => $loyalty_dollars,
    );

    pq_send_identify_to_klaviyo($properties);

    return $execute;
}

/**
 * Add badge info after badge was added
 */
add_action( 'mycred_after_badge_assign', 'pq_kl_add_loyalty_badge', 10, 3 );

function pq_kl_add_loyalty_badge( $user_id, $badge_id, $new_level ) {
    
    $user = get_user_by('id', $user_id);
    $email = $user->user_email;

    $main_badge_id = pq_get_main_badge_id();

    if ( $badge_id === $main_badge_id ) {

        $properties = array( 
            'email' => $email,
            'pq_has_main_badge' => 'yes',
        );
        pq_send_identify_to_klaviyo($properties);
    }
}


/**
 * Add registered orders count after purchase
 */
add_action( 'woocommerce_payment_complete', 'pq_send_order_count_after_order_made', 20, 1 );

function pq_send_order_count_after_order_made( $order_id ) {
    
    $order = wc_get_order( $order_id );
    $user_id = $order->get_user_id();
    if ( empty( $user_id ) ) return;
    
    $user = get_user_by( 'id', $user_id );
    $email = $user->user_email;
    
    $properties = pq_get_orders_count_properties($user_id, $email);
    pq_send_identify_to_klaviyo($properties);
}

/**
 * Add registered orders count on login
 */
add_action( 'wp_login', 'pq_send_order_count_after_login', 20, 2 );

function pq_send_order_count_after_login( $user_login, $user ) {
    
    $user_id = $user->ID;
    $email = $user->user_email;
    
    $properties = pq_get_orders_count_properties($user_id, $email);
    pq_send_identify_to_klaviyo($properties);
}

/**
 * Add registered orders count on registration
 */
add_action( 'user_register', 'pq_send_order_count_after_registration', 20, 1 );

function pq_send_order_count_after_registration( $user_id ) {

    $user = get_user_by( 'id', $user_id );
    $email = $user->user_email;
    
    $properties = pq_get_orders_count_properties($user_id, $email);
    pq_send_identify_to_klaviyo($properties);
}