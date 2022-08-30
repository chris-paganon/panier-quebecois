<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

$timezone = new DateTimeZone( get_option( 'timezone_string' ) );
$default_date_obj = new DateTime( 'tomorrow', $timezone );
$default_date = $default_date_obj->format( 'Y-m-d' ); ?>

<div class="wrap">
  <h2>Exporter les achats</h2>
  <form action="admin.php?page=pq-export-purchasing" method="post">
    <?php wp_nonce_field('export_purchasing_clicked'); ?>
    
    <label>Date de livraison
      <input type="text" name="my_delivery_date" placeholder="AAAA-MM-JJ" value="<?php echo $default_date ?>" />
    </label>
    </br></br>
    <label>Exporter les achats après cette commande (optionnel):
      <input type="text" name="pq_last_order"/>
    </label>
    <p>Pour les achats uniquement. Exporte les achats après le numéro de commande indiqué. N'inclus pas la commande indiquée. Format: 40123</p>

    <input type="hidden" value="true" name="export_purchasing_form" />
    <?php submit_button('Exporter les achats', 'primary', 'export_purchasing'); ?>
  </form>
</div>