<!--hardcoded bg image-->
<div>
    <p><?php echo __('Loyalty Program', 'panierquebecois'); ?></p>
	<?php if(pq_has_main_badge($user_id)) : ?>
    <?php
		$mycred = mycred();
		$balance = $mycred->get_users_balance( $user_id );
		?>
    <p><?php echo __('Your loyalty discount amount:') ?></p>
    <p><?php echo wc_price($balance); ?></p>

	<?php else : ?>
    <p><?php echo __('After 5 orders processed on our online shop, you\'ll unlock our loyalty system of 1% available whenever you need.', 'panierquebecois' ); ?>
    </p>
	<?php endif; ?>
    <p><a href="<?php get_permalink( wc_get_page_id( 'shop' ) ) ?>"><?php echo __('Place an order', 'panierquebecois'); ?></a>
    </p>
</div>
