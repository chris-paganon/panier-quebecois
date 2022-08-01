<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}


/**
 * Show order of the day for suppliers
 */
add_shortcode( 'pq_supplier_order', 'pq_supplier_order_fct' );

function pq_supplier_order_fct() {

    if ( current_user_can( 'pq_see_orders' ) ) {
        $delivery_date_raw = pq_get_current_delivery_date_for_supplier();
        $orders = myfct_get_relevant_orders( $delivery_date_raw, "" );

        $supplier_id = 195;
        $supplier = get_term_by( 'id', $supplier_id, 'product_tag' );

        $products = pq_get_products_array_for_supplier( $supplier, $orders );
        $supplier_order_html = pq_get_supplier_order_table( $products );

        return $supplier_order_html;
    }
}


/**
 * Get supplier order table
 */
function pq_get_supplier_order_table( $products ) {

    ob_start();

    ?>
    <table>
        <tr>
            <th>Produit</th>
            <th>Quantité</th>
            <th>Poids</th>
        </tr>

    <?php

    foreach ( $products as $product_id => $quantity ) {
        $product = wc_get_product( $product_id );
        $short_name = get_post_meta( $product_id, '_short_name', true);
        $weight = get_post_meta( $product_id, '_pq_weight', true );
        $unit = get_post_meta( $product_id, '_lot_unit', true );
        $weight_with_unit = $weight . $unit;
        ?>
        <tr>
            <td><?php echo $short_name; ?></td>
            <td><?php echo $quantity; ?></td>
            <td><?php echo $weight_with_unit; ?></td>
        </tr>
        <?php
    }
    ?>
    </table>
    <?php

    return ob_get_clean();
}


/**
 * Get array of products total quantity for a supplier
 */
function pq_get_products_array_for_supplier( $supplier, $orders ) {
    $products = array();
    foreach ( $orders as $order ) {
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = wc_get_product( $item->get_product_id() );
    
            if ( pq_is_relevant_product_for_supplier( $product, $supplier ) ) {
                if ( $item->get_variation_id() !== 0 ) {
                    $new_id = $item->get_variation_id();
                } else {
                    $new_id = $product->get_id();
                }
            
                $new_quantity_before_refund = $item->get_quantity();
                $quantity_refunded = $order->get_qty_refunded_for_item( $item_id );
                $new_quantity = $new_quantity_before_refund + $quantity_refunded;
        
                $is_new_id = true;
        
                foreach ( $products as $id => $quantity ) {
                    if ( $new_id == $id ) {
                        $products[ $id ] += $new_quantity;
                        $is_new_id = false;
                    }
                }
        
                if ( $is_new_id ) {
                    $new_product = array( $new_id => $new_quantity );
                    $products += $new_product;
                }
            }
        }
    }

    return $products;
}


/**
 * Get products only for the relevant supplier
 */
function pq_is_relevant_product_for_supplier( $product, $supplier_to_count ) {
    $product_id = $product->get_id();
    $product_suppliers = get_terms(array( 
        'taxonomy' => array('product_tag', 'pq_producer'),
        'object_ids' => $product_id,
    ));

    $is_product_to_count = false;
  
    foreach ( $product_suppliers as $product_supplier ) {
        if ( $product_supplier->term_id == $supplier_to_count->term_id ) {
            $is_product_to_count = true;
        }
    }

    return $is_product_to_count;
}


/**
 * Get the current delivery date
 */
function pq_get_current_delivery_date_for_supplier() {
    $delivery_days = PQ_delivery_days::$delivery_days;

    $wordpress_timezone = new DateTimeZone( get_option( 'timezone_string' ) );
    $now = new DateTime( 'today', $wordpress_timezone );

    $current_delivery_day = "";
    foreach ( $delivery_days as $delivery_day ) {
        $delivery_date = new DateTime ( $delivery_day, $wordpress_timezone );
        if ( $delivery_date == $now ) {
            $current_delivery_day = $delivery_date->format('Y-m-d');
        }
    }

    return $current_delivery_day;
}