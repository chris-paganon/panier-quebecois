<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

?>

<form id="missing-product-form">

  <div class="pq-form-row missing-product-type-wrapper">
    <label for="missing-product-type">Type de produit manquant:</label>
    <select name="missing-product-type" id="missing-product-type">
      <option value="replacement">Remplacement</option>
      <option value="organic-replacement">Remplacement BIO</option>
      <option value="refund">Remboursement</option>
    </select>
  </div>

  <div class="pq-form-row product-selection-wrapper" id="missing-product-wrapper">
    <label for="pq-missing-short-name-search-box">Produit manquant:</label>
    <input type="text" id="pq-missing-short-name-search-box" name="pq-missing-short-name-search-box" class="pq-short-name-search-box">
    <input type="hidden" id="selected-missing-product" name="selected-missing-product" class="selected-product">
    <ul class="pq-search-results"></ul>
  </div>

  <div class="pq-form-row product-selection-wrapper" id="replacement-product-wrapper">
    <label for="pq-replacement-short-name-search-box">Produit de remplacement:</label>
    <input type="text" id="pq-replacement-short-name-search-box" name="pq-replacement-short-name-search-box" class="pq-short-name-search-box">
    <input type="hidden" id="selected-replacement-product" name="selected-replacement-product" class="selected-product">
    <ul class="pq-search-results"></ul>
  </div>

  <button id="review-missing-product">Suivant</button>

</form>

<div id="review-missing-product-popup">
  <div id="review-missing-product-content-wrapper"></div>
  <button id="submit-missing-product">Envoyer</button>
</div>