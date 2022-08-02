<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Display all products categories in navbar menus
 */
add_filter( 'wp_get_nav_menu_items', 'pq_display_categories_in_navbar', 10, 3 );

function pq_display_categories_in_navbar( $items, $menu, $args ){
    
    $menu_id = $menu->term_id;
    
    if ( $menu_id == 227 || $menu_id == 515 ) {

        if ( $menu_id == 515 ) {
            $parent_menu_item_id = 38102; //Le marché
        } elseif ( $menu_id == 227 ) {
            $parent_menu_item_id = 10820; //Le marché 
        }

        $ctr = ($items[sizeof($items)-1]->ID)+1;
            
        $args = array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => 153,
            'exclude' => array( 237 ),
        );
        $parent_product_cats = get_terms( $args );

        foreach ( $parent_product_cats as $parent_product_cat ) {

            $new_item = pq_custom_nav_menu_item( $parent_product_cat->name, get_term_link($parent_product_cat), $ctr, $parent_menu_item_id );
            $items[] = $new_item;
            $new_id = $new_item->ID;
            $ctr++;

            $child_args = array(
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
                'parent' => $parent_product_cat->term_id,
            );
            $child_product_cats = get_terms( $child_args );

            if(!empty($child_product_cats)) {
                foreach ($child_product_cats as $child_product_cat) {

                    $new_child = pq_custom_nav_menu_item( $child_product_cat->name, get_term_link($child_product_cat), $ctr, $new_id );
                    $items[] = $new_child;
                    $ctr++;
                }
            }
        }
    }

    return $items;
}


function pq_custom_nav_menu_item( $title, $url, $order, $parent = 0 ){
    $item = new stdClass();

    $item->ID = 1000000 + $order + $parent;
    $item->db_id = $item->ID;
    $item->title = $title;
    $item->url = $url;
    $item->menu_order = $order;
    $item->menu_item_parent = $parent;
    $item->type = '';
    $item->object = '';
    $item->object_id = '';
    $item->classes = array();
    $item->target = '';
    $item->attr_title = '';
    $item->description = '';
    $item->xfn = '';
    $item->status = '';

    return $item;
}