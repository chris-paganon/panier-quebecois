<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

// --------------------------- ACCOUNT MODIFICATIONS  --------------------------- //


// --------- Make password strength easier on account creation ---------- //
add_filter( 'woocommerce_min_password_strength', 'pq_min_password_strength' );

function pq_min_password_strength( $strength ) {
  return 2;
}


// --------- Change the password hint on account creation ---------- //
add_filter( 'password_hint', 'pq_modify_password_hint' );

function pq_modify_password_hint( $hint ) {
  $hint = __( 'Conseil : Le mot de passe devrait contenir au moins huit caractères. Pour le rendre plus sûr, utilisez des lettres en majuscules et minuscules, des nombres, et des symboles tels que ! " ? $ % ^ & ).' ); //Hint: The password should be at least eight characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ &amp; ).

  return $hint;
}


// --------- Change "Mon compte" pour "Se connecter" on nav bar ---------- //
add_filter( 'wp_nav_menu_items', 'dynamic_label_change', 10, 2 );

function dynamic_label_change( $items, $args ) {
  if ( !is_user_logged_in() ) {
    $items = str_replace( ">Mon compte<", ">Se connecter<", $items );
  }
  return $items;
}


// ---------- Remove downloads tab on my account ---------- //
add_filter( 'woocommerce_account_menu_items', 'remove_downloads_my_account', 999 );

function remove_downloads_my_account( $items ) {
  unset( $items[ 'downloads' ] );
  return $items;
}