<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Add loyalty content to my account, checkout and login
 */
class PQ_loyalty_content {
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
    //Add my account loyalty tab and its content
    add_action( 'init', array( 'PQ_loyalty_content', 'bbloomer_add_loyalty_endpoint' ) );
    add_filter( 'query_vars', array( 'PQ_loyalty_content', 'bbloomer_loyalty_query_vars' ), 0 );
    add_filter( 'woocommerce_account_menu_items', array( 'PQ_loyalty_content', 'bbloomer_add_loyalty_link_my_account' ) );
    add_action( 'woocommerce_account_fidelite_endpoint', array( 'PQ_loyalty_content', 'bbloomer_loyalty_content' ) );
    add_filter( 'woocommerce_registration_redirect', array( 'PQ_loyalty_content', 'pq_redirect_loyalty_login' ), 20, 1 );
    add_filter( 'woocommerce_login_redirect', array( 'PQ_loyalty_content', 'pq_redirect_loyalty_login' ), 20, 1 );

    //Add content on login and registration forms
    add_action( 'woocommerce_before_customer_login_form', array( 'PQ_loyalty_content', 'pq_add_loyalty_text_before_registration' ) );
    add_action( 'woocommerce_register_form_start', array( 'PQ_loyalty_content', 'pq_add_loyalty_text_before_thankyou_registration' ) );

    //Add content and partial payment form to checkout and thank you page
    add_action( 'woocommerce_review_order_before_payment', array( 'PQ_loyalty_content', 'pq_mycred_part_woo' ), 20 );
    add_action( 'woocommerce_before_thankyou', array( 'PQ_loyalty_content', 'pq_mycred_display_points_thankyou' ) );
  }

  /**
   * Add my account loyalty tab and its content
   * 
   */

  //Register new endpoint to use for My Account page
  // Note: Resave Permalinks or it will give 404 error

  public static function bbloomer_add_loyalty_endpoint() {
    add_rewrite_endpoint( 'fidelite', EP_ROOT | EP_PAGES );
  }

  // Add new query var
  public static function bbloomer_loyalty_query_vars( $vars ) {
    $vars[] = 'fidelite';
    return $vars;
  }

  // Insert the new endpoint into the My Account menu
  public static function bbloomer_add_loyalty_link_my_account( $items ) {
    $my_item = array( 'fidelite' => __( 'Programme de fidélité' ) );

    $new_items = array_slice( $items, 0, 1, true ) + $my_item + array_slice( $items, 1, count( $items ), true );

    return $new_items;
  }

  //Add content to the new endpoint
  public static function bbloomer_loyalty_content() {

    if ( !function_exists( 'mycred' ) ) return;
    $mycred = mycred();

    $user_id = get_current_user_id();
    $username = get_user_meta( $user_id, 'nickname', true );
    $balance = $mycred->get_users_balance( $user_id ) ?>

    <div class="my-mycred-wrapper">
      <h3 class="my-mycred-header"> <?php echo PQ_loyalty_helper::get_image(); ?> Programme de fidélité</h3>
      <p class="my-mycred-paragraph"><span class="my-mycred-bold">Après 5 commandes</span> passées sur notre site, vous accéderez à notre système de <span class="my-mycred-bold">remise en argent de 1%</span> que vous pourrez utiliser quand vous le souhaitez. Il n’y a ni de date d’expiration ni de minimum avant de pouvoir en bénéficier.</p>
      <h4 class="my-mycred-subheader">Votre rabais accumulé est de:</h4>
      <div class="mycred-my-balance-wrapper"><div><?php echo wc_price($balance); ?></div></div>
    </div>
    <div class="my-mycred-wrapper" id="referral">
      <h3 class="my-mycred-header"> <?php echo esc_html__('Référez un ami et recevez $20 chacun'); ?> </h3>
      <h4 class="my-mycred-subheader"><?php echo esc_html__('Envoyez le lien suivant à vos amis:'); ?></h4>
      <p><?php echo home_url() . '?pqc=' . $username; ?></p>
      <h4 class="my-mycred-subheader"><?php echo esc_html__('Comment ça marche?'); ?></h4>
      <p class="my-mycred-paragraph"> <span class="my-mycred-bold"><?php echo esc_html__('Recevez $20'); ?></span> <?php echo esc_html__('pour chaque ami référé! Ils bénéficieront aussi de $20 de rabais sur leur premier achat!'); ?> </p>
      <p class="my-mycred-paragraph"> <?php echo esc_html__('Quand vos amis passeront commande après avoir ouvert le lien ci-dessus, votre bonus s\'ajoutera'); ?><span class="my-mycred-bold"> <?php echo esc_html__('automatiquement sur votre compte.'); ?></span><?php echo esc_html__(' La promotion de $20 s’appliquera aussi directement lors de leur commande.'); ?> </p>
      <p class="my-mycred-paragraph pq-referral-conditions"> <?php echo esc_html__('Offre valable pour la première commande d\'un nouveau client.'); ?> </p>
    </div><?php
  }

  /**
   * Add content on login and registration forms
   * 
   */

  /* ------ Add text before login form (not on checkout) ------ */
  public static function pq_add_loyalty_text_before_registration() {

    if ( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) == get_permalink( get_the_ID() ) ) {
      echo '<p>Créez un compte et recevez des remises en argent à partir de votre 5ème commande.</p>';
    }
  }

  /* ------ Add text before login form on thank you page ------ */
  public static function pq_add_loyalty_text_before_thankyou_registration() {

    if ( get_permalink( get_the_ID() ) == wc_get_checkout_url() ) {
      echo '<p>Utilisez la même adresse de messagerie que votre commande pour l\'attacher à votre compte.</p>';
    }
  }

  /* ----- Redirect login to my account wishlist on login ------ */
  public static function pq_redirect_loyalty_login( $redirect ) {
    $current_url = get_site_url() . $_SERVER[ 'REQUEST_URI' ];

    $myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
    $myaccount_page_url = get_permalink( $myaccount_page_id );

    $myaccount_default_page_url = wc_get_account_endpoint_url( 'favoris' );

    if ( strpos( $current_url, 'order-received' ) !== false || $current_url == $myaccount_page_url ) {
      return $myaccount_default_page_url;
    } else {
      return $redirect;
    }
  }

  /**
   * Frontend on checkout and thank you page
   * 
   */

  /* ----- Show partial paiement form after tip ----- */
  public static function pq_mycred_part_woo() {
    wc_get_template( 'checkout/mycred-partial-payments.php', array( 'checkout' => WC()->checkout() ) );
  }

  /* ----- Show points earned on thank you page ----- */
  public static function pq_mycred_display_points_thankyou( $order_id ) {
    if ( !function_exists( 'mycred' ) ) return;

    //Move thank you message before the points 
    add_filter( 'woocommerce_thankyou_order_received_text', 'pq_remove_thankyou_message', 10, 2 );

    function pq_remove_thankyou_message( $message, $order ) {
      $message = '';
      return $message;
    }

    echo '<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">' . esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ) . '</p>';

    //Get order info
    $order = wc_get_order( $order_id );
    $user_id = $order->get_user_id();
    $reward = PQ_loyalty_rewards::pq_get_reward_from_order( $order );
    $img_width = '45px';

    //Message for logged in users
    if ( !empty( $user_id ) ) {
      if ( pq_has_main_badge($user_id) ) {

        echo '<h2 class="myloyalty-main-message"> ' . PQ_loyalty_helper::get_image( $img_width ) . esc_html__( ' Vous avez gagné ' ) . wc_price($reward) . esc_html__( ' de rabais.' ) . '</h2><p class="myloyalty-additional-message">' . esc_html__( 'Utilisez-les sur la page de paiement lors de votre prochaine commande.') . '</p>';
        
      } else {
        $minimum_orders = 5;

        $user_orders = wc_get_orders( array(
            'limit' => $minimum_orders,
            'customer_id' => $user_id,
        ));
        
        $count_user_orders = count( $user_orders );
        $orders_left = $minimum_orders - $count_user_orders;

        if ( $count_user_orders < 5 ) {
          echo '<h2 class="myloyalty-main-message">' . PQ_loyalty_helper::get_image( $img_width ) . esc_html__( ' Plus que ') . $orders_left . esc_html__( ' commandes avant de cumuler vos remises en argent!' ) . '</h2>';
        }
      }

    } else {
      //Show message and registration form if not logged in
      echo '<h2 class="myloyalty-main-message">' . PQ_loyalty_helper::get_image( $img_width ) . esc_html__( ' Vous auriez pu gagner ' ) . wc_price($reward) . esc_html__( ' de remise en argent.' ) . '</h2>';
      echo '<p class="myloyalty-additional-message">' . esc_html__( 'Créez un compte maintenant pour recevoir des remises en argent à partir de votre 5ème commande.' ) . '</p>';
      echo '<p class="myloyalty-additional-message">' . esc_html__( 'Vous avez déjà un compte? Connectez-vous maintenant pour attacher la commande à votre compte et/ou récupérer votre remise.' ) . '</p>';

      echo '<div id="my-thankyou-registration">';
      echo wc_get_template( 'myaccount/form-login.php' );
      echo '</div>';
    }
  }
}
