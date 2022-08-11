<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Add shortcode to display inventory manager
 */
add_shortcode( 'pq_inventory_manager', 'pq_inventory_manager_fct' );

function pq_inventory_manager_fct() {

  if ( current_user_can( 'pq_see_operations' ) ) {
    echo 'SUCCESS';
  } else {
    echo 'FAILURE';
  }
}