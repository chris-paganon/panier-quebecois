<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Manage loyalty referrals
 */
class PQ_loyalty_referral {
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
    //Set cookie
    add_action( 'init', array( 'PQ_loyalty_referral', 'pq_set_referral_cookie' ) );

    //Give referrer reward
    add_action( 'woocommerce_payment_complete', array( 'PQ_loyalty_referral', 'pq_referral_order' ), 10 );

    //Give and validate referree coupon
    add_action( 'woocommerce_before_cart_table', array( 'PQ_loyalty_referral', 'pq_referree_reward' ) );
    add_action( 'woocommerce_before_checkout_form', array( 'PQ_loyalty_referral', 'pq_referree_reward' ) );
    add_filter( 'woocommerce_cart_totals_coupon_label', array( 'PQ_loyalty_referral', 'pq_referral_coupon_label' ), 10, 2 );
    add_action( 'woocommerce_after_checkout_validation', array( 'PQ_loyalty_referral', 'pq_validate_referree_checkout' ), 10, 2 );
  }

  /**
   * Helper functions
   * 
   */

  /* ----- Check if user ID or email made order in the last X days ------- */
  public static function pq_has_bought( $billing_email ) {
    $deadline_difference_days = 60;

    $deadline_difference_seconds = $deadline_difference_days * 24 * 60 * 60;
    $deadline_timestamp = time() - $deadline_difference_seconds;
    $ten_minutes_ago = time() - 600;

    $last_order = wc_get_orders( array(
      'billing_email' => $billing_email,
      'date_created' => $deadline_timestamp . '...' . $ten_minutes_ago,
      'limit' => 1,
    ) );

    if ( !empty( $last_order ) ) {
      return true;
    } else {
      return false;
    }
  }

  /* ----- Check if referrer user ID is also referree ID and if it exists ------ */
  public static function pq_get_valid_referrer_user_id( $mycred_referral_cookie_value ) {
    // Get referrer user ID
    $referrer_user = get_user_by( 'login', $mycred_referral_cookie_value );
    if ( isset( $referrer_user ) ) {
      $referrer_user_id = $referrer_user->ID;
    } else {
      return false;
    } //No account exists with this ID

    // Check if buyer was also referrer and if first purchase of referree
    $user_id = get_current_user_id();

    if ( $referrer_user_id != $user_id ) {
      return $referrer_user_id;
    } else {
      return false; //Referrer is also referree
    }
  }

  /**
   * Referral hooked functions
   * 
   */

  /* ----- Set the referral cookie ------ */
  public static function pq_set_referral_cookie() {
    if ( !isset( $_GET[ 'pqc' ] ) || empty( $_GET[ 'pqc' ] ) || isset( $_COOKIE[ 'pq_referral' ] ) ) return;

    $referrer_username = $_GET[ 'pqc' ];
    $referrer_user_id = PQ_loyalty_referral::pq_get_valid_referrer_user_id( $referrer_username );

    if ( $referrer_user_id ) {
      setcookie( 'pq_referral', $referrer_username, time() + 3600 * 24 * 30, COOKIEPATH, COOKIE_DOMAIN );
    }
  }

  /* ----- Give reward to referrer after referral purchase ------- */
  public static function pq_referral_order( $order_id ) {
    if ( !function_exists( 'mycred' ) ) return;
    // Only applicable on successfull paiement with referral cookie
    if ( !isset( $_COOKIE[ 'pq_referral' ] ) ) return;

    $mycred_referral_cookie_value = $_COOKIE[ 'pq_referral' ];
    $referrer_user_id = PQ_loyalty_referral::pq_get_valid_referrer_user_id( $mycred_referral_cookie_value ); //return false if not valid

    $order = wc_get_order( $order_id );
    $billing_email = $order->get_billing_email();
    $has_bought = PQ_loyalty_referral::pq_has_bought( $billing_email );

    // Check if buyer was also referrer and if first purchase of referree
    if ( !$has_bought && $referrer_user_id ) {
      $mycred = mycred();
      $referral_bonus = 20;

      //Give points to referrer
      $mycred->add_creds(
        'Referral Reward',
        $referrer_user_id,
        $referral_bonus,
        'Reward for referree purchase #' . $order_id
      );
    } else {
      $referrer_name = __( get_user_meta( $referrer_user_id, 'first_name', true ) );

      wc_add_notice( __( 'Les clients existants ne sont pas éligibles pour le parrainage' ), 'notice' );
    }

    //Remove cookie for the referree
    unset( $_COOKIE[ 'pq_referral' ] );
    setcookie( 'pq_referral', $mycred_referral_cookie_value, time() - 86400 );
  }

  /* ------ Give discount to referree ------ */
  public static function pq_referree_reward() {
    if ( !function_exists( 'mycred' ) ) return;
    if ( !isset( $_COOKIE[ 'pq_referral' ] ) ) return;

    $mycred_referral_cookie_value = $_COOKIE[ 'pq_referral' ];
    $referrer_user_id = PQ_loyalty_referral::pq_get_valid_referrer_user_id( $mycred_referral_cookie_value );

    $referrer_name = __( get_user_meta( $referrer_user_id, 'first_name', true ) );

    wc_print_notice( sprintf( 'Vous avez été référé par %s', $referrer_name ), 'notice' );

    //Apply coupon code if referree is not also referrer
    if ( $referrer_user_id ) {
      $referral_coupon_is_applied = false;
      $applied_coupons = WC()->cart->get_applied_coupons();
      foreach ( $applied_coupons as $applied_code ) {
        $applied_code_start = substr( $applied_code, 0, 4 );
        if ( $applied_code_start == 'ref_' ) {
          $referral_coupon_is_applied = true;
        }
      }

      //Only apply coupon if not already applied
      if ( !$referral_coupon_is_applied ) {
        //Create new single use coupon
        $coupon = new WC_Coupon;
        $coupon_code = 'ref_' . time();

        $coupon->set_code( $coupon_code );
        $coupon->set_discount_type( 'fixed_cart' );
        $coupon->set_individual_use( true );
        $coupon->set_amount( 20 );
        $coupon->set_usage_limit( 1 );
        $coupon->save();

        //Add coupon to cart
        WC()->cart->add_discount( sanitize_text_field( $coupon_code ) );
      }
    }
  }

  //Change referral coupon code label    
  public static function pq_referral_coupon_label( $label, $coupon ) {
    //Check if coupon is referral coupon
    $coupon_start = substr( $coupon->get_code(), 0, 4 );
    if ( $coupon_start == 'ref_' ) {
      $label = 'Parrainage';
    }

    return $label;
  }

  /*------ Check referree is entitled to discount and remove cookie and discount otherwise ------ */
  public static function pq_validate_referree_checkout( $data, $errors ) {
    if ( !function_exists( 'mycred' ) ) return;
    if ( !isset( $_COOKIE[ 'pq_referral' ] ) ) return;

    $mycred_referral_cookie_value = $_COOKIE[ 'pq_referral' ];
    $referrer_user_id = PQ_loyalty_referral::pq_get_valid_referrer_user_id( $mycred_referral_cookie_value );

    $billing_email = $data[ 'billing_email' ];
    $has_bought = PQ_loyalty_referral::pq_has_bought( $billing_email );

    //If referree is also referrer or has made orders before: remove coupon and cookie, cancel order and display error
    if ( $has_bought || !$referrer_user_id ) {
      unset( $_COOKIE[ 'pq_referral' ] );
      setcookie( 'pq_referral', $mycred_referral_cookie_value, time() - 86400 );

      $applied_coupons = WC()->cart->get_applied_coupons();
      foreach ( $applied_coupons as $applied_code ) {
        $applied_code_start = substr( $applied_code, 0, 4 );
        if ( $applied_code_start == 'ref_' ) {
          $referral_coupon_code = $applied_code;
        }
      }

      WC()->cart->remove_coupon( $referral_coupon_code );

      //Display error messages on checkout after failed order attempt
      if ( $has_bought ) {
        $error_message = __( 'Les clients existants ne sont pas éligibles pour le parrainage' );
      } elseif ( !$referrer_user_id ) {
        $error_message = __( 'Vous ne pouvez pas être parrain et filleul en même temps!' );
      }

      $errors->add( 'referral', $error_message );
    }
  }
}