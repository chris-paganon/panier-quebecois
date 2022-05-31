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
 * Return total carbon emissions saved
 */
add_shortcode( 'pq_carbon_emissions', 'pq_carbon_emissions_fct' );

function pq_carbon_emissions_fct() {

    $orders_count = pq_count_orders_fct();
    $carbon_saved = round( ($orders_count * 0.7 / 1000), 2 );

    return $carbon_saved;
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


/**
 * Return seller of the week
 */
add_shortcode( 'pq_seller_week', 'pq_seller_week_fct' );

function pq_seller_week_fct() {
    $sellerID = get_option( 'pq_featured_marchand_and_producer' );
    $seller = get_term( $sellerID );
    $seller_link = get_term_link( $seller );
    $image_id = get_term_meta ( $sellerID, 'image_id', true );

    $tax_query = array(
        array(
            'taxonomy' => 'pq_collections',
            'field' => 'slug',
            'terms' => 'marchand-de-la-semaine',
        ),
    );

    $products_query_arg = array(
        'tax_query' => $tax_query,
        'meta_query'    => array( array(
            'key'     => '_stock_status',
            'value'   => 'instock',
        )),
        'post_type' => 'product',
        'posts_per_page' => '3',
    );

    $products_query = new WP_Query( $products_query_arg );

    echo '<div class="sellerweek_block">';
    echo '<div class="sellerweek_infos">';
    if( $image_id ) { 
        echo wp_get_attachment_image ( $image_id, 'thumbnail' );
    }
    echo '<p class="sellerweek_title">'.$seller->name.'</p>';
    echo '<a href="' . esc_url( $seller_link ) . '">' . esc_attr( 'Magaziner', 'woocommerce' ) . '</a>';
    echo '</div>';
    echo '<div class="sellerweek_products woocommerce columns-3">';
    if ( $products_query->have_posts() ) {
        woocommerce_product_loop_start();
        while ( $products_query->have_posts() ) {
            $products_query->the_post();
            wc_get_template_part( 'content', 'product' );
        }
        woocommerce_product_loop_end();
    }
    wp_reset_postdata();
    echo '</div>';
    echo '</div>';
}
