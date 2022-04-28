<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_shortcode( 'pq_products_slider', 'pq_products_slider_fct');

function pq_products_slider_fct( $atts ) {

    extract(shortcode_atts(array(
        'type' => 'no_type',
        'key' => 'no_key',
        'value' => 'no_value',
        ), $atts));

    if ( $type == 'no_type' || $key == 'no_key' || $value == 'no_value' ) return;

    
    $tax_query = array(
		'relation' => 'AND',
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => array(7, 9),
			'operator' => 'NOT IN',
		)
	);

    $meta_query = array( 
        'relation' => 'AND',
        array(
            'key'   => '_stock_status',
            'value' => 'instock',
        )
    );

    switch ($type) {
        case 'taxonomy':
            $tax_query[] = array(
                'taxonomy' => $key,
                'field' => 'id',
                'terms' => $value,
            );
            break;
        case 'meta':
            $meta_query[] = array(
                'key'   => $key,
                'value' => $value,
            );
            break;
        default:
            return;
        }

    $pq_wp_query_args = array(
		'posts_per_page' => 18,
		'tax_query'      => $tax_query,
		'meta_query'     => $meta_query,
		'post_type'      => 'product',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	);

    $products_query = new WP_Query( $pq_wp_query_args );

    ?>
    <div class='pq-products-slider panier-perso'>
    <?php if ( $products_query->have_posts() ) :?>
        <div class="woocommerce columns-6">
            <?php woocommerce_product_loop_start(); ?>
            <?php while ( $products_query->have_posts() ) : $products_query->the_post(); ?>
                <?php wc_get_template_part( 'content', 'product' ); ?>
            <?php endwhile; // end of the loop. ?>
            <?php woocommerce_product_loop_end(); ?>
        </div>
    </div>
    <?php endif;

    wc_reset_loop();
    wp_reset_postdata();
}