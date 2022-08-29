<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

$timezone = new DateTimeZone( get_option( 'timezone_string' ) );
$today_date_obj = new DateTime( 'today', $timezone );
$today_date = $today_date_obj->format( 'Y-m-d' );
$orders_today = myfct_get_relevant_orders( $today_date );

echo '<h4>Nombre de commandes aujourd\'hui: ' . count($orders_today) . '</h4>';

$tomorrow_date_obj = new DateTime( 'tomorrow', $timezone );
$tomorrow_date = $tomorrow_date_obj->format( 'Y-m-d' );
$orders_tomorrow = myfct_get_relevant_orders( $tomorrow_date );
echo '<h4>Nombre de commandes demain: ' . count($orders_tomorrow) . '</h4>';

?>
<form action="" method="post">
  <?php wp_nonce_field('operations_export_clicked'); ?>
  
  <input type="hidden" value="true" name="operations_export_form" />
  <input type="submit" value="Exporter les listes">
</form>

</br>

<form action="" method="post">
  <?php wp_nonce_field('labels_export_clicked'); ?>
  
  <input type="hidden" value="true" name="labels_export_form" />
  <input type="submit" value="Exporter les étiquettes">
</form>

</br>

<form action="" method="post">
  <?php wp_nonce_field('cold_labels_export_clicked'); ?>
  
  <input type="hidden" value="true" name="cold_labels_export_form" />
  <input type="submit" value="Exporter les étiquettes de froid">
</form>

</br>

<a class="button" href="<?php echo get_permalink(60999); ?>">Gérer l'inventaire</a>