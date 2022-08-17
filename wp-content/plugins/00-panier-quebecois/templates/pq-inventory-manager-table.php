<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Template vars.
 *
 * @var array $products
 */

?>

<table class="pq-inventory-manager-table">
  <thead>
    <tr class="inventory-title-row">
      <th>Produit</th>
      <th>Inventaire</th>
      <th>Unité</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ( $products as $key => $product_arr ) : 
      $product_json = htmlentities(json_encode($product_arr));
      ?>
      <tr class="inventory-product-row">
        <td class="inventory_short_name"><?php echo $product_arr['_short_name'];?></td>
        <td class="inventory_stock">
          <input class="inventory_stock_input" type="text" value="<?php echo $product_arr['_pq_operation_stock'];?>" product-data="<?php echo $product_json; ?>">
          <?php wp_nonce_field('pq_inventory_changed', 'pq_inventory_nonce'); ?>
        </td>
        <td class="inventory_unit"><?php echo $product_arr['_lot_unit'];?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>