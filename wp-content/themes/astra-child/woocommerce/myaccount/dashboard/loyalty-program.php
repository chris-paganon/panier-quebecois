<!--hardcoded bg image-->
<?php $upload_dir = wp_upload_dir();    ?>
<div class="dashboard-loyalty-program" style="background-image: url(<?php echo wp_get_attachment_url( 61319 ); ?>">
    <p class="dashboard-lp-title"><?php echo __('Loyalty Program', 'panierquebecois'); ?></p>
	<?php if (pq_has_main_badge($user_id)) : ?>
    <?php
		$mycred = mycred();
		$balance = $mycred->get_users_balance( $user_id );
		?>
    <p class="dashboard-lp-discount"><?php echo __('Your cash back available:', 'panierquebecois') ?></p>
    <p class="dashboard-lp-balance"><?php echo wc_price($balance); ?></p>

	<?php else : ?>
    <p class="dashboard-lp-text">
        <?php 
        $minimum_orders = 5;

        $user_orders = wc_get_orders( array(
            'limit' => $minimum_orders,
            'customer_id' => $user_id,
        ));
        
        $count_user_orders = count( $user_orders );
        $orders_left = $minimum_orders - $count_user_orders;

        echo esc_html__( 'After 5 orders processed on our online shop, you\'ll unlock our 1% cash back system redeemable anytime. ', 'panierquebecois' );
        echo esc_html__( 'You only need ', 'panierquebecois' ) . $orders_left . esc_html__( ' more order(s)!', 'panierquebecois' );
        ?>
    </p>
	<?php endif; ?>
    <p class="dashboard-lp-btn">
        <a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ) ?>"><?php echo __('Place an order', 'panierquebecois'); ?></a>
    </p>
</div>
