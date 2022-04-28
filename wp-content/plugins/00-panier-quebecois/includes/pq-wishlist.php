<?php
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Add wishlist endpoint and account menu item
 */
add_action( 'init', 'pq_add_wishlist_endpoint' );
function pq_add_wishlist_endpoint() {
    add_rewrite_endpoint( 'favoris', EP_ROOT | EP_PAGES );
}

add_filter( 'query_vars', 'pq_wishlist_query_vars', 0 );
function pq_wishlist_query_vars( $vars ) {
    $vars[] = 'favoris';
    return $vars;
}

add_filter( 'woocommerce_account_menu_items', 'pq_add_wishlist_my_account' );
function pq_add_wishlist_my_account( $items ) {
    //Remove the default wishlist menu item
    unset($items['my-wishlist']);

    $my_item = array( 'favoris' => __( 'Mes favoris' ) );
    $new_items = array_slice( $items, 0, 1, true ) + $my_item + array_slice( $items, 1, count( $items ), true );

    return $new_items;
}


/**
 * Display the wishlist in my account
 */
add_action( 'woocommerce_account_favoris_endpoint', 'pq_wishlist_content' );

function pq_wishlist_content() {

    $wishlist_content = '';
	$wl_items = mywishlist_get_current_wishlist_items();

    //Load items on first page load (before cookies are accessible)
    if ( empty($wl_items) && false !== mywishlist_get_user_email() && ! isset($_COOKIE['mywishlist_email']) ) {
        $current_email = mywishlist_get_user_email();
        $_COOKIE['mywishlist_email'] = $current_email;
    	$wl_items = mywishlist_get_current_wishlist_items();
    }

	if (count($wl_items) > 0 ){

        $products_query_arg = array(
            'post_type' => array('product', 'product_variation'),
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'posts_per_page' => -1,
            'post__in' => $wl_items,
            'fields' => 'ids',
        );
    
        $products_query = new WP_Query( $products_query_arg );
    
        if ( $products_query->have_posts() ) { 
            ?>
            <div class="woocommerce pq-wishlist-products columns-4">
                <?php woocommerce_product_loop_start(); ?>
                <?php while ( $products_query->have_posts() ) : $products_query->the_post(); ?>
                    <?php wc_get_template_part( 'content', 'product' ); ?>
                <?php endwhile; // end of the loop. ?>
                <?php woocommerce_product_loop_end(); ?>
            </div>
            <?php
        }
    
        wc_reset_loop();
        wp_reset_postdata();

    } else {
		echo '<p>' . __( 'Vous n\'avez pas de favoris. Cliquez sur le coeur en haut à gauche des photos de produits pour les ajouter à vos favoris.' ) . '</p>';
    }
}