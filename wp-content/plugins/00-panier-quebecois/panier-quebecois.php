<?php

if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/*
Plugin Name: 00-Panier Québécois
Description: All custom modifications for the Panier Québécois store
Version: 2.0
Author: Christophe Paganon
*/

/**
 * Initialize main plugin
 * 
 */
add_action( 'plugins_loaded', array( 'Panier_Quebecois', 'instance' ) );

class Panier_Quebecois {
  /**
   * Variables
   */
  protected static $_instance = null;

  /**
   * Initiate a single instance of the class
   */
  public static function instance() {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function __construct() {
    $this->define_constants();
    $this->includes();
    $this->pq_init_classes();
    $this->pq_hook_scripts();
  }

  /**
   * Define directory and URL constants
   */
  private function define_constants() {
    //Filesystem directory paths (for php files)
    define( 'PQ_ROOT_DIR', plugin_dir_path( __FILE__ ) );
    define( 'PQ_INCLUDE_DIR', PQ_ROOT_DIR . 'includes/' );
    define( 'PQ_TEMPLATE_DIR', PQ_ROOT_DIR . 'templates/' );
    define( 'PQ_VENDOR_DIR', PQ_ROOT_DIR . 'vendor/' );
    define( 'PQ_INCLUDE_ADMIN_DIR', PQ_INCLUDE_DIR . 'admin/' );
    define( 'PQ_INCLUDE_LOYALTY_DIR', PQ_INCLUDE_DIR . 'loyalty/' );
    define( 'PQ_INCLUDE_EMAIL_DIR', PQ_INCLUDE_DIR . 'email/' );
    define( 'PQ_INCLUDE_ROCKET_DIR', PQ_INCLUDE_DIR . 'wp-rocket/' );
    define( 'PQ_INCLUDE_AJAXSHOP_DIR', PQ_INCLUDE_DIR . 'ajax-shop/' );

    //Web addresses URL (for css & JS)
    define( 'PQ_ROOT_URL', plugin_dir_url( __FILE__ ) );
    define( 'PQ_ASSETS_URL', PQ_ROOT_URL . 'assets/' );
    define( 'PQ_CSS_URL', PQ_ASSETS_URL . 'css/' );
    define( 'PQ_JS_URL', PQ_ASSETS_URL . 'js/' );
  }

  /**
   * Include relevant php files
   */
  public function includes() {
    //Dependencies
    require_once PQ_VENDOR_DIR . 'autoload.php';

    //Admin
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-export-admin-pages.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-export-admin.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-export-helpers-dependencies.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-export-helpers.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-export-operations.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-products-meta.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-orders-completion.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-stock-notifs.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-settings-pages.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-supplier-orders.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-supplier-emails.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-supplier-sms.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-inventory-manager.php' );
    include_once( PQ_INCLUDE_ADMIN_DIR . 'pq-missing-products-manager.php' );

    //AJAX shop
    include_once( PQ_INCLUDE_AJAXSHOP_DIR . 'pq-ajax-shop-starter.php' );

    //Email
    include_once( PQ_INCLUDE_EMAIL_DIR . 'pq-email.php' );

    //Loyalty
    include_once( PQ_INCLUDE_LOYALTY_DIR . 'pq-loyalty-content.php' );
    include_once( PQ_INCLUDE_LOYALTY_DIR . 'pq-loyalty-badges.php' );
    include_once( PQ_INCLUDE_LOYALTY_DIR . 'pq-loyalty-referral.php' );
    include_once( PQ_INCLUDE_LOYALTY_DIR . 'pq-loyalty-rewards.php' );
    include_once( PQ_INCLUDE_LOYALTY_DIR . 'pq-loyalty-helper.php' );

    //WP Rocket
    include_once( PQ_INCLUDE_ROCKET_DIR . 'pq-wp-rocket-bypass.php' );

    //Main
    include_once( PQ_INCLUDE_DIR . 'pq-global-functions.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-shop-archives.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-shop-content.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-product-page-content.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-discovery-offer.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-checkout.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-myaccount.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-delivery-days.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-products-slider.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-wishlist.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-page-content.php' );
    include_once( PQ_INCLUDE_DIR . 'pq-navbar.php' );
    //include_once( PQ_INCLUDE_DIR . 'pq-ajax-add-to-cart.php' );
  }

  /**
   * Instanciate the classes
   */
  public function pq_init_classes() {
    //Admin
    PQ_products_meta::instance();
    PQ_orders_completion::instance();

    //Loyalty
    PQ_loyalty_content::instance();
    PQ_loyalty_rewards::instance();
    PQ_loyalty_helper::instance();
    PQ_loyalty_referral::instance();

    //Main
    PQ_shop_content::instance();
    PQ_product_page_content::instance();
    PQ_discovery_offer::instance();
  }

  /**
   * Hook in function to enqueue scripts
   */
  public function pq_hook_scripts() {
    add_action( 'wp_enqueue_scripts', array( $this, 'pq_enqueue_scripts' ) );
  }

  /**
   * Enqueue scripts
   */
  public static function pq_enqueue_scripts() {
    //Enqueue JS
    wp_enqueue_script( 'pq_header', PQ_JS_URL . 'pq-header.js', array( 'jquery' ), rand( 111, 9999 ), true );
    wp_enqueue_script( 'pq_optional_bundle_item_button', PQ_JS_URL . 'pq_optional_bundle_item_button.js', array( 'jquery' ), rand( 111, 9999 ), true );
    wp_enqueue_script( 'pq_cat_filters_script', PQ_JS_URL . 'pq-cat-filters-script.js', array( 'jquery' ), rand( 111, 9999 ), true );
    wp_enqueue_script( 'pq_anchor_switcher', PQ_JS_URL . 'pq-anchor-switcher.js', array( 'jquery' ), rand( 111, 9999 ), false );
    wp_enqueue_script( 'pq_shop_archives', PQ_JS_URL . 'pq-shop-archives.js', array( 'jquery' ), rand( 111, 9999 ), false );
    wp_enqueue_script( 'pq_delivery_countdown', PQ_JS_URL . 'pq-delivery-countdown.js', array( 'jquery' ), rand( 111, 9999 ), false );

    // Register, Enqueue and localise ajax script for loading all products through ajax (on marketplace only)
    if ( get_the_ID() == 6720 ) {
      wp_enqueue_script( 'pq_get_all_products_js', PQ_JS_URL . 'get-all-products.js', array( 'jquery' ), rand( 111, 9999 ), true );
      wp_localize_script( 'pq_get_all_products_js', 'pq_get_all_products_js_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
      ) );
    }

    if ( get_the_ID() == get_option( 'woocommerce_checkout_page_id' ) ) {
      wp_enqueue_script( 'pq_checkout_js', PQ_JS_URL . 'pq-checkout.js', array( 'jquery' ), rand( 111, 9999 ), true );
    }

    $next_delivery_deadline = PQ_delivery_days::pq_next_delivery_deadline();
    $next_delivery_deadline_formatted_JS = $next_delivery_deadline->format(DateTime::ATOM);
    wp_localize_script( 'pq_delivery_countdown', 'next_delivery_deadline_object', array(
      'next_delivery_deadline' => $next_delivery_deadline_formatted_JS,
    ) );
    
    //Slick carousel
		global $post;
    if ( ((is_a( $post, 'WP_Post' ) ) && has_shortcode( $post->post_content, 'pq_products_slider')) || is_account_page()) {
      wp_enqueue_script( 'pq_products_slider', PQ_JS_URL . 'pq-products-slider.js', array( 'jquery', 'slick-js' ), rand( 111, 9999 ), false );
      wp_enqueue_style( 'slick-css', PQ_ASSETS_URL . 'src/library/css/slick.css', [], false, 'all' );
      wp_enqueue_style( 'slick-theme-css', PQ_ASSETS_URL . 'src/library/css/slick-theme.css', ['slick-css'], false, 'all' );
      wp_enqueue_script( 'slick-js', PQ_ASSETS_URL . 'src/library/js/slick.min.js', ['jquery'], rand( 111, 9999 ), true );
    }
    
    //Inventory manager AJAX
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'pq_inventory_manager') ) {
      wp_enqueue_script( 'pq_inventory_manager_js', PQ_JS_URL . 'pq-inventory-manager-ajax.js', array( 'jquery' ), rand( 111, 9999 ), true  );
      wp_localize_script( 'pq_inventory_manager_js', 'pq_inventory_manager_variables', array('ajax_url' => admin_url('admin-ajax.php')) );
	    wp_enqueue_style( 'pq_inventory_manager_css', PQ_CSS_URL . '/pq-inventory-manager.css', array('astra-theme-css'), rand(111,9999) );
    }

    //Missing products manager JS, CSS and AJAX
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'pq_missing_products_manager') ) {
      wp_enqueue_script( 'pq_missing_products_manager_js', PQ_JS_URL . 'pq-missing-products-manager.js', array( 'jquery' ), rand( 111, 9999 ), true  );
      wp_localize_script( 'pq_missing_products_manager_js', 'pq_missing_products_variables', array('ajax_url' => admin_url('admin-ajax.php')) );
	    wp_enqueue_style( 'pq-missing-products-manager_css', PQ_CSS_URL . '/pq-missing-products-manager.css', array('astra-theme-css'), rand(111,9999) );
    }
  }
}