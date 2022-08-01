<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Send emails to sellers with the day's order details
 */
function pq_send_seller_emails() {
  
  $headers = array('Content-Type: text/html; charset=UTF-8');
  $fmt_fr = new IntlDateFormatter( 'fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, NULL, IntlDateFormatter::GREGORIAN, 'EEEE dd MMMM y' );
  $full_date = $fmt_fr->format( time() );

  $suppliers = get_terms( array(
    'taxonomy' => 'product_tag',
    'hide_empty' => false,
  ));

  foreach ($suppliers as $supplier) {
    $supplier_email = get_term_meta ( $supplier->term_id, 'pq_seller_email', true );
    if ( ! empty($supplier_email) ) {

      $delivery_date_raw = pq_get_current_delivery_date_for_supplier();
      $orders = myfct_get_relevant_orders( $delivery_date_raw, "" );

      $products = pq_get_products_array_for_supplier( $supplier, $orders );

      if ( ! empty($products) ) {
        $supplier_order_html = pq_get_supplier_email_html( $products, $full_date );
        wp_mail( $supplier_email, 'Commande du ' . $full_date, $supplier_order_html, $headers);
      }
    }
  }
}


/**
 * Get supplier email html
 */
function pq_get_supplier_email_html( $products, $full_date ) {

  $cell_style = 'border:solid 1px; padding: 10px; text-align: center; font-size: 17px;';

  ob_start();

  ?>

  <h1 style="font-size: 20px;">Commande du <?php echo $full_date; ?>:</h1>

  <table style="border:solid 1px; border-collapse: collapse;">
      <tr>
          <th style="<?php echo $cell_style; ?>">Produit</th>
          <th style="<?php echo $cell_style; ?>">Quantité</th>
      </tr>

  <?php

  foreach ( $products as $product_id => $quantity ) {
      $product = wc_get_product( $product_id );
      $short_name = get_post_meta( $product_id, '_short_name', true);
      ?>
      <tr>
          <td style="<?php echo $cell_style; ?>"><?php echo $short_name; ?></td>
          <td style="<?php echo $cell_style; ?>"><?php echo $quantity; ?></td>
      </tr>
      <?php
  }
  ?>
  </table>
  <?php

  return ob_get_clean();
}