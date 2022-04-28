<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}


/**
 * Return total number of processing and completed orders
 */
add_shortcode( 'pq_count_orders', 'pq_count_orders_fct' );

function pq_count_orders_fct() {

    $completed_orders_count = wc_orders_count('completed');
    $processing_orders_count = wc_orders_count('processing');
    $orders_count = $completed_orders_count + $processing_orders_count;

    return $orders_count;
}


/**
 * Return total number of suppliers with products in stock
 */
add_shortcode( 'pq_count_suppliers', 'pq_count_suppliers_fct' );

function pq_count_suppliers_fct() {

    //Count sellers
    $sellers = get_terms( array(
        'taxonomy' => 'product_tag',
        'hide_empty' => 1,
    ));

    $suppliers_count = 0;
    foreach ( $sellers as $seller ) {
        if ( $seller->count > 0 ) {
            $suppliers_count++;
        }
    }

    //Count producers (couldn't get $producer->count to work properly)
    $producers = get_terms( array(
        'taxonomy' => 'pq_producer',
        'hide_empty' => 1,
    ));

    foreach ( $producers as $producer ) {

        $tax_query = array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'pq_producer',
                'field' => 'slug',
                'terms' => $producer->slug,
            ),
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'term_taxonomy_id',
                'terms'    => array(7, 9),
                'operator' => 'NOT IN',
            ),
        );

        $products_query_arg = array(
            'tax_query' => $tax_query,
            'meta_query'    => array( array(
                'key'     => '_stock_status',
                'value'   => 'instock',
            )),
            'post_type' => 'product',
        );

        $products_query = new WP_Query( $products_query_arg );

        if ( $products_query->have_posts() ) {
            $suppliers_count++;
        }
    }

    return $suppliers_count;
}