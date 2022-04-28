<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
Plugin Name: 00-Temporary Functions
Description: Temporary functions to run once through button
Version: 1.0
Author: Christophe Paganon
*/


// ------ Add the page ------ //
add_action('admin_menu', 'myfct_importing_menu');

function myfct_importing_menu(){
	add_menu_page('Temporary fonctions', 'Temporary fonctions', 'manage_options', 'pq-temp-page', 'pq_temp_page_fonction');
}


// ------ Add the text and buttons ------ //
function pq_temp_page_fonction() {

    if (!current_user_can('manage_options'))  {
        wp_die( __('You do not have sufficient pilchards to access this page.')    );
    }

    ?>
    <div class="wrap">
    <h2>My temporary fonction page</h2>
    <?php

    // Check whether the button has been pressed AND also check the nonce
    if (isset($_POST['pq-temp-fonction']) && check_admin_referer('pq_temp_fonction_clicked')) {
    	pq_export_users();
    } 

    ?>
    <h3>Export all users with main badge</h3>
    <form action="options-general.php?page=pq-temp-page" method="post">
    <?php

		wp_nonce_field('pq_temp_fonction_clicked');
		echo '<input type="hidden" value="true" name="pq-temp-fonction" />';
		submit_button('Export');

    ?>
    </form>
    </div>
    <?php
}



/*
*
* Functions associated with the buttons
*
*/

/**
 * Export all users with a badge to CSV
 */
function pq_export_users() {
	echo '<div id="message" class="updated fade"><p>Badges exported</p></div>';

	if ( ! function_exists( 'mycred' ) ) return;
	$mycred  = mycred();

	$pq_badge_id = pq_get_main_badge_id();
	$users = get_users( array('number' => -1) );
	$csv = array(array(
		'email',
		'badge ID',
	));
	
	foreach ( $users as $user ) {
		$user_id = $user->ID;
		$email = $user->user_email;
		$user_badges = mycred_get_users_badges($user_id);

		if ( array_key_exists($pq_badge_id, $user_badges) ) {
			$user_line = array(array(
				$email,
				$pq_badge_id,
			));
			$csv = array_merge($csv, $user_line);
		}

	}

	$timezone = new DateTimeZone(get_option('timezone_string'));
	$now = new DateTime('', $timezone);
	$filename = 'users-with-badges_ ' . $now->format('Y-m-d G:i:s') . '.csv';
	myfct_export_csv($filename, $csv);
}

// ------ Convert points to cash back ------ //
function pq_mycred_bulk_convert_cred() {
	echo '<div id="message" class="updated fade"><p>The points were converted to cash back.</p></div>';

	if ( ! function_exists( 'mycred' ) ) return;
	$mycred  = mycred();

	$users = get_users( array('number' => -1) );

	$count=0;

	foreach ( $users as $user ) {
		$user_id = $user->ID;
		$balance = mycred_get_users_cred($user_id);

		if( $balance > 0 ) {

			$mycred->add_creds( 
			'Convert to cash back', 
			$user_id, 
			- ( $balance - ($balance*0.02) ),
			'Convert to cash back',
			);
		}
	}
}

// ------ Expire all old points ------ //
function pq_mycred_bulk_add_cred() {
	echo '<div id="message" class="updated fade"><p>The points expired.</p></div>';

	if ( ! function_exists( 'mycred' ) ) return;
	$mycred  = mycred();

	$users = get_users( array('number' => -1) );

	$count=0;

	foreach ( $users as $user ) {
		$user_id = $user->ID;
		$balance = mycred_get_users_cred($user_id);

		$last_reward_args = array(
			'user_id' => $user_id,
			'number' => 1,
			'amount' => array( 
			'num' => 0, 
			'compare' => '>' ),
		);

		$last_reward_log = new myCRED_Query_Log( $last_reward_args );

		if ( $last_reward_log->have_entries() ) {

			foreach ( $last_reward_log->results as $entry ) {
				$last_reward_timestamp = $entry->time;

				if( $balance != 0 && $last_reward_timestamp < 1618080604 ) {

					$mycred->add_creds( 
					'Manual Expiration', 
					$user_id, 
					- $balance, 
					'Manual Expiration',
					);
				}
			}
		}
	}  
} 


/**
 * Update cost of goods sold for all products
 */

function pq_update_products() {
	echo '<div id="message" class="updated fade"><p>The products were updated.</p></div>';

	$products_args = array(
		'return'      => 'ids',
		'limit'       => -1,
		'category'    => 'produit-unite',
	);

	$products = wc_get_products($products_args);

	foreach ( $products as $product_id ) {
		$purchasing_price = get_post_meta($product_id, '_purchasing_price', true);
		if ( ! empty($purchasing_price) ) {
			update_post_meta($product_id, '_wc_cog_cost', $purchasing_price);
		}
	}
}


/**
 * Translate dynamic content easily
 */

add_shortcode( 'temp_translation', 'temp_translation_fct' );

function temp_translation_fct() {
    if ( !function_exists( 'mycred' ) ) return;

	echo '<p>Utilisez la même adresse de messagerie que votre commande pour l\'attacher à votre compte.</p>';

    echo '<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">' . esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ) . '</p>';

    //Get order info
    $reward = 6;
    $img_width = '45px';

	$placeholder = true;

    //Message for logged in users
    if ( $placeholder ) {
      if ( $placeholder ) {

        echo '<h2 class="myloyalty-main-message"> ' . PQ_loyalty_helper::get_image( $img_width ) . esc_html__( ' Vous avez gagné ' ) . wc_price($reward) . esc_html__( ' de rabais.' ) . '</h2><p class="myloyalty-additional-message">' . esc_html__( 'Utilisez-les sur la page de paiement lors de votre prochaine commande.') . '</p>';
        
      }
	  if ( $placeholder ) {
        $orders_left = 2;

        if ( $placeholder ) {
          echo '<h2 class="myloyalty-main-message">' . PQ_loyalty_helper::get_image( $img_width ) . esc_html__( ' Plus que ') . $orders_left . esc_html__( ' commandes avant de cumuler vos remises en argent!' ) . '</h2>';
        }
      }

    } 
	if ( $placeholder ) {
      //Show message and registration form if not logged in
      echo '<h2 class="myloyalty-main-message">' . PQ_loyalty_helper::get_image( $img_width ) . esc_html__( ' Vous auriez pu gagner ' ) . wc_price($reward) . esc_html__( ' de remise en argent.' ) . '</h2>';
      echo '<p class="myloyalty-additional-message">' . esc_html__( 'Créez un compte maintenant pour recevoir des remises en argent à partir de votre 5ème commande.' ) . '</p>';
      echo '<p class="myloyalty-additional-message">' . esc_html__( 'Vous avez déjà un compte? Connectez-vous maintenant pour attacher la commande à votre compte et/ou récupérer votre remise.' ) . '</p>';

      echo '<div id="my-thankyou-registration">';
      echo wc_get_template( 'myaccount/form-login.php' );
      echo '</div>';
    }
  }