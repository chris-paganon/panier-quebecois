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

<table>
  <thead>
    <tr>
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
        <td><?php echo $product_arr['_short_name'];?></td>
        <td>
          <input type="text" value="<?php echo $product_arr['_pq_operation_stock'];?>" product-data="<?php echo $product_json; ?>">
          <?php wp_nonce_field('pq_inventory_changed', 'pq_inventory_nonce'); ?>
        </td>
        <td><?php echo $product_arr['_lot_unit'];?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>