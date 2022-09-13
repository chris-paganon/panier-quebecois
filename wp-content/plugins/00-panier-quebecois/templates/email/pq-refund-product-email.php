<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Template vars.
 *
 * @var string $missing_product_name
 * @var string $billing_first_name
 * @var string $billing_language
 */

?>

<p>Bonjour <?php esc_html_e($billing_first_name) ?>,</p>
<p>En achetant les produits ce matin, notre marchand nous a informé ne plus avoir de <?php _e($missing_product_name) ?> en stock. En l'absence de produit équivalent nous avons décidé de vous le rembourser directement sur votre carte de crédit/débit.</p>

<p>Nous faisons toujours notre possible pour vous fournir les meilleurs produits du marché en fonction des stocks disponibles.</p>

<p>Nous nous excusons pour ce changement de dernière minute, et vous remercions pour votre confiance dans notre service!</p>

<p>Bonne journée,</p>