<?php
if ( !defined( 'ABSPATH' ) ) exit;

?>

<div class="pq-popup-lightbox delivery-zone-select-popup-lightbox">
  <div class="pq-popup-box delivery-zone-select-popup">
    <div class="pq-popup__content delivery-zone-select-popup__content">
      <h3><?php esc_html_e('Entrez votre code postal', 'panier-quebecois'); ?></h3>
      <input type="text" name="pq-postal-code" id="pq-postal-code" placeholder="H0H 0H0" />
      <button id="pq-postal-code-submit">Valider</button>
      <div class="pq-postal-code-error"></div>
      <p><?php esc_html_e('Nous livrons maintenant partout au Québec. Nous avons besoin de votre code postal pour déterminer quels produits sont disponibles dans votre zone', 'panier-quebecois'); ?></p>
    </div>
  </div>
</div>