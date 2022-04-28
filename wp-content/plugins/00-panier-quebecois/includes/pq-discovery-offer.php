<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Checkout and cart process modifications for the discovery offer
 */
class PQ_discovery_offer {
  /**
   * Variables
   * 
   */
  protected static $_instance = null;

  /**
   * Initiate a single instance of the class
   * 
   */
  public static function instance() {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function __construct() {
    $this->pq_init_hooks();
  }

  /**
   * Initiate all the hooked functions
   * 
   */
  public function pq_init_hooks() {
    //Hide menu items for discovery offer 
    add_action( 'woocommerce_before_checkout_form', array( $this, 'pq_checkout_menu_hidder_for_discovery' ) );
    add_action( 'woocommerce_before_cart', array( $this, 'pq_checkout_menu_hidder_for_discovery' ) );
    add_action( 'woocommerce_before_single_product', array( $this, 'pq_checkout_menu_hidder_for_discovery' ) );
  }

  /**
   * Hide menu items for discovery offer 
   */
  public static function pq_checkout_menu_hidder_for_discovery() {
    $has_discovery_offer = false;

    foreach ( WC()->cart->get_coupons() as $code => $coupon ) {

      if ( $code == 'decouverte' || $code == 'decouverte_bio' ) {
        $has_discovery_offer = true;
      }
    }

    if ( $has_discovery_offer ) { ?>
      <script type="text/javascript">
        jQuery(document).ready(function($) {
          $('.pq_remove_simplified').hide();
          $('#my-nav-bar .elementor-widget-nav-menu').hide();
          $('#pq_homepage_pic a').attr('href', '<?php echo get_permalink(32271); ?>');
          $('.pq_return_to_shop a').attr('href', '<?php echo get_permalink(32285); ?>');
        });
      </script><?php
    }
  }
}