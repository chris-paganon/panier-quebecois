<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

?>

<form class="missing-product-form">

  <div class="product-selection-wrapper">
    <label for="pq-missing-short-name-search-box">Produit manquant:</label>
    <input type="text" id="pq-missing-short-name-search-box" class="pq-short-name-search-box">
    <input type="hidden" id="selected-missing-product" class="selected-product">
    <ul class="pq-search-results"></ul>
  </div>

  <div class="product-selection-wrapper">
    <label for="pq-replacement-short-name-search-box">Produit de remplacement:</label>

    <input type="text" id="pq-replacement-short-name-search-box" class="pq-short-name-search-box">
    <input type="hidden" id="selected-replacement-product" class="selected-product">
    <ul class="pq-search-results"></ul>
  </div>

  <button>Suivant</button>

</form>