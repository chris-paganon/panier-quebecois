<?php
/**
 * Checkout login form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() || 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
  return;
}

if ( $checkout->is_registration_required() ) {
  $login_or_message = 'or create an account by completing your order below';
  $login_details_message = 'If you already have an account, please login here. If you are a new customer, please proceed with your order below to create your account.';
} else {
  $login_or_message = 'or create an account by completing your order below (optional)';
  $login_details_message = 'If you already have an account, please login here. If you are a new customer, don\'t worry, you can make your order without an account.';
}

?>
<div class="woocommerce-form-login-toggle">
  <div id="my-checkout-login-id"></div>
  <?php wc_print_notice( ' <a href="#" class="showlogin">' . esc_html__( 'Click here to login ', 'woocommerce' ) . '</a>' . esc_html__( $login_or_message, 'woocommerce' ), 'notice'); ?>
</div>
<?php

woocommerce_login_form(
  array(
    'message' => esc_html__( $login_details_message, 'woocommerce' ),
    'redirect' => wc_get_checkout_url(),
    'hidden' => true,
  )
);
