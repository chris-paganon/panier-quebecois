<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )exit;

// BEGIN ENQUEUE PARENT ACTION

define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

function child_enqueue_styles() {

	//Enqueue CSS
	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), rand(111,9999) );
	wp_enqueue_style( 'mycss-navbar-style', get_stylesheet_directory_uri() . '/assets/css/mycss-navbar-style.css', array('astra-child-theme-css'), rand(111,9999) );
	wp_enqueue_style( 'mycss-other-style', get_stylesheet_directory_uri() . '/assets/css/mycss-other-style.css', array('astra-child-theme-css'), rand(111,9999) );
	wp_enqueue_style( 'mycss-woocommerce-style', get_stylesheet_directory_uri() . '/assets/css/mycss-woocommerce-style.css', array('astra-child-theme-css'), rand(111,9999) );
	wp_enqueue_style( 'pq_checkout_style', get_stylesheet_directory_uri() . '/assets/css/pq_checkout_style.css', array('astra-theme-css'), rand(111,9999) );
	wp_enqueue_style( 'pq_products_single_style', get_stylesheet_directory_uri() . '/assets/css/pq_products_single_style.css', array('astra-theme-css'), rand(111,9999) );
	wp_enqueue_style( 'pq_shop_style', get_stylesheet_directory_uri() . '/assets/css/pq_shop_style.css', array('astra-theme-css'), rand(111,9999) );
	wp_enqueue_style( 'pq_loyalty_style', get_stylesheet_directory_uri() . '/assets/css/pq_loyalty_style.css', array('astra-theme-css'), rand(111,9999) );
	wp_enqueue_style( 'pq_bundle_product_single_style', get_stylesheet_directory_uri() . '/assets/css/pq_bundle_product_single_style.css', array('astra-theme-css'), rand(111,9999) );
	wp_enqueue_style( 'pq_products_slider', get_stylesheet_directory_uri() . '/assets/css/pq_products_slider.css', array('astra-theme-css'), rand(111,9999) );
	
	wp_enqueue_style( 'mycss-font-lato', 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap', array('astra-child-theme-css'), rand(111,9999) );

	if(is_account_page()){
		wp_enqueue_style( 'pq_dashboard_style', get_stylesheet_directory_uri() . '/assets/css/pq_myaccount_style.css', array('astra-theme-css'), rand(111,9999) );
	}

	//Enqueue JS
	wp_enqueue_script( 'pq_products_single', get_stylesheet_directory_uri() . '/assets/js/pq_products_single.js', array('jquery'), rand(111,9999), true );
}

add_action( 'init', 'myfct_marketplace_menu_title' );

function myfct_marketplace_menu_title() {
  $current_language = get_locale();

  if ( $current_language == 'en_US' ) {
    wp_enqueue_style( 'mycss-marketplace-menu-en', get_stylesheet_directory_uri() . '/assets/css/mycss-marketplace-menu-en.css', array( 'astra-child-theme-css' ), rand( 111, 9999 ) );
  } elseif ( $current_language == 'fr_FR' ) {
    wp_enqueue_style( 'mycss-marketplace-menu-fr', get_stylesheet_directory_uri() . '/assets/css/mycss-marketplace-menu-fr.css', array( 'astra-child-theme-css' ), rand( 111, 9999 ) );
  }
}

add_filter( 'body_class', function($classes){
	if(is_account_page() && !is_wc_endpoint_url() && is_user_logged_in()){
		$classes[] = 'woocommerce-dashboard';
	}
	return $classes;
} );

//add_action( 'template_redirect', function() {
//	if(is_front_page() && is_user_logged_in()){
//		if(!isset($_COOKIE['dashboard_seen'])){
//			setcookie('dashboard_seen', true, 0, "/");
//			$url = get_permalink( get_option('woocommerce_myaccount_page_id') );
//			wp_redirect($url);
//			exit;
//		}
//	}
//} );

add_action('wp_head', function(){
	if(is_front_page() && is_user_logged_in()){
		echo "<script type='text/javascript'>        
			if(!sessionStorage.getItem('dashboard_seen')){
            sessionStorage.setItem('dashboard_seen', true);
            window.location.replace('/mon-compte/');
        }
			</script>";
	}
});


// END ENQUEUE PARENT ACTION

// ************************************************************************************************ //