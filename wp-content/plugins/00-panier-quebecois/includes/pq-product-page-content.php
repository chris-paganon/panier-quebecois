<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Add custom meta to the product pages
 */

class PQ_product_page_content {
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
        //Add shortcodes
        add_action( 'init', array($this, 'pq_register_shortcodes') );

        //Add custom location to product recommendations
        add_filter( 'woocommerce_prl_location_product_details_actions', array($this, 'pq_add_custom_product_recommendation_location'), 10, 2 );

        //Remove bundle item link in title of product add to cart form
        add_filter('woocommerce_bundled_item_link_html', array($this, 'pq_remove_bundle_item_link'), 10, 3);
        add_action( 'woocommerce_bundled_item_details', array($this, 'pq_add_optional_bundle_item_title'), -5, 2 );
        add_action( 'woocommerce_bundled_item_details', array($this, 'pq_add_bundled_item_quantity'), 16, 2 );

        //Modify add-to-cart text for special product categories
        add_filter( 'woocommerce_product_single_add_to_cart_text', array($this, 'pq_add_to_cart_single_text'), 10, 2 );

        //Fix bundle add to cart with several defaulted variations
        add_filter( 'woocommerce_bundle_front_end_params', array( $this, 'pq_fix_bundle_with_variations_add_to_cart'), 10, 1 );

        //Display food restrictions on product thumbnail
        add_action( 'woocommerce_product_thumbnails', array('PQ_shop_content', 'pq_add_product_food_restrictions') );
    }

    /**
     * Register the shortcodes
     */
    public static function pq_register_shortcodes() {
        add_shortcode( 'basket_desciption', array( $this, 'pq_basket_desciption_fct') );
        add_shortcode( 'pq_product_taxonomies', array( $this, 'pq_product_taxonomies_fct') );
        add_shortcode( 'pq_product_additional_info', array( $this, 'pq_product_additional_info_fct') );
        add_shortcode( 'pq_product_cross_sell', array( $this, 'pq_product_cross_sell_fct') );
        add_shortcode( 'pq_product_single_meta', array( $this, 'pq_product_single_meta_function') );
        add_shortcode( 'pq_stock_restant', array( $this, 'pq_stock_left_fct') );
    }


    /**
     * Change add to cart text for some product categories on the product page
     */
    public static function pq_add_to_cart_single_text( $button_text, $product ) {

        $page_id = get_queried_object_id();
        
        if ( $page_id != 6720 && $page_id != 32285 && !is_search() ) {
            if ( has_term('carte-cadeau', 'product_cat', $product->get_id() ) ) {       
                $button_text =  ' Offrir un cadeau';
            } elseif ( has_term('panier-decouverte', 'product_cat', $product->get_id() ) ) {       
                $button_text =  'Commandez maintenant';
            } else {
                $button_text =  '';
            }
        } else {
            $button_text = '';
        }

        return $button_text;
    }


    /**
     * 
     */
    public static function pq_stock_left_fct() {
        $product_id = get_the_ID();
        $product = wc_get_product($product_id);
        $stock_quantity_html = '';

        if ( $product->get_type() != 'bundle' ) {
            $stock_quantity = $product->get_stock_quantity();
        } else {
            $stock_quantity = get_post_meta($product_id, '_wc_pb_bundle_stock_quantity', true);
        }

        if ( ! empty($stock_quantity) ) {
            $stock_quantity_html = 'Il en reste <span>'. $stock_quantity . '</span>';
        }
        return $stock_quantity_html;
    }


    /**
     * 
     * Helpers
     * 
     */

    /**
     * Get product taxonomy names in HTML
     */
    public static function pq_get_terms_html($terms) {
        
        $taxonomy_html = '';
        
        foreach ($terms as $term_key => $term) {
 
            $term_name = $term->name;
            $taxonomy_html .= $term_name;

            end($terms);
            if ( $term_key !== key($terms) ) {
                $taxonomy_html .= ', ';
            }
        }

        return $taxonomy_html;
    }


    /**
     * Get product taxonomy names in HTML
     */
    public static function pq_get_terms_link_html($terms) {
        
        $taxonomy_html = '';
        
        foreach ($terms as $term_key => $term) {
 
            $term_name = $term->name;
            $term_archive_link = get_term_link( $term );

            $taxonomy_html .= '<a href="' . $term_archive_link . '" class="">' . $term_name . '</a>';

            end($terms);
            if ( $term_key !== key($terms) ) {
                $taxonomy_html .= ', ';
            }
        }

        return $taxonomy_html;
    }


    /**
     * Get product taxonomy block in HTML
     */
    public static function pq_get_taxonomy_html($taxonomy_key, $product_id) {

        $terms = get_the_terms($product_id, $taxonomy_key);

        if ( empty($terms) ) {

            if ( $taxonomy_key == 'product_tag' ) {
                $taxonomy_key = 'pq_producer';
                $terms = get_the_terms($product_id, $taxonomy_key);

                if ( empty($terms) ) return '';

            } else {
                return '';
            }
        }
        $taxonomy = get_taxonomy($taxonomy_key);

        if ( count($terms) == 1 ) {
            $taxonomy_labels = get_taxonomy_labels($taxonomy);
            $taxonomy_name = $taxonomy_labels->singular_name;
        } else {
            $taxonomy_name = $taxonomy->label;
        }

        if ( $taxonomy_key == 'product_tag' || $taxonomy_key == 'pq_producer' ) {
            $taxonomy_terms = self::pq_get_terms_link_html($terms);
        } else {
            $taxonomy_terms = self::pq_get_terms_html($terms);
        }

        $taxonomy_html = '<div class="pq_product_terms_wrapper"><h4 class="pq_product_terms_name">' . $taxonomy_name . ': </h4><p class="pq_product_terms">' . $taxonomy_terms . '</p></div>';
        
        return $taxonomy_html;
    }


    /**
     * Get product taxonomy image URl
     */
    public static function pq_get_taxonomy_img_url($taxonomy_key, $product_id) {
        
        switch ($taxonomy_key) {
			case 'product_tag':
				$url = wp_get_attachment_url(36483);
				break;
			case 'country_origin':

                //Display different icon if from Quebec
                $terms = get_the_terms($product_id, $taxonomy_key);
                if ( ! empty($terms) ) {
                    $is_from_qc = false;

                    foreach ($terms as $term) {
                        if ($term->name == 'Québec') {
                            $is_from_qc = true;
                        }
                    }

                    if ($is_from_qc) {
                        $url = wp_get_attachment_url(32659);
                    } else {
                        $url = wp_get_attachment_url(36860);
                    }
                }
				break;
			case 'pq_harvest':
				$url = wp_get_attachment_url(36481);
				break;
			default:
				$url = wp_get_attachment_url(36483);
		}

		return $url;
    }


    /**
     * Get product taxonomy image
     */
    public static function pq_get_taxonomy_img($taxonomy_key, $product_id) {

        $image_url = PQ_product_page_content::pq_get_taxonomy_img_url($taxonomy_key, $product_id);

        $img = '<img src="' . $image_url . '" class ="pq_product_taxonomy_img ' . $taxonomy_key . '_img ">';
		return $img;
    }


    /**
     * Check if meta or taxonomy description is empty
     */
    public static function pq_has_meta_or_tax_description( $slug, $product_id, $type = 'meta') {
        switch ($type) {
            case 'meta':
                $meta = get_post_meta( $product_id, $slug, true );
                if ( empty($meta) ) return false;
                break;
            case 'taxonomy':
                $terms = get_the_terms($product_id, $slug);
                if ( empty($terms) ) return false;
                foreach ( $terms as $term ) {
                    $term_description = term_description($term->term_id);
                    if( empty($term_description) ) return false;
                }
                break;
        }

        return true;
    }


    /**
     * Get product additional info title in HTML
     */
    public static function pq_get_additional_info_title_html( $slug, $name, $product_id, $position, $type = 'meta' ) {
        
        $has_meta_or_tax_description = self::pq_has_meta_or_tax_description($slug, $product_id, $type);
        if ( ! $has_meta_or_tax_description ) return '';

        if ( $position == 1 ) {
            $active = 'pq_active';
        } else {
            $active = '';
        }

        $title_html = '<h4 class="pq_additonal_info_title ' . $slug . ' ' . $active . '">' . $name . '</h4>';
        return $title_html;
    }


    /**
     * Get product additional info image URL
     */
    public static function pq_get_additional_info_img_url($slug, $product_id) {
        switch ($slug) {
			case '_pq_instructions':
				$url = wp_get_attachment_url(36482);
				break;
			case 'product_tag':
				$url = wp_get_attachment_url(36480);
				break;
			case 'pq_producer':
				$url = wp_get_attachment_url(36480);
				break;
			case '_pq_ingredients':
				$url = wp_get_attachment_url(32660);
				break;
            default:
				$url = wp_get_attachment_url(32660);
		}

		return $url;
    }


    /**
     * Get product additional info image
     */
    public static function pq_get_additional_info_img($slug, $product_id) {

        $image_url = PQ_product_page_content::pq_get_additional_info_img_url($slug, $product_id);

        $img = '<img src="' . $image_url . '" class ="pq_product_meta_img ' . $slug . '_img ">';
		return $img;
    }


    /**
     * Get product additional info text content in HTML
     */
    public static function pq_get_additional_info_html($slug, $name, $product_id, $type = 'meta') {
        $has_meta_or_tax_description = self::pq_has_meta_or_tax_description($slug, $product_id, $type);
        if ( ! $has_meta_or_tax_description ) return '';

        switch ( $type ) {
            case 'meta':
                $info_raw = get_post_meta( $product_id, $slug, true );
                break;
            case 'taxonomy':
                $terms = get_the_terms($product_id, $slug);
                $info_html = '';
                foreach ( $terms as $term ) {
                    $term_description = term_description($term->term_id);
                    $info_raw .= $term_description;
                }
        }

        $info_html = '<div class="pq_product_meta">' . $info_raw . '</div>';
        return $info_html;
    }


    /**
     * Get product additional info HTML block
     */
    public static function pq_get_additional_info_block_html( $slug, $name, $product_id, $position, $type = 'meta' ) {

        $has_meta_or_tax_description = self::pq_has_meta_or_tax_description($slug, $product_id, $type);
        if ( ! $has_meta_or_tax_description ) return '';

        if ( $position == 1 ) {
            $active = 'pq_active';
        } else {
            $active = '';
        }

        $additional_info_block_html = '<div class="pq_additional_info_block_wrapper ' . $slug . ' ' . $active . '">';
        
        $additional_info_block_html .= '<div class="pq_additional_info_title_block_wrapper">';
        $additional_info_block_html .= PQ_product_page_content::pq_get_additional_info_img($slug, $product_id);
        $additional_info_block_html .= PQ_product_page_content::pq_get_additional_info_title_html($slug, $name, $product_id, $position, $type);
        $additional_info_block_html .= '</div>';

        $additional_info_block_html .= PQ_product_page_content::pq_get_additional_info_html($slug, $name, $product_id, $type);

        $additional_info_block_html .= '</div>';

        return $additional_info_block_html;
    }



    /**
     * 
     * Metas and taxonomies shortcodes
     * 
     */

    /**
     * Products taxonomies shortcode function
     */
    public static function pq_product_taxonomies_fct() {

        $product_id = get_the_ID();

        $taxonomies_to_display = array(
            'country_origin',
            'pq_harvest',
            'product_tag',
        );

        $product_meta_html = '<div class="pq_product_taxonomies_wrapper">';

        foreach ( $taxonomies_to_display as $taxonomy_key ) {

            $terms = get_the_terms($product_id, $taxonomy_key);

            if ( empty($terms) ) {
                if ( $taxonomy_key == 'product_tag' ) {
                    $taxonomy_key = 'pq_producer';
                    $terms = get_the_terms($product_id, $taxonomy_key);
                }
            }

            if ( ! empty($terms) ) {

                $product_meta_html .= '<div class="pq_product_taxonomy_wrapper">';

                $product_meta_html .= $this->pq_get_taxonomy_img($taxonomy_key, $product_id);
                $product_meta_html .= $this->pq_get_taxonomy_html($taxonomy_key, $product_id);

                $product_meta_html .= '</div>';
            }
        }

        $product_meta_html .= '</div>';
        
        return $product_meta_html;
    }


    /**
     * Products addtional information shortcode function
     */
    public static function pq_product_additional_info_fct() {

        $product_id = get_the_ID();

        $info_to_display = array(
            array(
                'slug' => '_pq_instructions',
                'name' => 'Conseils de préparation',
                'type' => 'meta',
            ),
            array(
                'slug' => 'product_tag',
                'name' => 'Marchand',
                'type' => 'taxonomy',
            ),
            array(
                'slug' => '_pq_ingredients',
                'name' => 'Astuces/ingrédients',
                'type' => 'meta',
            ),
        );

        //Don't display anything if all metas are empty
        $has_additional_info = false;
        foreach ( $info_to_display as $key => $info_args ) {

            $slug = $info_args['slug'];
            switch ($info_args['type']) {

                case 'meta':
                    $meta = get_post_meta( $product_id, $slug, true );
                    if (! empty($meta) ) {
                        $has_additional_info = true;
                    } else {
                        unset($info_to_display[$key]);
                    }
                    break;

                case 'taxonomy':
                    $terms = get_the_terms($product_id, $slug);
                    
                    if ( empty($terms) ) {
                        if ( $slug == 'product_tag' ) {
                            $slug = 'pq_producer';
                            $terms = get_the_terms($product_id, $slug);
                            $info_to_display[$key]['slug'] = 'pq_producer';
                            $info_to_display[$key]['name'] = 'Producteur/Fabricant';
                        }
                    }
                    
                    if (! empty($terms) ) {
                        foreach ( $terms as $term ) {
                            $term_description = term_description($term->term_id);
                            if( ! empty($term_description) ) {
                                $has_additional_info = true;
                            } else {
                                unset($info_to_display[$key]);
                            }
                        }
                    } else {
                        unset($info_to_display[$key]);
                    }
                    break;
            }
        }

        if ( ! $has_additional_info ) return;
        
        //Display wrapper block
        $additional_info_html = '<div class="pq_product_additional_info_wrapper">';
        
        //Display the menu
        $additional_info_html .= '<nav class="pq_product_additional_info_menu">';
        $position = 1;
        foreach ( $info_to_display as $key => $info_args ) {
            $additional_info_html .= $this->pq_get_additional_info_title_html( $info_args['slug'], $info_args['name'], $product_id, $position, $info_args['type'] );
            $position++;
        }
        $additional_info_html .= '</nav>';

        //Display the content
        $position = 1;
        foreach ( $info_to_display as $key => $info_args ) {
            $additional_info_html .= $this->pq_get_additional_info_block_html( $info_args['slug'], $info_args['name'], $product_id, $position, $info_args['type'] );
            $position++;
        }

        $additional_info_html .= '</div>';

        return $additional_info_html;
    }


    /**
     * Single meta or taxonomy shortcode display shortcode funtion
     */
    public static function pq_product_single_meta_function($atts) {
        extract(shortcode_atts(array(
            'meta_key' => 0,
            'meta_name' => 0,
            'type' => 0,), 
            $atts)
        );
        
        $product_id = get_the_ID();

        if ( $meta_key === 0 || $type === 0 || empty($product_id) ) return '';

        if ( $type == 'meta' && $meta_name !== 0 ) {
            $meta_html = $this->pq_get_additional_info_html( $meta_key, $meta_name, $product_id );
        } elseif ( $type == 'taxonomy' ) {
            $meta_html = $this->pq_get_taxonomy_html( $meta_key, $product_id );
        } else {
            return '';
        }

        return $meta_html;        
    }



    /**
     * 
     * Recommendations
     * 
     */
 
    /**
     * Add a custom location to woocommerce product recommendations
     */
    public static function pq_add_custom_product_recommendation_location($locations, $locations_obj) {
        $locations['pq_custom_product_recommendation_location'] = array(
            'id'              => 'pq_custom_location',
            'label'           => __( 'PQ Location', 'woocommerce-product-recommendations' ),
            'priority'        => 1000
        );

        return $locations;
    }


    /**
     * Setup the custom woocommerce product recommendations location in a shortcode
     */
    public static function pq_product_cross_sell_fct() {
        do_action('pq_custom_product_recommendation_location');
    }



    /**
     * 
     * Bundles
     * 
     */

    /**
     * Basket description shortcode function
     */
    public static function pq_basket_desciption_fct($atts) {

        extract(shortcode_atts(array(
            'basket_id' => 1,
            ), $atts));

        $description_string = '';

        $bundled_products = WC_PB_DB::query_bundled_items( array(
            'return' => 'objects',
            'bundle_id' => $basket_id,
        ));

        if ( $basket_id != 1 ) {

            $description_string = '<ul>';

            foreach ($bundled_products as $bundle_item) {
                $bundle_item_data = $bundle_item->get_data();
                $bundle_item_meta_data = $bundle_item->get_meta_data();
                $product_id = $bundle_item_data['product_id'];
                $is_optional = $bundle_item_meta_data['optional'];

                if ( $is_optional !== 'yes' ) {
                    $product = wc_get_product($product_id);
                    $product_name = sanitize_text_field( $product->get_name() );
                    $product_short_desc = sanitize_text_field( $product->get_short_description() );

                    $product_quantity = sanitize_text_field( get_post_meta($product_id, '_frontend_quantity', true) );
                    
                    if ( $bundle_item_meta_data['override_variations'] === 'yes' ) {
                        $variation_slug = reset($bundle_item_meta_data['default_variation_attributes']);
                        $variation_term_key = array_key_first($bundle_item_meta_data['default_variation_attributes']);

                        $attribute_terms = get_terms($variation_term_key);
                        foreach ( $attribute_terms as $attribute_term ) {
                            if ( $attribute_term->slug == $variation_slug ) {
                                $variation_name = $attribute_term->name;
                            }
                        }

                        if ( empty($product_quantity) ) {
                            $product_quantity = $variation_name;
                        } else {
                            $product_quantity .= ' ' . $variation_name;
                        }
                    }

                    $description_string .= '<li>' . $product_name . '&nbsp;<i>' . $product_quantity . '</i> <i>' . $product_short_desc . '</i></li>';
                }
            }

            $description_string .= '</ul>';
        }

        return $description_string;
    }


    /**
     * Add title to product bundles before options
     */
    public static function pq_add_optional_bundle_item_title( $current_bundled_item, $product ) {

        $product_id = $product->get_id();
        $bundled_items = WC_PB_DB::query_bundled_items( array(
            'return' => 'objects',
            'bundle_id' => $product_id,
        ));

        $previous_bundled_item_menu_order = 99;

        foreach ( $bundled_items as $bundled_item ) {

            $bundled_item_id = $bundled_item->get_id();
            $bundled_item_menu_order = $bundled_item->get_menu_order();
            $is_bundled_item_optional = $bundled_item->get_meta_data()['optional'];

            if ( $is_bundled_item_optional === 'yes' && $bundled_item_menu_order < $previous_bundled_item_menu_order ) {
                $first_optional_bundled_item_id = $bundled_item_id;
                $previous_bundled_item_menu_order = $bundled_item_menu_order;
            }

            $is_all_optional = true;
            if ( $is_bundled_item_optional !== 'yes' ) {
                $is_all_optional = false;
            }
        }

        if ( $current_bundled_item->is_optional() && $current_bundled_item->get_id() === $first_optional_bundled_item_id && !$is_all_optional ) {
            echo '<h3 class="pq_optional_bundled_items_title">' . esc_html( 'Ingrédients optionnels:' ) . '</h3>';
        }
    }


    /**
     * Add bundled item custom frontend quantity
     */
    public static function pq_add_bundled_item_quantity( $bundled_item, $product ) {
        echo '<div class="frontend_quantity_wrapper">';

        $bundled_item_id = $bundled_item->get_product_id();
        $product_quantity = get_post_meta( $bundled_item_id, '_frontend_quantity', true );

        if ( ! empty($product_quantity)) {
            $product_quantity_html = '<div class="frontend_quantity"><p>' . $product_quantity . '</p></div>';
            echo $product_quantity_html;
        }

        //Close the product quantity container
        echo '</div>';
    }


    /**
     * Remove bundle item link in title of product add to cart form
     */
    public static function pq_remove_bundle_item_link($html_link, $bundled_item, $bundle) {
        return '';
    }

    /**
     * Fix bundle add to cart with several defaulted variations
     */
    public static function pq_fix_bundle_with_variations_add_to_cart( $params ) {
        unset($params['i18n_select_options']);
        return $params;
    }
}