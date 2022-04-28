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
    $now = current_time( 'timestamp', false );

    if ( $is_delivery ) {
      $start_interval = strtotime( 'yesterday 11:30pm', $now );
      $end_interval = strtotime( 'today 11:59pm', $now );

      $query = array(
        'status' => 'wc-processing',
        'limit' => -1,
        'meta_key' => '_orddd_timestamp',
        'meta_compare' => 'BETWEEN',
        'meta_value' => array( $start_interval, $end_interval ),
      );
    } else {
      $start_interval = $now - 3600;
      $end_interval = $now + 30 * 60;

      $query = array(
        'status' => 'wc-processing',
        'limit' => -1,
        'meta_key' => '_orddd_timeslot_timestamp',
        'meta_compare' => 'BETWEEN',
        'meta_value' => array( $start_interval, $end_interval ),
      );
    }

    $orders = wc_get_orders( $query );

    foreach ( $orders as $order ) {
      $order_id = $order->get_id();
      $orddd_pickup_location_id = get_post_meta( $order_id, '_orddd_location', true );

      if ( $is_delivery && empty( $orddd_pickup_location_id ) ) {

        $order->update_status( 'completed' );
      } elseif ( !$is_delivery && !empty( $orddd_pickup_location_id ) ) {

        $order->update_status( 'completed' );
      }
    }
  }
}