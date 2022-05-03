<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Add custom meta to the marketplace
 */
class PQ_shop_content {
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

    //Load astra woocommerce compatibility later to work with ajax loaded products
    add_action( 'after_setup_theme', array( $this, 'pq_rehook_astra_woocommerce') );

    //Display custom product quantities
    add_action( 'astra_woo_shop_add_to_cart_before', array( $this, 'pq_add_product_quantity_not_variable' ) );
    add_action( 'pq_before_variation_select', array( $this, 'pq_add_product_quantity_variable' ) );

    //Display suppliers
    add_action( 'astra_woo_shop_title_before', array( $this, 'pq_add_product_supplier' ), 15 );

    //Display food restrictions
    add_action( 'woocommerce_before_shop_loop_item', array( $this, 'pq_add_product_food_restrictions' ) );

    //Display icons on shop page thumbnails
    add_action( 'woocommerce_before_shop_loop_item', array( $this, 'pq_add_product_quebec_icon' ) );
    add_action( 'woocommerce_before_shop_loop_item', array( $this, 'pq_add_product_double_points_icon' ) );

    //Display variation prices correctly
    add_action( 'init', array( $this, 'pq_move_variable_price_above_dropdown' ), 5 );
    add_filter( 'woocommerce_variable_price_html', array( $this, 'pq_remove_variable_price' ), 10, 2 );

    //Remove price of bundle product with only optional items
    add_filter ( 'woocommerce_get_price_html', array( $this, 'pq_remove_fully_optional_bundle_price'), 10, 2 );

    //Add quantity input to shop page
    add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'quantity_inputs_for_loop_ajax_add_to_cart' ), 10, 2 );
    add_action( 'wp_footer', array( $this, 'archives_quantity_fields_script' ) );
  }

  /**
   * Helper functions
   * 
   */
  public static function pq_get_quebec_icon() {
    $url = wp_get_attachment_url( 17006 );
    $image_html = '<img class="pq_country_origin_icon" src="' . $url . '">';

    return $image_html;
  }

  public static function pq_get_double_points_icon() {
    $url = wp_get_attachment_url( 27758 );
    $image_html = '<img class="pq_double_points_icon" src="' . $url . '">';

    return $image_html;
  }

  /**
   * Hooked functions
   * 
   */

  public static function pq_rehook_astra_woocommerce() {
    $astra_woocommerce = Astra_Woocommerce::get_instance();
    remove_action( 'wp', array($astra_woocommerce, 'woocommerce_init') );
    add_action( 'init', array($astra_woocommerce, 'woocommerce_init') );
		remove_action( 'wp', array($astra_woocommerce, 'shop_customization') );
		add_action( 'init', array($astra_woocommerce, 'shop_customization') );
  }

  /**
   * Add short description on ajax marketplace
   */
  public static function pq_show_excerpt_shop_page() {
    $product_id = get_the_ID();
    $product = wc_get_product( $product_id );

    if ( $product->is_type( 'simple' ) || $product->is_type( 'variable' ) ) {
      echo '<div class="ast-woo-shop-product-description"><p>' . $product->get_short_description() . '</p></div>';
    }
  }

  /**
   * Add supplier before the title
   */
  public static function pq_add_product_supplier() {
    $product_id = get_the_ID();
    $suppliers = get_the_terms( $product_id, 'product_tag' );

    if ( empty( $suppliers ) ) {
      $suppliers = get_the_terms( $product_id, 'pq_producer' );
      if ( empty( $suppliers ) ) {
        $suppliers = get_the_terms( $product_id, 'pq_distributor' );
        if ( empty( $suppliers ) ) return '';
      }
    }

    //Add a div wrapper here
    $suppliers_html = '<div class="pq_shop_supplier_wrapper">';

    foreach ( $suppliers as $key => $supplier ) {
      $supplier_archive_link = get_term_link( $supplier );
      $supplier_name = $supplier->name;

      $suppliers_html .= '<a href="' . $supplier_archive_link . '" class="pq_shop_supplier">' . $supplier_name . '</a>';

      end( $suppliers );
      if ( $key !== key( $suppliers ) ) {
        $suppliers_html .= ', ';
      }
    }

    $suppliers_html .= '</div>';
    echo $suppliers_html;
  }


  /**
   * Add product custom frontend quantity
   */
  public static function pq_add_product_quantity_not_variable() {
    $product_id = get_the_ID();
    $product = wc_get_product( $product_id );
    $product_quantity = get_post_meta( $product_id, '_frontend_quantity', true );
    $product_type = $product->get_type();

    if ( !empty( $product_quantity ) && $product_type !== 'variable') {
      $product_quantity_html = '<div class="frontend_quantity"><p>' . $product_quantity . '</p></div>';
      echo $product_quantity_html;
    }
  }


  /**
   * Add product custom frontend quantity for variable product
   */
  public static function pq_add_product_quantity_variable() {
    $product_id = get_the_ID();
    $product = wc_get_product( $product_id );
    $product_quantity = get_post_meta( $product_id, '_frontend_quantity', true );
    $product_type = $product->get_type();

    if ( !empty( $product_quantity ) && $product_type == 'variable') {
      $product_quantity_html = '<div class="frontend_quantity"><p>' . $product_quantity . '</p></div>';
      echo $product_quantity_html;
    }
  }



    /**
     * Displaying food restrictions
     */
    public static function pq_add_product_food_restrictions() {
      $product_id = get_the_ID();
      $product = wc_get_product( $product_id );
        $food_restrictions = get_the_terms($product_id, 'food_restrictions');

        $hide_vege_categories = array(
            171, //Paniers
            173, //Fruits
            177, //Légumes
            246, //Plantes et fleurs
            253, //Beauté
            293, //Fromages et produits laitiers
            297, //Thé et café
            299, //Épices du monde
            300, //Riz, graines et légumineuses
            303, //Tofu et alternatives vegan
            308, //Boisson
            309, //Boulangerie et patisserie
            312, //Miel, confiture et tartinade
            313, //Condiments
            314, //Huile et vinaigre
            315, //Olives et légumes marinés
            316, //Oeufs
            317, //Céréales
            319, //Autre
            343, //Farine et sucre
            358, //Maison
            437, //Fines herbes
            679, //Pâtes sèches
            724, //Ingrédients pour la pâtisserie
            725, //Noix salées
            726, //Fruits secs et noix sucrées
            727, //Sels et poivres
        );

        $hide_vege_tag = false;
        if( ! empty(array_intersect($product->get_category_ids(), $hide_vege_categories)) ) {
            $hide_vege_tag = true;
        }
        
        if ( ! empty ($food_restrictions) ) {
            $food_restriction_html = '<div class="food_restrictions_container">';
            $i = 1;
            foreach ($food_restrictions as $food_restriction) {

                $food_restriction_name = $food_restriction->name;
                $food_restriction_slug = $food_restriction->slug;
                $food_restriction_id = $food_restriction->term_id;

                if ( ! ($food_restriction_id == 258 && $hide_vege_tag) ) {

                    //Add class to inner food restrictions to add a right border
                    $food_restriction_border = '';
                    if ( count($food_restrictions) > 1 && count($food_restrictions) != $i ) {
                        $food_restriction_border = 'food_restriction_with_border';
                    }

                    $food_restriction_html .= '<div class="food_restriction_name food_restriction_' . $food_restriction_slug . ' ' . $food_restriction_border . '">' . $food_restriction_name . '</div>';

                    $i++;
                }
            }
            $food_restriction_html .= '</div>';

            if ( $i > 1 ) { 
              echo $food_restriction_html;
            }
        }
    }


  /**
   * Add Quebec icon over product thumbnail
   */
  public static function pq_add_product_quebec_icon() {
    $product_id = get_the_ID();
    $country_origins = get_the_terms( $product_id, 'country_origin' );

    if ( !empty( $country_origins ) ) {
      foreach ( $country_origins as $country_origin ) {
        $country_origin_name = $country_origin->name;
        if ( $country_origin_name == 'Québec' ) {
          echo $this->pq_get_quebec_icon();
        }
      }
    }
  }

  /**
   * Add double points icon over product thumbnail
   */
  public static function pq_add_product_double_points_icon() {
    $product_id = get_the_ID();
    $has_double_points = get_post_meta( $product_id, '_pq_double_points', true );

    if ( !empty( $has_double_points ) ) {
      echo $this->pq_get_double_points_icon();
    }
  }

  /**
   * Move JS variation price above dropdown menu
   */
  public static function pq_move_variable_price_above_dropdown() {
    remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation' );
    add_action( 'woocommerce_before_variations_form', 'woocommerce_single_variation' );
  }

  /**
   * Remove basic variation price for products with a price range
   */
  public static function pq_remove_variable_price( $price, $product ) {
    $prices = $product->get_variation_prices( true );

    if ( count( array_unique( $prices[ 'price' ] ) ) !== 1 ) {
      return '';
    }

    return $price;
  }

  /**
   * Remove price of bundle product with only optional items
   */
  public static function pq_remove_fully_optional_bundle_price( $price, $product ) {
    $product_id = $product->get_id();

    if ( $product->is_type('bundle') ) {
      $raw_price = get_post_meta( $product_id, '_wc_pb_base_price', true);
      
      if ( empty($raw_price) ) {
        $price = '';
      }
    }

    return $price;
  }

  /**
   * Add AJAX add to cart button with quantity
   */
  public static function quantity_inputs_for_loop_ajax_add_to_cart( $html, $product ) {
    $product_id = $product->get_id();
    $button_text = '';

  if ( has_term( array(171 /*Paniers*/, 153 /*produit unité*/), 'product_cat', $product_id ) ) {
      if ( $product && $product->is_type( array( 'simple', 'bundle' ) ) && $product->is_purchasable() && $product->is_in_stock() && !$product->is_sold_individually() ) {
        // Get the necessary classes
        $class = implode( ' ', array_filter( array(
          'button',
          'product_type_' . $product->get_type(),
          $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
          $product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : '',
        ) ) );

        // Embedding the quantity field to Ajax add to cart button
        $html = sprintf(
          '<div class="qty-and-add-to-cart">%s<a rel="nofollow" href="%s" data-quantity="%s" data-product_id="%s" data-product_sku="%s" class="%s">%s</a></div>',
          woocommerce_quantity_input( array(), $product, false ),
          esc_url( $product->add_to_cart_url() ),
          esc_attr( isset( $quantity ) ? $quantity : 1 ),
          esc_attr( $product->get_id() ),
          esc_attr( $product->get_sku() ),
          esc_attr( isset( $class ) ? $class : 'button' ),
          $button_text //replaces the commented argument below
          //esc_html( $product->add_to_cart_text() )
        );
      }
    } elseif ( has_term( array(414 /*recette*/, 571 /*kit*/), 'product_cat', $product_id ) ) {
      $url = get_permalink( $product_id );
      $html = '<a class="button product_type_' . $product->get_type() . ' add_to_cart_button pq_recipe" href="' . $url . '">Découvrir</a>';
    }

    return $html;
  }

  //Javascript associated to AJAX quantity add to cart
  public static function archives_quantity_fields_script() { ?>
    <script type='text/javascript'>
      jQuery(function($) {
        // Update data-quantity
        $(document.body).on('click input', 'input.qty', function() {
          $(this).parent().parent().find('a.ajax_add_to_cart').attr('data-quantity', $(this).val());

          // (optional) Removing other previous "view cart" buttons
          $(".added_to_cart").remove();
        });
      });
    </script><?php
  }
}