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
 * @var string $is_refund_needed
 * @var string $refund_amount
 */

?>

<p>Bonjour <?php esc_html_e($billing_first_name) ?>,</p>
<p>En achetant les produits ce matin, notre marchand nous a informé ne plus avoir de <?php _e($missing_product_name) ?> en stock. Nous avons donc décidé de le remplacer par son équivalent "non bio"<?php if ( ! $is_refund_needed ) { esc_html_e(' au même prix', 'panier-quebecois');} ?>.</p>

<?php if ( $is_refund_needed ) : ?>
  <p>Nous vous avons donc remboursé les <?php echo wc_price($refund_amount); ?> de différence. Si vous souhaitez toutefois un remboursement total nous pouvons également le faire sur demande.</p>
<?php endif ?>

<p>Nous faisons toujours notre possible pour vous fournir les meilleurs produits du marché en fonction des stocks disponibles.</p>

<p>Nous nous excusons pour ce changement de dernière minute, et vous remercions pour votre confiance dans notre service!</p> 
<p>Bonne journée,</p>