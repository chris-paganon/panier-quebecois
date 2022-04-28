<?php
/**
 * Product loop sale flash
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/sale-flash.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

global $post, $product;
$product_id = $product->get_id();
$is_new = get_post_meta( $product_id, '_pq_new', true );
$is_last_chance = get_post_meta( $product_id, '_pq_last_chance', true );

if ( $product->is_on_sale() || ! empty($is_new) || ! empty($is_last_chance) ) :
  if ( empty( $is_new ) && empty( $is_last_chance ) ) {
    echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale', 'woocommerce' ) . '</span>', $post, $product );
  } elseif ( !empty( $is_new ) ) {
    echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale pq_new">' . esc_html__( 'New', 'woocommerce' ) . '</span>', $post, $product );;
  } elseif ( !empty( $is_last_chance ) ) {
    echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale pq_last_chance">' . esc_html__( 'Last chance', 'woocommerce' ) . '</span>', $post, $product );;
  }
endif;

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */