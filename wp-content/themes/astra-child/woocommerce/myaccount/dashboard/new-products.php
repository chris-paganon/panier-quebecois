<div class="mto-dashboard-container dashboard-new-products">
    <p class="dashboard-np-title"><?php echo $first_name ? $first_name.', ' : ''; ?><?php echo __('discover our new products', 'panierquebecois'); ?></p>
	<?php do_shortcode('[pq_products_slider type="meta" key="_pq_new" value="1"]') ?>
    <p class="dashboard-np-btn">
        <a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ) ?>"><?php echo __('Visit the market', 'panierquebecois'); ?></a>
    </p>
</div>
