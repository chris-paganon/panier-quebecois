<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

?>

<div class="wrap">
  <h2>Exporter les produits</h2>
  <form action="admin.php?page=pq-export-products" method="post">
    <?php wp_nonce_field('export_products_clicked'); ?>
    <input type="hidden" value="true" name="export_products" />
    <?php submit_button('Exporter les produits'); ?>
  </form>
</div>
