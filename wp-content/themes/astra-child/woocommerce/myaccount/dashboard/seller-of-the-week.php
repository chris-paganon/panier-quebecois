<div class="mto-dashboard-container dashboard-seller-week">
    <?php if ( shortcode_exists('pq_seller_week') && do_shortcode('[pq_seller_week]') ) : ?>
        <p class="dashboard-sw-title"><?php echo __('Discover our seller of the week', 'panierquebecois'); ?></p>
        <?php do_shortcode('[pq_seller_week]'); ?>
    <?php endif ?>
</div>