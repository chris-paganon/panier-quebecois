<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Add the export pages
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
  }

  wc_pq_get_template( 'admin/pq-export-purchasing-content.php', '' );
}


/* ---- Add content to the orders export page ----- */
function myfct_orders_export_content() {
  if ( !current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have sufficient pilchards to access this page.' ) );
  }

  if ( isset( $_POST[ 'export_orders' ] ) && check_admin_referer( 'export_orders_clicked' ) ) {
    $delivery_date_raw = $_POST[ 'my_delivery_date' ];
    $error = myfct_validate_delivery_date( $delivery_date_raw );

    if ( !empty( $error ) ) {
      echo '<div class="notice notice-error"><p> ' . $error . '</p></div>';
    } else {
      myfct_orders_export( $delivery_date_raw );
    }
  }

  wc_pq_get_template( 'admin/pq-export-orders-content.php', '' );
}

/**
 * Add content to the products export page
 */
function pq_products_export_content() {
  if ( !current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have sufficient pilchards to access this page.' ) );
  }
  
  if ( isset( $_POST[ 'export_products' ] ) && check_admin_referer( 'export_products_clicked' ) ) {
    pq_export_products();
  }

  wc_pq_get_template( 'admin/pq-export-products-content.php', '' );
}
