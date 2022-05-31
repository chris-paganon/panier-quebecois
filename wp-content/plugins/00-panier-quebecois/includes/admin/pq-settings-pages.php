<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


/**
*
* Add the settings pages
*
*/

/**
 * Add the pages
 */
add_action( 'admin_menu', 'pq_add_settings_pages' );

function pq_add_settings_pages() {
	add_submenu_page('panier-quebecois', 'PQ Settings', 'Réglages', 'manage_options', 'pq-settings-page', 'pq_settings_page_content');
}

/**
 * Main setup of the settings page
 */
function pq_settings_page_content() {
    ?>
    <div class="wrap">
        <h2>Réglages de Panier Québécois</h2>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'pq-settings-page' );
                do_settings_sections( 'pq_settings_sections' );
                submit_button();
            ?>
        </form>
    </div> 
    <?php
}

/**
 * Adding sections to the settings page
 */
add_action( 'admin_init', 'pq_setup_settings_sections' );

function pq_setup_settings_sections() {
    add_settings_section( 'pq_featured_products_title_section', 'Titre des produits en vedette', '', 'pq_settings_sections' );
    add_settings_section( 'pq_featured_marchand_and_producer_list_section', 'Choix du marchand ou producteur de la semaine', '', 'pq_settings_sections' );
}

/**
 * Adding fields to the settings page
 */
add_action( 'admin_init', 'pq_settings_fields' );

function pq_settings_fields() {
    add_settings_field( 'pq_featured_products_title', 'Titre', 'pq_featured_products_title_field', 'pq_settings_sections', 'pq_featured_products_title_section' );

    add_settings_field( 'pq_featured_marchand_and_producer', 'Choix', 'pq_featured_marchand_and_producer_list_field', 'pq_settings_sections', 'pq_featured_marchand_and_producer_list_section' );

    register_setting( 'pq-settings-page', 'pq_featured_products_title');
    register_setting( 'pq-settings-page', 'pq_featured_marchand_and_producer' );
    register_setting( 'pq-settings-page', 'pq_featured_test' );
}

/**
 * Adding the title field
 */
function pq_featured_products_title_field() {
    echo '<input name="pq_featured_products_title" id="pq_featured_products_title" type="text" value="' . get_option( 'pq_featured_products_title' ) . '" />';
}

function pq_featured_marchand_and_producer_list_field() {

    $selected = get_option( 'pq_featured_marchand_and_producer' );
    // print_r($selected);

    $terms = get_terms( array(
        'taxonomy' => ['product_tag', 'pq_producer'],
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ) );
    if ( $terms ) : 

        echo '<select name="pq_featured_marchand_and_producer" id="pq_featured_marchand_and_producer" class="postform"">';
        echo '<option value="0">Please select Marchand or Producer</option>';
        foreach( $terms as $term ) : 
            if($term->taxonomy == 'product_tag'){
                $tax = 'Marchand';
            }
            else{
                $tax = 'Producer';
            }
            $is_selected = '';
            if($selected == $term->term_id){ 
                $is_selected = 'selected'; 
            }

            echo '<option value="'.$term->term_id.'" id="term-id-'.$term->term_id.'" '.$is_selected.'>'.$term->name.' ('.$tax.')</option>';
        endforeach;
        echo '</select>';
    endif;
    //wp_dropdown_categories( array( 'taxonomy' => ['product_tag', 'pq_producer'], 'hide_empty' => 0, 'name' => "pq_featured_marchand_and_producer", 'selected' => $term_obj[0]->term_id, 'orderby' => 'name', 'hierarchical' => 0, 'show_option_none' => 'Please select' ) );

}