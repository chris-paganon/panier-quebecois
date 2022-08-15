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

<h1>Inventaire des opérations</h1>

<table>
  <thead>
    <tr>
      <th>Produit</th>
      <th>Inventaire</th>
      <th>Unité</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ( $products as $key => $product_arr ) : ?>
      <tr>
        <td><?php echo $product_arr['_short_name'];?></td>
        <td><input type="text" value="<?php echo $product_arr['_pq_operation_stock'];?>"></td>
        <td><?php echo $product_arr['_lot_unit'];?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>