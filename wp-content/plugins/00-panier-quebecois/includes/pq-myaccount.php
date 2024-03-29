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

add_filter('walker_nav_menu_start_el', function($item_output, $menu_item, $depth, $args){
	if($menu_item->object_id === get_option( 'woocommerce_myaccount_page_id' ) && ($args->menu == 'menu-langue' || $args->menu == 'menu-mobile')) {
		$item_output .= pq_badge_loyalty_balance_fct();
	}
	return $item_output;
}, 10, 4 );

// ---------- Add shortcode for loyalty balance ---------- //
function pq_badge_loyalty_balance_fct(  ) {
  if ( is_user_logged_in() ) {
    $user_id  = get_current_user_id();
    if($user_id){
      if (pq_has_main_badge($user_id)){
        $mycred = mycred();
        $balance = $mycred->get_users_balance( $user_id );
        if($balance){
          $html = '<div class="pq_loyalty_balance_badge">';
          $html .= '<div class="balance">'.wc_price($balance).'</div>';
          $html .= '</div>';
          return $html;
        }
      }
    }
  }
  return '';
}