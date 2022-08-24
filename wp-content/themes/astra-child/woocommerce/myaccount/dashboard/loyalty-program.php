<!--hardcoded bg image-->
<div class="dashboard-loyalty-program" style="background-image: url(https://panierquebecois.test/wp-content/uploads/2022/08/dashboard-loyalty-program-bg.jpg);">
    <p class="dashboard-lp-title"><?php echo __('Loyalty Program', 'panierquebecois'); ?></p>
	<?php if(pq_has_main_badge($user_id)) : ?>
    <?php
		$mycred = mycred();
		$balance = $mycred->get_users_balance( $user_id );
		?>
    <p class="dashboard-lp-discount"><?php echo __('Your loyalty discount amount:') ?></p>
    <p class="dashboard-lp-balance"><?php echo wc_price($balance); ?></p>

	<?php else : ?>
    <p class="dashboard-lp-text">
        <?php echo __('After 5 orders processed on our online shop, you\'ll unlock our loyalty system of 1% available whenever you need.', 'panierquebecois' ); ?>
    </p>
	<?php endif; ?>
    <p class="dashboard-lp-btn">
        <a href="<?php get_permalink( wc_get_page_id( 'shop' ) ) ?>"><?php echo __('Place an order', 'panierquebecois'); ?></a>
    </p>
</div>
