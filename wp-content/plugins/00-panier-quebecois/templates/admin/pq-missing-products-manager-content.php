<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

?>

<form id="missing-product-form">

  <div class="pq-form-row" id="missing-product-type-wrapper">
    <label for="missing-product-type">Type de produit manquant:</label>
    <select name="missing-product-type" id="missing-product-type">
      <option value="replacement">Remplacement</option>
      <option value="organic-replacement">Remplacement BIO</option>
      <option value="refund">Remboursement</option>
    </select>
  </div>

  <div class="pq-form-row product-selection-wrapper" id="missing-product-wrapper">
    <label for="pq-missing-short-name-search-box">Produit manquant:</label>
    <div class="pq-search-box-wrapper">
      <input type="text" id="pq-missing-short-name-search-box" name="pq-missing-short-name-search-box" class="pq-short-name-search-box">
      <ul class="pq-search-results"></ul>
    </div>
    <input type="hidden" id="selected-missing-product" name="selected-missing-product" class="selected-product">
  </div>

  <div class="pq-form-row product-selection-wrapper" id="replacement-product-wrapper">
    <label for="pq-replacement-short-name-search-box">Produit de remplacement:</label>
    <div class="pq-search-box-wrapper">
      <input type="text" id="pq-replacement-short-name-search-box" name="pq-replacement-short-name-search-box" class="pq-short-name-search-box">
      <ul class="pq-search-results"></ul>
    </div>
    <input type="hidden" id="selected-replacement-product" name="selected-replacement-product" class="selected-product">
  </div>

  <div class="pq-form-row" id="is-refund-needed-wrapper">
    <label for="is-refund-needed">Remboursement nécéssaire?</label>
    <input type="checkbox" name="is-refund-needed" id="is-refund-needed" value="1" checked>
  </div>

  <div class="pq-form-row" id="manual-refund-amount-wrapper">
    <label for="manual-refund-amount">Montant à rembourser</label>
    <input type="number" name="manual-refund-amount" id="manual-refund-amount">
  </div>

  <button id="review-missing-product">Suivant</button>
  <?php wp_nonce_field('pq_missing_products_submit', 'pq_missing_products_submit_nonce'); ?>

</form>

<div id="review-missing-product-popup-wrapper">
  <div id="review-missing-product-popup">
    <span class="pq-close">×</span>
    <div id="review-missing-product-content-wrapper"></div>
    <button id="submit-missing-product">Envoyer</button>
  </div>
</div>