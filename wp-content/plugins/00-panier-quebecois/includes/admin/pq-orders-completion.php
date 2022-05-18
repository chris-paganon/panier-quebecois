<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Orders completion functions
 * 
 */

class PQ_orders_completion {
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
    add_action( 'myhook_close_orders', array( $this, 'pq_close_orders' ), 10, 2 );
    add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'pq_handle_orddd_timeslot_timestamp_query_var' ), 10, 2 );
  }

  /**
   * Order closing automation functions
   *
   */

  /* ----- Enable orddd timeslot timestamp for wc_get_orders query ------*/
  public static function pq_handle_orddd_timeslot_timestamp_query_var( $query, $query_vars ) {
    if ( !empty( $query_vars[ '_orddd_timeslot_timestamp' ] ) ) {
      $query[ 'meta_query' ][] = array(
        'key' => '_orddd_timeslot_timestamp',
        'value' => esc_attr( $query_vars[ '_orddd_timeslot_timestamp' ] ),
      );
    }

    return $query;
  }

  /* ----- Closing the orders if orddd is same day and pickup started in the past ----- */
  public static function pq_close_orders( $date, $is_delivery ) {
    $wordpress_timezone = new DateTimeZone( get_option( 'timezone_string' ) );
    $now = new DateTime( '', $wordpress_timezone );
    $delivery_date = $now->format('Y-m-d');

    $query = array(
      'status' => 'wc-processing',
      'limit' => -1,
      '_shipping_date' => $delivery_date,
    );

    $orders = wc_get_orders( $query );

    foreach ( $orders as $order ) {
      $order_id = $order->get_id();
      $pickup_datetime = get_post_meta( $order_id, 'pq_pickup_datetime', true );

      if ( $is_delivery && empty( $pq_is_pickup ) ) {

        $order->update_status( 'completed' );

      } elseif ( !$is_delivery && !empty( $pickup_datetime ) ) {

        $pickup_datetime_obj = new DateTime( $pickup_datetime, $wordpress_timezone );
        $start_interval = new DateTime('- 1 hour', $wordpress_timezone);
        $end_interval = new DateTime('+ 30 minutes', $wordpress_timezone);
        
        if ( $start_interval < $pickup_datetime_obj && $pickup_datetime_obj < $end_interval ) {
          $order->update_status( 'completed' );
        }        
      }
    }
  }
}