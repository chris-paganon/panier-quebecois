<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

function pq_send_seller_emails() {
  wp_mail( 'cpaganon@gmail.com', 'TEST SELLER', 'wasssssssup');
}

