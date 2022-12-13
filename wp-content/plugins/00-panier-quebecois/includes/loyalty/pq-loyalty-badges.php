<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Get the ID of the main PQ badge
 */
function pq_get_main_badge_id() {
    $badge_id = 42342;
    return $badge_id;
}


/**
 * Check if user has the main PQ badge
 */
function pq_has_main_badge( $user_id ) {
    if ( !function_exists( 'mycred' ) ) return false;
    $pq_badge_id = pq_get_main_badge_id();
	$user_badges = mycred_get_users_badges($user_id);

	if ( array_key_exists($pq_badge_id, $user_badges) ) {
        return true;
    } else {
        return false;
    }
}


/**
 * Assign badge to user after payment is completed
 */
add_action( 'woocommerce_payment_complete', 'pq_add_badge_after_order', 10, 1 );

function pq_add_badge_after_order( $order_id ) {
    if ( !function_exists( 'mycred' ) ) return;

    $order = wc_get_order( $order_id );
    $user_id = $order->get_user_id();

    //Give badge only if it's a connected user
    if ( $user_id ) {

        //Only run if user doesn't already have the badge
        if ( ! pq_has_main_badge($user_id) ) {

            $minimum_orders = 5;
                    
            //Give badge if user has 3+ orders
            $user_orders = wc_get_orders( array(
                'limit' => $minimum_orders,
                'customer_id' => $user_id,
            ));
            
            $count_user_orders = count( $user_orders );
            if ( $count_user_orders >= $minimum_orders ) {
                $pq_badge_id = pq_get_main_badge_id();
                mycred_assign_badge_to_user( $user_id, $pq_badge_id, 1 );
            }
        }
    }
}