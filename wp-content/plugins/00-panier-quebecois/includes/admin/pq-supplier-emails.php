<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Send emails to sellers with the day's order details
 */
function pq_send_seller_emails() {
  
  $headers = array(
    'Content-Type: text/html; charset=UTF-8', 
    'Reply-To: Arthur Capaldi <acapaldi@panierquebecois.ca>',
  );
  $fmt_fr = new IntlDateFormatter( 'fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, NULL, IntlDateFormatter::GREGORIAN, 'EEEE dd MMMM y' );
  $full_date = $fmt_fr->format( time() );

  $suppliers = get_terms( array(
    'taxonomy' => 'product_tag',
    'hide_empty' => false,
  ));

  foreach ($suppliers as $supplier) {
    $supplier_email = get_term_meta ( $supplier->term_id, 'pq_seller_email', true );
    if ( ! empty($supplier_email) ) {

      $orders = pq_get_relevant_orders_today();
      $products = pq_get_product_rows( $orders, $supplier );
      $products = pq_add_quantity_to_buy_to_products($products);

      $short_name_column = array_column($products, '_short_name');
      $packing_priority_column = array_column($products, '_packing_priority');
      array_multisort($packing_priority_column, SORT_ASC, SORT_STRING, $short_name_column, $products);

      if ( ! empty($products) ) {
        $supplier_needs_units = get_term_meta ( $supplier->term_id, 'pq_seller_needs_units', true );
        $supplier_order_html = pq_get_supplier_email_html( $products, $full_date, $supplier_needs_units );
        wp_mail( $supplier_email, 'Commande du ' . $full_date, $supplier_order_html, $headers);
      }
    }
  }
}


/**
 * Get supplier email html
 */
function pq_get_supplier_email_html( $products, $full_date, $supplier_needs_units = false ) {

  $cell_style = 'border:solid 1px; padding: 5px 10px; text-align: center; font-size: 15px;';

  ob_start();

  ?>

  <h1 style="font-size: 18px;">Bonjour,</h1>
  <h2 style="font-size: 15px;">Commande pour <?php echo $full_date; ?>:</h2>

  <table style="border:solid 1px; border-collapse: collapse;">
    <tr>
      <th style="<?php echo $cell_style; ?>">Produit</th>
      <th style="<?php echo $cell_style; ?>">Quantité</th>
      <?php if ($supplier_needs_units) : ?>
        <th style="<?php echo $cell_style; ?>">Unité</th>
      <?php endif ?>
    </tr>

  <?php

  foreach ( $products as $product_arr ) {
    $quantity = ! array_key_exists('crates_to_order', $product_arr) ? $product_arr['quantity_to_buy'] : $product_arr['crates_to_order'];
    $short_name = $product_arr['_short_name'];
    $weight_with_unit = $product_arr['weight'];
    if ( $quantity > 0 ) {
      ?>
      <tr>
        <td style="<?php echo $cell_style; ?>"><?php echo $short_name; ?></td>
        <td style="<?php echo $cell_style; ?>"><?php echo $quantity; ?></td>
        <?php if ($supplier_needs_units) : ?>
          <td style="<?php echo $cell_style; ?>"><?php echo $weight_with_unit; ?></td>
        <?php endif ?>
      </tr>
      <?php
    }
  }
  ?>
  </table>
  <h2 style="font-size: 15px;">Si vous avez des problèmes ou des questions contactez Thomas Lemoine (<a href="tel:5142317590">514 231-7590</a> ou <a href="mailto:tlemoine@panierquebecois.ca">tlemoine@panierquebecois.ca</a>) ou Arthur Capaldi (<a href="tel:5149982202">514 998-2202</a> ou <a href="mailto:acapaldi@panierquebecois.ca">acapaldi@panierquebecois.ca</a>) dès que possible.</h2>
  <h2 style="font-size: 15px;">Merci et bonne journée,</h2>
  <?php

  return ob_get_clean();
}