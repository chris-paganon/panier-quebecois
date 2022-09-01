<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$user_info  = get_userdata( get_current_user_id() );
$first_name = $user_info->first_name;


?>
<div class="mto-dashboard">
<!--    full screen header-->
    <?php echo wc_get_template( 'myaccount/dashboard/header.php', ['first_name' => $first_name] ); ?>
<!--    new products-->
	<?php echo wc_get_template( 'myaccount/dashboard/new-products.php', ['first_name' => $first_name] ); ?>
<!--    full screen loyalty-->
	<?php echo wc_get_template( 'myaccount/dashboard/loyalty-program.php', ['user_id' => get_current_user_id()] ); ?>
    <!--    refferal-->
	<?php echo wc_get_template( 'myaccount/dashboard/refferal.php', ['user_id' => get_current_user_id()] ); ?>
    <!--    sellers of the week-->
	<?php echo wc_get_template( 'myaccount/dashboard/seller-of-the-week.php' ); ?>
</div>

