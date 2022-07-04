<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Display all products categories in navbar menus
 */
add_filter( 'wp_get_nav_menu_items', 'pq_display_categories_in_navbar', 10, 3 );

function pq_display_categories_in_navbar( $items, $menu, $args ){
    error_log('items:');
    error_log(print_r($items, true));
    error_log('menu:');
    error_log(print_r($menu, true));
    error_log('args:');
    error_log(print_r($args, true));

    $menu_id = $menu->term_id;

    if ( $menu_id == 227 || $menu_id == 515 ) {

        $ctr = ($items[sizeof($items)-1]->ID)+1;
        
        foreach ($items as $index => $i) {
            if ("product_cat" !== $i->object) {
                continue;
            }
            $menu_parent = $i->ID;
            $terms = get_terms( array('taxonomy' => 'product_cat', 'parent'  => $i->object_id ) );
            foreach ($terms as $term) {
                $new_item = pq_custom_nav_menu_item( $term->name, get_term_link($term), $ctr, $menu_parent );
                $items[] = $new_item;
                $new_id = $new_item->ID;
                $ctr++;
                $terms_child = get_terms( array('taxonomy' => 'product_cat', 'parent'  => $term->term_id ) );
                if(!empty($terms_child)) {
                    foreach ($terms_child as $term_child) {
                        $new_child = pq_custom_nav_menu_item( $term_child->name, get_term_link($term_child), $ctr, $new_id );
                        $items[] = $new_child;
                        $ctr++;
                    }
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