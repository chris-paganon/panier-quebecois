<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Manage loyalty rewards
 */

class PQ_loyalty_rewards {
  /**
   * Variables
   * 
   */
  protected static $_instance = null;
  /**
   * Initiate a single instance of the class
   * 
   */
  public static function instance() {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function __construct() {
    $this->pq_init_hooks();
  }

  /**
   * Initiate all the hooked functions
   * 
   */
  public function pq_init_hooks() {
    add_action( 'woocommerce_before_thankyou', array( 'PQ_loyalty_rewards', 'pq_reward_order_percentage' ), 10, 1 );
    add_action( 'wp_login', array( 'PQ_loyalty_rewards', 'pq_points_from_recent_order_login' ), 10, 2 );
    add_action( 'user_register', array( 'PQ_loyalty_rewards', 'pq_points_from_recent_order' ), 10, 1 );
  }

  /**
   * Helper functions
   */

  /* ----- Function to get reward from order id ------ */
  public static function pq_get_reward_from_order( $order ) {
    $multiplier = 0.02;
    $total = $order->get_total();
    $reward = round( $total * $multiplier, 2 );

    foreach ( $order->get_items() as $item_id => $item ) {
      $product_id = $item->get_product_id();

      $has_double_points = get_post_meta( $product_id, '_pq_double_points', true );

      if ( !empty( $has_double_points ) ) {
        $reward += $item->get_total();
      }
    }

    $reward = round( $reward, 2 );

    return $reward;
  }

  /**
   * Reward functions
   */

  /* ----- Get points as percentage of order total ------ */
  public static function pq_reward_order_percentage( $order_id ) {
    if ( !function_exists( 'mycred' ) ) return;

    // Get user
    $order = wc_get_order( $order_id );
    $user_id = $order->get_user_id();
    if ( empty( $user_id ) ) return;

    // Load myCRED
    $mycred = mycred();

    // Make sure user only gets points once per order
    if ( $mycred->has_entry( 'purchase reward', $order_id, $user_id ) ) return;

    if ( pq_has_main_badge($user_id) ) {

      $reward = PQ_loyalty_rewards::pq_get_reward_from_order( $order );

      // Add reward
      $mycred->add_creds(
        'purchase reward',
        $user_id,
        $reward,
        'Reward for store purchase',
        $order_id,
        array( 'ref_type' => 'post' )
      );
    }
  }

  /**
   * Late rewards on login and registration
   */

  /* ----- Get points for order recently made on login ----- */
  public static function pq_points_from_recent_order_login( $user_login, $user ) {
    if ( !function_exists( 'mycred' ) ) return;

    $user_id = $user->ID;
    PQ_loyalty_rewards::pq_points_from_recent_order( $user_id );
  }

  /* ----- Get points for order recently made on registration ----- */
  public static function pq_points_from_recent_order( $user_id ) {
    if ( !function_exists( 'mycred' ) ) return;

    $user = get_user_by( 'id', $user_id );
    $user_email = $user->user_email;

    $deadline_timestamp = time() - 172800;

    // Get 1 order made after deadline with same email
    $last_order = wc_get_orders( array(
      'customer' => $user_email,
      'date_paid' => '>' . $deadline_timestamp,
      'limit' => 1,
    ) );

    if ( !empty( $last_order ) ) {
      $order = reset( $last_order );
      $order_id = $order->get_id();

      update_post_meta( $order_id, '_customer_user', $user_id );
      pq_add_badge_after_order( $order_id );

      $mycred = mycred();

      // Make sure user only gets points once per order
      if ( $mycred->has_entry( 'purchase reward', $order_id, $user_id ) ) return;
      if ( $mycred->has_entry( 'late reward', $order_id, $user_id ) ) return;

      if ( pq_has_main_badge($user_id) ) {

        $reward = PQ_loyalty_rewards::pq_get_reward_from_order( $order );

        // Add reward
        $mycred->add_creds(
          'late reward',
          $user_id,
          $reward,
          'Late reward after account creation or login',
          $order_id
        );
      }
    }
  }
}