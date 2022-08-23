<!--hardcoded bg image-->
<div id="#mto-dashboard-header">
	<div class="dashboard-header"><?php echo __( 'Hello', 'panierquebecois' ) ?> <?php echo $first_name; ?></div>
	<div class="dashboard-subtitle"><?php echo __( 'What do you want to do today ?', 'panierquebecois' ) ?></div>
    <div class="main-cta">
        <a href="<?php get_permalink( wc_get_page_id( 'shop' ) ) ?>"><?php echo __('Visit the market', 'panierquebecois'); ?></a>
    </div>
    <div class="nav-ctas">
        <ul>
            <li><a href="<?php echo wc_get_endpoint_url('favoris', '', get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>"><?php echo __('Favorites', 'panierquebecois') ?></a></li>
            <li><a href="<?php echo wc_get_endpoint_url('fidelite', '', get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>"><?php echo __('Loyalty program', 'panierquebecois') ?></a></li>
            <li><a href="#"><?php echo __('Reffer a friend', 'panierquebecois') ?></a></li>
            <li><a href="<?php echo wc_get_endpoint_url('orders', '', get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>"><?php echo __('See my order history', 'panierquebecois') ?></a></li>
            <li><a href="<?php echo wc_get_endpoint_url('edit-account', '', get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>"><?php echo __('Edit my profile', 'panierquebecois') ?></a></li>
            <li><a href="<?php echo wp_logout_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>"><?php echo __('Log out', 'panierquebecois') ?></a></li>
        </ul>
    </div>
</div>