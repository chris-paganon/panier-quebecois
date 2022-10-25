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

<div class="inventory-filters-wrapper">
  <div class="inventory-filter">
    <label for="product-categories">Catégorie</label>
    <select name="product-categories" id="product-categories" class="pq-inventory-options">
      <option value="all">Tous les produits</option>
      <option value="fruit-et-legumes">Fruits et légumes</option>
      <option value="epicerie">Épicerie</option>
      <option value="frais">Frais</option>
    </select>
  </div>

  <div class="inventory-filter">
    <label for="inventory-type">Type d'inventaire</label>
    <select name="inventory-type" id="inventory-type" class="pq-inventory-options">
      <option value="all">Tous les types</option>
      <?php 
      $inventory_types = get_terms( array(
        'taxonomy' => 'pq_inventory_type',
      ));
      foreach ( $inventory_types as $inventory_type ) : ?>
        <option value="<?php echo $inventory_type->slug ?>"><?php echo $inventory_type->name ?></option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="inventory-filter">
    <label for="has-stock">Stock renseigné?</label>
    <input type="checkbox" name="has-stock" id="has-stock" class="pq-inventory-options">
  </div>
</div>

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
      <tr class="inventory-product-row" product-data="<?php echo $product_json; ?>">
        <td class="inventory_short_name"><?php echo $product_arr['_short_name'];?></td>
        <td class="inventory_stock">
          <input class="inventory_stock_input" type="text" value="<?php echo $product_arr['_pq_operation_stock'];?>">
          <?php wp_nonce_field('pq_inventory_changed', 'pq_inventory_nonce'); ?>
        </td>
        <td class="inventory_unit"><?php echo $product_arr['_lot_unit'];?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>