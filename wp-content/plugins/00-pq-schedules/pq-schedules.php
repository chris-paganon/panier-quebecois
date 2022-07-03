<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )exit;

/*
Plugin Name: 00-PQ Schedules
Description: Registering PQ CRON jobs
Version: 2.0
Author: Christophe Paganon
*/

class PQ_order_schedules {
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

  /**
   * Order closing automation on a schedule
   *
   */
  public static function pq_get_close_orders_schedule() {
    $schedules_args = array(
      array(
        'date' => 'Monday 9:30pm',
        'is_delivery' => true,
      ),
      array(
        'date' => 'Monday 1:30pm',
        'is_delivery' => false,
      ),
      array(
        'date' => 'Monday 3:30pm',
        'is_delivery' => false,
      ),
      array(
        'date' => 'Wednesday 9:30pm',
        'is_delivery' => true,
      ),
      array(
        'date' => 'Wednesday 1:30pm',
        'is_delivery' => false,
      ),
      array(
        'date' => 'Wednesday 3:30pm',
        'is_delivery' => false,
      ),
      array(
        'date' => 'Friday 9:30pm',
        'is_delivery' => true,
      ),
      array(
        'date' => 'Friday 1:30pm',
        'is_delivery' => false,
      ),
      array(
        'date' => 'Friday 3:30pm',
        'is_delivery' => false,
      ),
    );

    return $schedules_args;
  }
}

/**
 * Orders completion
 * 
 */

/**
 * Activate CRON jobs for orders completion
 */
register_activation_hook( __FILE__, 'myfct_close_orders_schedule' );

function myfct_close_orders_schedule() {
  $schedules_args = PQ_order_schedules::pq_get_close_orders_schedule();

  $default_timezone = date_default_timezone_get();
  date_default_timezone_set( get_option( 'timezone_string' ) );

  foreach ( $schedules_args as $schedule_args ) {

    if ( !wp_next_scheduled( 'myhook_close_orders', $schedule_args ) ) {
      wp_schedule_event( strtotime( $schedule_args[ 'date' ] ), 'weekly', 'myhook_close_orders', $schedule_args );
    }
  }

  date_default_timezone_set( $default_timezone );
}

/**
 * Deactivate CRON jobs for orders completion
 */
register_deactivation_hook( __FILE__, 'myfct_clean_close_orders_schedule' );

function myfct_clean_close_orders_schedule() {
  $schedules_args = PQ_order_schedules::pq_get_close_orders_schedule();

  foreach ( $schedules_args as $schedule_args ) {
    wp_clear_scheduled_hook( 'myhook_close_orders', $schedule_args );
  }
}



/**
 * Clearing cache
 * 
 */

/**
 * Activate CRON jobs to clear the cache
 */
register_activation_hook( __FILE__, 'pq_clear_cache_schedule' );

function pq_clear_cache_schedule() {

  $default_timezone = date_default_timezone_get();
  date_default_timezone_set( get_option( 'timezone_string' ) );

  if ( !wp_next_scheduled( 'pqhook_clear_cache_new_day' ) ) {
    wp_schedule_event( strtotime( '12:02am' ), 'daily', 'pqhook_clear_cache_new_day' );
  }

  date_default_timezone_set( $default_timezone );
}


/**
 * Deactivate CRON jobs to clear the cache
 */
register_deactivation_hook( __FILE__, 'pq_clean_clear_cache_schedule' );

function pq_clean_clear_cache_schedule() {
  wp_clear_scheduled_hook( 'pqhook_clear_cache_new_day' );
}


/**
 * Clear the cache
 */
add_action( 'pqhook_clear_cache_new_day', 'pq_clear_cache' );

function pq_clear_cache() {
  if ( function_exists( 'rocket_clean_domain' ) ) {
    rocket_clean_domain();
  }
}


/**
 * Setting stock after delivery day
 * 
 */

/**
 * Activate CRON jobs to set the stock
 */
register_activation_hook( __FILE__, 'pq_set_stock_schedule' );

function pq_set_stock_schedule() {
  $delivery_days = PQ_delivery_days::$delivery_days;

  $default_timezone = date_default_timezone_get();
  date_default_timezone_set( get_option( 'timezone_string' ) );

  foreach ( $delivery_days as $delivery_day ) {
    if ( !wp_next_scheduled( 'pqhook_set_stock_' . $delivery_day ) ) {
      wp_schedule_event( strtotime( $delivery_day . ' 12:04am' ), 'weekly', 'pqhook_set_stock_' . $delivery_day );
    }
  }
  
  date_default_timezone_set( $default_timezone );
}


/**
 * Deactivate CRON jobs to set the stock
 */
register_deactivation_hook( __FILE__, 'pq_clean_set_stock_schedule' );

function pq_clean_set_stock_schedule() {
  $delivery_days = PQ_delivery_days::$delivery_days;
  foreach ( $delivery_days as $delivery_day ) {
    wp_clear_scheduled_hook( 'pqhook_set_stock_' . $delivery_day );
  }
}


/**
 * Set the stock after each delivery day is closed
 */
add_action ('init', 'pq_hook_function_to_pqhook_set_stock');
function pq_hook_function_to_pqhook_set_stock() {
  $delivery_days = PQ_delivery_days::$delivery_days;
  foreach ( $delivery_days as $delivery_day ) {
    add_action( 'pqhook_set_stock_' . $delivery_day, 'pq_set_stock' );
  }
}


/**
 * Add custom stock meta for wc_get_products query 
 */
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'pq_wc_get_products_custom_query_var', 10, 2 );
function pq_wc_get_products_custom_query_var( $query, $query_vars ) {

	if ( ! empty( $query_vars['_pq_set_auto_stock'] ) ) {
		$query['meta_query'][] = array(
			'key' => '_pq_set_auto_stock',
			'value' => esc_attr( $query_vars['_pq_set_auto_stock'] ),
		);
	}

	if ( ! empty( $query_vars['_pq_auto_stock_quantity'] ) ) {
		$query['meta_query'][] = array(
			'key' => '_pq_auto_stock_quantity',
			'value' => esc_attr( $query_vars['_pq_auto_stock_quantity'] ),
		);
	}

	return $query;
}


/**
 * Set auto stock on schedule
 */
function pq_set_stock() {
  $products = wc_get_products( array(
    'limit' => -1,
    '_pq_set_auto_stock' => 1,
  ));

  foreach ( $products as $product ) {
    $product_id = $product->get_id();
    $auto_stock_qty = get_post_meta( $product_id, '_pq_auto_stock_quantity', true );

    if ( ! empty($auto_stock_qty) && $auto_stock_qty > 0 ) {
      
      update_post_meta( $product_id, '_stock_status', 'instock' );
      update_post_meta( $product_id, '_manage_stock', 'yes' );
      update_post_meta( $product_id, '_stock', $auto_stock_qty );
      wp_set_post_terms( $product_id, '', 'product_visibility', false );
      wc_delete_product_transients( $product_id );

      if ( function_exists( 'rocket_clean_domain' ) ) {
        rocket_clean_domain();
      }
    }
  }
}


/**
 * Cleaning inactive products
 * 
 */

/**
 * Activate CRON jobs to clear the cache
 */
register_activation_hook( __FILE__, 'pq_clean_inactive_schedule' );

function pq_clean_inactive_schedule() {

  $default_timezone = date_default_timezone_get();
  date_default_timezone_set( get_option( 'timezone_string' ) );

  if ( !wp_next_scheduled( 'pqhook_clean_inactive' ) ) {
    wp_schedule_event( strtotime( '3am' ), 'daily', 'pqhook_clean_inactive' );
  }

  date_default_timezone_set( $default_timezone );
}


/**
 * Deactivate CRON jobs to clear the cache
 */
register_deactivation_hook( __FILE__, 'pq_clean_clean_inactive_schedule' );

function pq_clean_clean_inactive_schedule() {
  wp_clear_scheduled_hook( 'pqhook_clean_inactive' );
}


/**
 * Add custom inactive meta query for wc_get_products query
 */
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'pq_wc_get_products_pq_inactive_query_var', 10, 2 );
function pq_wc_get_products_pq_inactive_query_var( $query, $query_vars ) {

	if ( ! empty($query_vars['_pq_inactive']) && esc_attr($query_vars['_pq_inactive']) == 'NOT EXISTS' ) {
		$query['meta_query'][] = array(
      'relation' => 'OR',
      array(
        'key' => '_pq_inactive',
        'compare' => '=',
        'value' => 'not_a_value'),
      array(
        'key' => '_pq_inactive',
        'compare' => 'NOT EXISTS',
        'value' => 'useless'),
      );
	} elseif ( ! empty($query_vars['_pq_inactive']) ) {
    $query['meta_query'][] = array(
      'key' => '_pq_inactive',
      'value' => esc_attr($query_vars['_pq_inactive']),
    );
  }

	return $query;
}


/**
 * Clean inactive products
 */
add_action('pqhook_clean_inactive', 'pq_clean_inactive_products');

function pq_clean_inactive_products() {
  $products = wc_get_products( array(
    'limit' => -1,
    'status' => 'publish',
    '_pq_inactive' => 'NOT EXISTS',
  ));

  foreach ( $products as $product ) {
    $product_id = $product->get_id();
    update_post_meta($product_id, '_pq_inactive', 0);
  }

  $products = wc_get_products( array(
    'limit' => -1,
    'status' => 'publish',
    'stock_status' => 'instock',
    '_pq_inactive' => '1',
  ));

  foreach ( $products as $product ) {
    $product_id = $product->get_id();
    update_post_meta($product_id, '_pq_inactive', 0);
  }
}