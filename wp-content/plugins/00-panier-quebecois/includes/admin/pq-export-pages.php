<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/*
 *
 *
 * Add the export pages
 *
 *
 */

/* ------ Add the pages ------ */
add_action( 'admin_menu', 'myfct_purchasing_export_menu' );

function myfct_purchasing_export_menu() {
  add_menu_page( 'Panier Québécois', 'Panier Québécois', 'manage_options', 'panier-quebecois', 'myfct_purchasing_export_content', '', 50 );
  add_submenu_page( 'panier-quebecois', 'PQ Export Purchasing', 'Exporter les achats', 'manage_options', 'pq-export-purchasing', 'myfct_purchasing_export_content' );
  add_submenu_page( 'panier-quebecois', 'PQ Export Products', 'Exporter les produits', 'manage_options', 'pq-export-products', 'pq_products_export_content' );
  add_submenu_page( 'panier-quebecois', 'PQ Export Orders', 'Exporter les commandes', 'manage_options', 'pq-export-orders', 'myfct_orders_export_content' );
}

/* ---- Add content to the purchasing export page ----- */
function myfct_purchasing_export_content() {
  if ( !current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have sufficient pilchards to access this page.' ) );
  }

  $timezone = new DateTimeZone( get_option( 'timezone_string' ) );
  $default_date_obj = new DateTime( 'tomorrow', $timezone );
  $default_date = $default_date_obj->format( 'Y-m-d' ); ?>
  <div class="wrap">
    <h2>Exporter les achats</h2><?php

    if ( ( isset( $_POST[ 'export_purchasing' ] ) || isset( $_POST[ 'export_products_to_weight' ] ) ) && check_admin_referer( 'export_purchasing_clicked' ) ) {

      $delivery_date_raw = $_POST[ 'my_delivery_date' ];
      $error = myfct_validate_delivery_date( $delivery_date_raw );

      if ( !empty( $error ) ) {
        echo '<div class="notice notice-error"><p> ' . $error . '</p></div>';
      } else {
        if ( isset( $_POST[ 'export_purchasing' ] ) ) {

          if ( ! empty( $_POST[ 'pq_last_order' ] ) ) {
            $import_after_order = $_POST[ 'pq_last_order' ];
            $error = pq_validate_order_number($import_after_order);
            if ( !empty( $error ) ) {
              echo '<div class="notice notice-error"><p> ' . $error . '</p></div>';
            } else {
              myfct_purchasing_export( $delivery_date_raw, $import_after_order );
            }
          } else {
            myfct_purchasing_export( $delivery_date_raw );
          }
        } elseif ( isset( $_POST[ 'export_products_to_weight' ] ) ) {
          myfct_products_to_weight_export( $delivery_date_raw );
        }
      }
    } ?>
    <form action="admin.php?page=pq-export-purchasing" method="post">
      <?php wp_nonce_field('export_purchasing_clicked'); ?>
      
      <label>Date de livraison
        <input type="text" name="my_delivery_date" placeholder="AAAA-MM-JJ" value="<?php echo $default_date ?>" />
      </label>
      </br></br>
      <label>Exporter les achats après cette commande (optionnel):
        <input type="text" name="pq_last_order"/>
      </label>
      <p>Pour les achats uniquement. Exporte les achats après le numéro de commande indiqué. N'inclus pas la commande indiquée. Format: 40123</p>

      <input type="hidden" value="true" name="export_purchasing_form" />
      <?php submit_button('Exporter les achats', 'primary', 'export_purchasing'); ?>
      <?php submit_button('Exporter les produits à peser', 'large', 'export_products_to_weight'); ?>
    </form><?php
}


/* ---- Add content to the orders export page ----- */
function myfct_orders_export_content() {
  if ( !current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have sufficient pilchards to access this page.' ) );
  }

  $timezone = new DateTimeZone( get_option( 'timezone_string' ) );
  $default_date_obj = new DateTime( 'tomorrow', $timezone );
  $default_date = $default_date_obj->format( 'Y-m-d' ); ?>

  <div class="wrap">
    <h2>Exporter les commandes</h2><?php

    if ( isset( $_POST[ 'export_orders' ] ) && check_admin_referer( 'export_orders_clicked' ) ) {
      $delivery_date_raw = $_POST[ 'my_delivery_date' ];
      $error = myfct_validate_delivery_date( $delivery_date_raw );

      if ( !empty( $error ) ) {
        echo '<div class="notice notice-error"><p> ' . $error . '</p></div>';
      } else {
        myfct_orders_export( $delivery_date_raw );
      }
    }

    ?>
    <form action="admin.php?page=pq-export-orders" method="post">
      <?php wp_nonce_field('export_orders_clicked'); ?>
      <input type="text" name="my_delivery_date" placeholder="AAAA-MM-JJ" value="<?php echo $default_date ?>" />
      <input type="hidden" value="true" name="export_orders" />
      <?php submit_button('Exporter les commandes'); ?>
    </form><?php
}

/**
 * Add content to the products export page
 */
function pq_products_export_content() {
  if ( !current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have sufficient pilchards to access this page.' ) );
  } ?>
  <div class="wrap">
    <h2>Exporter les produits</h2><?php

    if ( isset( $_POST[ 'export_products' ] ) && check_admin_referer( 'export_products_clicked' ) ) {
      pq_export_products();
    } ?>
    <form action="admin.php?page=pq-export-products" method="post">
      <?php wp_nonce_field('export_products_clicked'); ?>
      <input type="hidden" value="true" name="export_products" />
      <?php submit_button('Exporter les produits'); ?>
    </form><?php
}
