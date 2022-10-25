<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

$timezone = new DateTimeZone( get_option( 'timezone_string' ) );
$default_date_obj = new DateTime( 'today', $timezone );
$default_date = $default_date_obj->format( 'Y-m-d' ); ?>

<div class="wrap">
  <h2>Exporter les commandes</h2>
  <form action="admin.php?page=pq-export-orders" method="post">
    <?php wp_nonce_field('export_orders_clicked'); ?>
    <input type="text" name="my_delivery_date" placeholder="AAAA-MM-JJ" value="<?php echo $default_date ?>" />
    <input type="hidden" value="true" name="export_orders" />
    <?php submit_button('Exporter les commandes'); ?>
  </form>
</div>