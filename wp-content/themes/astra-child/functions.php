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

add_action('wp_head', function(){
	if( is_front_page() && is_user_logged_in() && ! current_user_can('manage_options') ){
		echo "<script type='text/javascript'>        
			if(!sessionStorage.getItem('dashboard_seen')){
            sessionStorage.setItem('dashboard_seen', true);
            window.location.replace('/mon-compte/');
        }
			</script>";
	}
});


/**
 * Add GTM tags
 */
add_action( 'wp_head', 'add_gtm_head_tag' );
function add_gtm_head_tag() {
	?>
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-W3FQLFB');</script>
	<!-- End Google Tag Manager -->
	<?php
}

add_action( 'wp_footer', 'add_gtm_body_tag' );
function add_gtm_body_tag() {
	?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-W3FQLFB"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	<?php
}

// END ENQUEUE PARENT ACTION

// ************************************************************************************************ //