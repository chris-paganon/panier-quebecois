<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Template vars.
 *
 * @var string $missing_product_name
 * @var string $replacement_product_name
 * @var string $billing_first_name
 * @var string $billing_language
 * @var string $is_refund_needed
 * @var string $refund_amount
 */

?>

<p>Bonjour <?php esc_html_e($billing_first_name) ?>,</p>
<p>En achetant les produits ce matin, notre marchand nous a informé ne plus avoir le produit "<?php _e($missing_product_name) ?>"" en stock. Nous avons donc décidé de le remplacer par "<?php _e($replacement_product_name) ?>".</p>

<?php if ( $is_refund_needed ) : ?>
  <p>Nous vous avons donc remboursé les <?php echo wc_price($refund_amount); ?> de différence.</p>
<?php endif ?>

<p>Si le produit de remplacement ne vous convient pas, laissez-le nous savoir et nous nous ferons un plaisir de vous rembourser <?php if ( $is_refund_needed ) { echo 'intégralement'; } ?> (même si vous avez déjà reçus votre commande).</p>
<p>Nous faisons toujours notre possible pour vous fournir les meilleurs produits du marché en fonction des stocks disponibles. Nous nous excusons pour ce changement de dernière minute, et vous remercions pour votre confiance dans notre service!</p> 
<p>Bonne journée,</p>