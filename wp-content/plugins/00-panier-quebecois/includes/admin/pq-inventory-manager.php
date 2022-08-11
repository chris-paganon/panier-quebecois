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
		$args = array();
		ob_start();
		wc_pq_get_template( 'pq-inventory-manager-table.php', $args );
		return ob_get_clean();
	}
}