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
}

/**
 * Adding fields to the settings page
 */
add_action( 'admin_init', 'pq_settings_fields' );

function pq_settings_fields() {
    add_settings_field( 'pq_featured_products_title', 'Titre', 'pq_featured_products_title_field', 'pq_settings_sections', 'pq_featured_products_title_section' );
    register_setting( 'pq-settings-page', 'pq_featured_products_title' );
}

/**
 * Adding the title field
 */
function pq_featured_products_title_field() {
    echo '<input name="pq_featured_products_title" id="pq_featured_products_title" type="text" value="' . get_option( 'pq_featured_products_title' ) . '" />';
}