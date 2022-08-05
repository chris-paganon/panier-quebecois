<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Send SMS to sellers 
 */
function pq_send_seller_sms() {

  require PQ_VENDOR_DIR . 'autoload.php';

  $sid = 'TWILIO_ACCOUNT_SID';
  $token = 'TWILIO_AUTH_TOKEN';
  $FROM_NO = 'TWILIO_FROM_NO';

  $twilio = new Twilio\Rest\Client($sid, $token);

  $message = $twilio->messages->create('', array(
    'body' => 'This is the ship that made the Kessel Run in fourteen parsecs?',
    'from' => '',
  ));
}