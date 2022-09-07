<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class PQ_products_meta {

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
		//Add custom metas
		add_action( 'woocommerce_product_options_pricing', array($this, 'myfct_add_custom_price') );
		add_action( 'woocommerce_product_options_sku', array($this, 'myfct_add_custom_inventory') );
		add_action( 'woocommerce_product_options_stock_status', array($this, 'pq_add_custom_stock') );

		add_action( 'woocommerce_variation_options_pricing', array($this, 'myfct_add_custom_price_variable'), 10, 3 );
		add_action( 'woocommerce_variation_options' , array($this, 'myfct_add_custom_short_name_variable'), 10, 3 );

		//Save custom metas
		add_action( 'woocommerce_process_product_meta', array($this, 'myfct_save_custom_product_data'), 10, 1 );
		add_action( 'woocommerce_save_product_variation', array($this, 'myfct_save_custom_variable_product_data'), 10, 1 );
		
		//Modify default product tags labels
		add_filter( 'woocommerce_taxonomy_args_product_tag', array($this, 'pq_custom_wc_taxonomy_args_product_tag') );
		
		//Register taxonomies
		add_action( 'init', array($this, 'myfct_producer_taxonomy') );
		add_filter( 'dgwt/wcas/indexer/taxonomies', array($this, 'pq_producer_taxonomy_in_fibosearch'), 10, 1 );
		add_action( 'init', array($this, 'myfct_distributor_taxonomy') );
		add_action( 'init', array($this, 'myfct_country_origin_taxonomy') );
		add_action( 'init', array($this, 'pq_harvest_taxonomy') );
		add_action( 'init', array($this, 'pq_commercial_zone_taxonomy') );
		add_action( 'init', array($this, 'myfct_bought_from_taxonomy') );
		add_action( 'init', array($this, 'myfct_food_restrictions_taxonomy') );
		add_action( 'init', array($this, 'pq_collection_taxonomy') );
		add_action( 'init', array($this, 'pq_inventory_type_taxonomy') );

		//Add custom descriptions
		add_action( 'add_meta_boxes', array($this, 'pq_add_meta_boxes') );

		//Save custom descriptions
		add_action( 'woocommerce_admin_process_product_object', array($this, 'pq_save_custom_meta_boxes'), 10, 1 );

		//Add image meta to sellers and producers
		add_action( 'admin_enqueue_scripts', array($this, 'load_media_image_marchand_and_producer') );

		add_action( 'product_tag_edit_form_fields', array($this, 'update_marchand_and_producer_image'), 10, 2 );
		add_action( 'pq_producer_edit_form_fields', array($this, 'update_marchand_and_producer_image'), 10, 2 );

		add_action( 'edited_product_tag', array($this, 'updated_marchand_and_producer_image'), 10, 2 );
		add_action( 'edited_pq_producer', array($this, 'updated_marchand_and_producer_image'), 10, 2 );

		add_filter( 'manage_edit-product_tag_columns', array($this, 'display_marchand_and_producer_image_column_heading') ); 
		add_filter( 'manage_edit-pq_producer_columns', array($this, 'display_marchand_and_producer_image_column_heading') ); 

		add_action( 'manage_product_tag_custom_column', array($this, 'display_marchand_and_producer_image_column_value') , 10, 3);
		add_action( 'manage_pq_producer_custom_column', array($this, 'display_marchand_and_producer_image_column_value') , 10, 3);

		//Add seller email meta
		add_action( 'product_tag_edit_form_fields', array($this, 'pq_update_seller_contact_info'), 10, 2 );
		add_action( 'edited_product_tag', array($this, 'pq_updated_seller_contact_info'), 10, 2 );
	}

	/**
	 * Helper functions
	 * 
	 */

	/**
	 * All custom input arguments in one array
	 */

	public static function pq_get_all_custom_args() {

		$all_custom_meta_input_args = array(
			array(
				'position'              => 'price',
				'in_variable'           => true,
				'input_args'            => array(
					'id'                    => '_purchasing_price',
					'label'                 => __( 'Prix d\'achat' ) . ' (' . get_woocommerce_currency_symbol() . ')',
					'data_type'             => 'price',
					'woocommerce_wp'        => '_text_input',
				),
			),
			array(
				'position'              => 'price',
				'in_variable'           => true,
				'input_args'            => array(
					'id'                    => '_market_price',
					'label'                 => __( 'Prix marchand' ) . ' (' . get_woocommerce_currency_symbol() . ')',
					'data_type'             => 'price',
					'woocommerce_wp'        => '_text_input',
				),
			),
			array(
				'position'              => 'price',
				'in_variable'           => true,
				'input_args'            => array(
					'id'                    => '_price_per_kg',
					'label'                 => __( 'Prix d\'achat au kg ($/kg)' ),
					'data_type'             => 'price',
					'woocommerce_wp'        => '_text_input',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => true,
				'input_args'            => array(
					'id'                    => '_short_name',
					'label'                 => '<abbr title="' . esc_attr__( 'Nom pour les étiquettes', 'woocommerce' ) . '">' . esc_html__( 'Nom court' ) . '</abbr>',
					'woocommerce_wp'        => '_text_input',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => true,
				'input_args'            => array(
					'id'                    => '_pq_operation_stock',
					'label'                 => __( 'Stock en opération' ),
					'woocommerce_wp'        => '_text_input',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => false,
				'input_args'            => array(
					'id'                    => '_pq_reference',
					'label'                 => '<abbr title="' . esc_attr__( 'Nom de référence chez le fournisseur', 'woocommerce' ) . '">' . esc_html__( 'Rérérence fournisseur' ) . '</abbr>',
					'woocommerce_wp'        => '_text_input',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => false,
				'input_args'            => array(
					'id'                    => '_pq_double_points',
					'label'                 => __( 'Points de fidélisations doublés' ),
					'cbvalue'               => 1,
					'woocommerce_wp'        => '_checkbox',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => false,
				'input_args'            => array(
					'id'                    => '_pq_new',
					'label'                 => __( 'Nouveauté' ),
					'cbvalue'               => 1,
					'woocommerce_wp'        => '_checkbox',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => false,
				'input_args'            => array(
					'id'                    => '_pq_last_chance',
					'label'                 => __( 'Bientot fini' ),
					'cbvalue'               => 1,
					'woocommerce_wp'        => '_checkbox',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => false,
				'input_args'            => array(
					'id'                    => '_pq_special_delivery',
					'label'                 => __('Livraison spéciale?'),
					'cbvalue'               => 1,
					'woocommerce_wp'        => '_checkbox',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => false,
				'input_args'            => array(
					'id'                    => '_packing_priority',
					'label'                 => __( 'Priorité d\'emballage' ),
					'type'                  => 'number',
					'custom_attributes' => array(
						'step' 	=> 'any',
					),
					'woocommerce_wp'        => '_text_input',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => true,
				'input_args'            => array(
					'id'                    => '_pq_weight',
					'label'                 => __( 'Poids' ),
					'type'                  => 'number',
					'woocommerce_wp'        => '_text_input',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => true,
				'input_args'            => array(
					'id'                    => '_lot_unit',
					'label'                 => __( 'Unité' ),
					'woocommerce_wp'        => '_text_input',
				),
			),
			array(
				'position'              => 'inventory',
				'in_variable'           => true,
				'input_args'            => array(
					'id'                    => '_lot_quantity',
					'label'                 => __( 'Quantité par lot' ),
					'type'                  => 'number',
					'woocommerce_wp'        => '_text_input',
				),
			)
		);

		return $all_custom_meta_input_args;
	}


	/**
	 * Get custom input args in clean array almost ready for woocommerce_wp_text_input & woocommerce_wp_checkbox...
	 */

	public static function pq_get_clean_input_args( $position = 'all', $is_variable = false ) {

		$custom_meta_input_args = PQ_products_meta::pq_get_all_custom_args();
		$clean_arg_inputs = array();

		foreach ( $custom_meta_input_args as $arg ) {

			$is_arg_in_variable = $arg['in_variable'];
			$arg_position = $arg['position'];
			$arg_inputs = $arg['input_args'];

			if ( $position == 'all' ) {
				$clean_arg_inputs[] = $arg_inputs;

			} elseif ( $position != 'all' && $arg_position == $position ) {

				if ( ! $is_variable ) {
					$clean_arg_inputs[] = $arg_inputs;
				} elseif ( $is_variable && $is_arg_in_variable ) {
					$clean_arg_inputs[] = $arg_inputs;
				}
			}
		}

		return $clean_arg_inputs;
	}


	/**
	* Add & save custom product metas
	* 
	*/

	/**
	 * Add custom pricing metas for simple products 
	 */
	public static function myfct_add_custom_price () {

		?>
		<div class="options_group pricing">
			<?php

			$custom_price_args = $this->pq_get_clean_input_args( 'price' );

			foreach ( $custom_price_args as $input_args ) {
				woocommerce_wp_text_input( $input_args );
			}

			?>
		</div>
		<?php
	}


	/** 
	 * Add custom inventory metas for simple products 
	 */
	public static function myfct_add_custom_inventory() {

		?>
		<div class="options_group">
			<?php

			$custom_inventory_args = $this->pq_get_clean_input_args( 'inventory' );

			foreach ( $custom_inventory_args as $input_args ) {
				$woocommerce_wp_type = array_pop($input_args);

				if ( $woocommerce_wp_type == '_text_input' ) {
					woocommerce_wp_text_input( $input_args );
				} elseif ( $woocommerce_wp_type == '_checkbox' ) {
					woocommerce_wp_checkbox( $input_args );
				}
			}

			?>
		</div>
		<?php
	}


	/**
	 * Add custom inactive checkbox below inventory management
	 */
	public static function pq_add_custom_stock() {
		?>
		<div class="options_group">
			<?php			
				woocommerce_wp_checkbox( array (
					'id'      => '_pq_inactive',
					'label'   => __('Inactif?'),
					'cbvalue' => 1,
				));
				woocommerce_wp_checkbox( array (
					'id'      => '_pq_set_auto_stock',
					'label'   => '<abbr title="' . esc_attr__( 'Le stock sera remplacé par le nombre ci-dessous après chaque fermeture des commandes' ) . '">' . esc_html__( 'Remplacer le stock automatiquement?' ) . '</abbr>',
					'cbvalue' => 1,
				));
				woocommerce_wp_text_input( array( 
					'id'      => '_pq_auto_stock_quantity',
					'label'   => __('Quantité de stock automatique'),
					'type'    => 'number',
				));
			?>
		</div>
		<?php
	}


	/**
	 * Add custom price metas for variable products
	 */
	public static function myfct_add_custom_price_variable( $loop, $variation_data, $variation ) {
		?>
		<div class="options_group form-row pricing">
			<?php

			$custom_price_args = $this->pq_get_clean_input_args( 'price', true );

			foreach ( $custom_price_args as $input_args ) {
				
				$woocommerce_wp_type = array_pop($input_args);

				if ( $woocommerce_wp_type == '_text_input' ) {
					woocommerce_wp_text_input( array(
						'id'    => $input_args['id'] . '_variable' . '[' . $variation->ID . ']',
						'label' => $input_args['label'],
						'value' => get_post_meta( $variation->ID, $input_args['id'], true ),
					));
				}
			}

			?>
		</div>
		<?php
	}

	/**
	 * Add custom short name meta for variable products
	 */
	public static function myfct_add_custom_short_name_variable( $loop, $variation_data, $variation ) {
		?>
		<div class="options_group form-row pricing">
			<?php

			$custom_price_args = $this->pq_get_clean_input_args( 'inventory', true );

			foreach ( $custom_price_args as $input_args ) {
				
				$woocommerce_wp_type = array_pop($input_args);

				if ( $woocommerce_wp_type == '_text_input' ) {
					woocommerce_wp_text_input( array(
						'id'    => $input_args['id'] . '_variable' . '[' . $variation->ID . ']',
						'label' => $input_args['label'],
						'value' => get_post_meta( $variation->ID, $input_args['id'], true ),
					));
				}
			}

			?>
		</div>
		<?php
	}


	/** 
	 * Save custom simple product metas
	 */
	public static function myfct_save_custom_product_data ($post_id) {

		$product = wc_get_product($post_id);

		$double_points = isset($_POST['_pq_double_points']) ? 1 : 0;
		$product->update_meta_data('_pq_double_points', $double_points);

		$double_points = isset($_POST['_pq_new']) ? 1 : 0;
		$product->update_meta_data('_pq_new', $double_points);

		$double_points = isset($_POST['_pq_last_chance']) ? 1 : 0;
		$product->update_meta_data('_pq_last_chance', $double_points);

		$special_delivery = isset($_POST['_pq_special_delivery']) ? 1 : 0;
		$product->update_meta_data('_pq_special_delivery', $special_delivery);

		$is_auto_stock = isset($_POST['_pq_set_auto_stock']) ? 1 : 0;
		$product->update_meta_data('_pq_set_auto_stock', $is_auto_stock);

		$inactive = isset($_POST['_pq_inactive']) ? 1 : 0;
		$stock_status = isset($_POST['_stock_status']) ? $_POST['_stock_status'] : '';
		if ( $stock_status == 'instock' ) {
			$inactive = 0;
		}
		$product->update_meta_data('_pq_inactive', $inactive);

		$custom_args = $this->pq_get_clean_input_args( 'all' );
		
		foreach ( $custom_args as $input_args ) {

			if ( $input_args['woocommerce_wp'] == '_text_input' ) {
				$custom_key = $input_args['id'];
				
				$post_data = isset($_POST[$custom_key]) ? $_POST[$custom_key] : '';
				$product->update_meta_data($custom_key, sanitize_text_field(stripslashes($post_data)));
			}
		}

		$auto_stock_qty = isset($_POST['_pq_auto_stock_quantity']) ? $_POST['_pq_auto_stock_quantity'] : '';
		$product->update_meta_data('_pq_auto_stock_quantity', sanitize_text_field(stripslashes($auto_stock_qty)));

		$product->save();
	}

	
	/**
	 * Save custom variable products metas
	 */
	public static function myfct_save_custom_variable_product_data( $post_id ) {
		
		$custom_args = $this->pq_get_clean_input_args( 'all', true );
		
		foreach ( $custom_args as $input_args ) {

			$field_value = isset($_POST[$input_args['id'] . '_variable'][ $post_id ]) ? $_POST[$input_args['id'] . '_variable'][ $post_id ] : '';
			update_post_meta( $post_id, $input_args['id'], esc_attr( $field_value ) );
		}
	}


	/**
	 * Custom taxonomies
	 * 
	 */


	/**
	 * Modify default product tags labels
	 */
	public static function pq_custom_wc_taxonomy_args_product_tag( $args ) {
		$args['label'] = __( 'Marchands', 'woocommerce' );
		$args['labels'] = array(
			'name' 				         => __( 'Marchands', 'woocommerce' ),
			'singular_name' 	         => __( 'Marchand', 'woocommerce' ),
			'menu_name'			         => _x( 'Marchands', 'Admin menu name', 'woocommerce' ),
			'separate_items_with_commas' => 'Séparer les marchands par des virgules',
			'choose_from_most_used'      => 'Choisir parmis les plus utilisés',
		);

		return $args;
	}


	/**
	 * Register producer taxonomy
	 */

	public static function myfct_producer_taxonomy() {
		$labels = array(
			'name'                       => 'Producteurs/Fabricants',
			'singular_name'              => 'Producteur/Fabricant',
			'separate_items_with_commas' => 'Séparer les fabriquants par des virgules',
			'choose_from_most_used'      => 'Choisir parmis les plus utilisés',
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_admin_column' => true,
		);

		register_taxonomy( 'pq_producer', 'product', $args );
		register_taxonomy_for_object_type( 'pq_producer', 'product' );
	}

	public static function pq_producer_taxonomy_in_fibosearch( $taxonomies ) {
		$taxonomies[] = 'pq_producer';
		return $taxonomies;
	}


	/**
	 * Register distributor taxonomy
	 */

	public static function myfct_distributor_taxonomy() {
		$labels = array(
			'name'                       => 'Distributeurs',
			'singular_name'              => 'Distributeur',
			'separate_items_with_commas' => 'Séparer les distributeurs par des virgules',
			'choose_from_most_used'      => 'Choisir parmis les plus utilisés',
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_admin_column' => true,
		);

		register_taxonomy( 'pq_distributor', 'product', $args );
		register_taxonomy_for_object_type( 'pq_distributor', 'product' );
	}


	/**
	 * Register country of origin taxonomy
	 */

	public static function myfct_country_origin_taxonomy() {
		$labels = array(
			'name'                       => 'Origine',
			'singular_name'              => 'Origine',
			'separate_items_with_commas' => 'Séparer les pays par des virgules',
			'choose_from_most_used'      => 'Choisir parmis les plus utilisés',
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_admin_column' => true,
		);

		register_taxonomy( 'country_origin', 'product', $args );
		register_taxonomy_for_object_type( 'country_origin', 'product' );
	}


	/**
	 * Register harvest time in Quebec taxonomy
	 */
	public static function pq_harvest_taxonomy() {
		$labels = array(
			'name'                       => 'Récolte au Québec',
			'singular_name'              => 'Récolte au Québec',
			'separate_items_with_commas' => 'Séparer par des virgules',
			'choose_from_most_used'      => 'Choisir parmis les plus utilisés',
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_admin_column' => true,
		);

		register_taxonomy( 'pq_harvest', 'product', $args );
		register_taxonomy_for_object_type( 'pq_harvest', 'product' );
	}


	/**
	 * Register commercial zone taxonomy
	 */
	public static function pq_commercial_zone_taxonomy() {
		$labels = array(
			'name'                       => 'Zone commerciale',
			'parent_item'                => 'Ne pas utiliser',
			'add_new_item'               => 'Ajouter nouvelle zone',
			'separate_items_with_commas' => 'Séparer par des virgules',
			'choose_from_most_used'      => 'Choisir parmis les plus utilisés',
			'most_used'                  => 'Les plus utilisés',
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
		);

		register_taxonomy( 'pq_commercial_zone', 'product', $args );
		register_taxonomy_for_object_type( 'pq_commercial_zone', 'product' );
	}


	/**
	 * Register bought from taxonomy
	 */

	public static function myfct_bought_from_taxonomy() {
		$labels = array(
			'name'                       => 'Acheté chez',
			'parent_item'                => 'Ne pas utiliser',
			'add_new_item'               => 'Ajouter nouveau vendeur',
			'separate_items_with_commas' => 'Séparer par des virgules',
			'choose_from_most_used'      => 'Choisir parmis les plus utilisés',
			'most_used'                  => 'Les plus utilisés',
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
		);

		register_taxonomy( 'pq_bought_from', 'product', $args );
		register_taxonomy_for_object_type( 'pq_bought_from', 'product' );
	}


	/**
	 * Register inventory type taxonomy
	 */

	public static function pq_inventory_type_taxonomy() {
		$labels = array(
			'name'                       => 'Type d\'inventaire',
			'parent_item'                => 'Ne pas utiliser',
			'add_new_item'               => 'Ajouter nouveau type',
			'separate_items_with_commas' => 'Séparer par des virgules',
			'choose_from_most_used'      => 'Choisir parmis les plus utilisés',
			'most_used'                  => 'Les plus utilisés',
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
		);

		register_taxonomy( 'pq_inventory_type', 'product', $args );
		register_taxonomy_for_object_type( 'pq_inventory_type', 'product' );
	}


	/**
	 * Register food restrictions taxonomy
	 */

	public static function myfct_food_restrictions_taxonomy() {
		$labels = array(
			'name'                       => 'Habitudes alimentaires',
			'singular_name'              => 'Habitude alimentaire',
			'parent_item'                => 'Ne pas utiliser',
			'add_new_item'               => 'Ajouter nouvelle habitude alimentaire',
			'separate_items_with_commas' => 'Séparer les habitudes par des virgules',
			'choose_from_most_used'      => 'Choisir parmis les plus utilisés',
			'most_used'                  => 'Les plus utilisés',
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
		);

		register_taxonomy( 'food_restrictions', 'product', $args );
		register_taxonomy_for_object_type( 'food_restrictions', 'product' );
	}


	/**
	 * Register collections taxonomy to display products on certain pages
	 */

	public static function pq_collection_taxonomy() {
		$labels = array(
			'name'                       => 'Collections',
			'singular_name'              => 'Collection',
			'parent_item'                => 'Ne pas utiliser',
			'add_new_item'               => 'Ajouter nouvelle collection',
			'separate_items_with_commas' => 'Séparer les collections par des virgules',
			'choose_from_most_used'      => 'Choisir parmis les plus utilisés',
			'most_used'                  => 'Les plus utilisés',
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
		);

		register_taxonomy( 'pq_collections', 'product', $args );
		register_taxonomy_for_object_type( 'pq_collections', 'product' );
	}


	/**
	 * Add custom description fields
	 * 
	 *	Add custom meta boxes
	*/

	public static function pq_add_meta_boxes() {

		add_meta_box(
			'frontend_quantity_meta_box',
			__( 'Quantité visible sur marketplace'),
			'pq_add_frontend_quantity',
			'product',
			'normal',
			'default'
		);

		add_meta_box(
			'ingredients_meta_box',
			__( 'Ingrédients'),
			'pq_add_ingredients',
			'product',
			'normal',
			'default'
		);

		add_meta_box(
			'instructions_meta_box',
			__( 'Conseils de préparation'),
			'pq_add_instructions',
			'product',
			'normal',
			'default'
		);

		add_meta_box(
			'producer_info_meta_box',
			__( 'Infos producteur'),
			'pq_add_producer_info',
			'product',
			'normal',
			'default'
		);
	}


	/**
	 * Save the custom descriptions
	 */

	public static function pq_save_custom_meta_boxes( $product ) {

		$custom_data_keys = array(
			'_frontend_quantity',
			'_pq_ingredients',
			'_pq_instructions',
			'_pq_producer_info',
		);

		foreach ($custom_data_keys as $custom_key) {

			if ( isset($_POST[$custom_key]) ) {
				$product->update_meta_data( $custom_key, wp_kses_post(stripslashes($_POST[$custom_key])) );
			}
		}
	}


	/**
	 * Add image meta to sellers and producers
	 */
	function load_media_image_marchand_and_producer() {
		$screen = get_current_screen();
		if($screen->taxonomy != 'product_tag' && $screen->taxonomy != 'pq_producer' ) {
			
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'pq-admin-categories', PQ_JS_URL . 'pq-admin-categories.js', array('jquery'), rand( 111, 9999 ), false );
		
	}


	function update_marchand_and_producer_image ( $term, $taxonomy ) { ?>
		<tr class="form-field term-group-wrap">
			<th scope="row">
				<label for="image_id"><?php _e( 'Image', 'taxt-domain' ); ?></label>
			</th>
			<td>
	
				<?php $image_id = get_term_meta ( $term -> term_id, 'image_id', true ); ?>
				<input type="hidden" id="image_id" name="image_id" value="<?php echo $image_id; ?>">
	
				<div id="image_wrapper">
				<?php if ( $image_id ) { ?>
				   <?php echo wp_get_attachment_image ( $image_id, 'thumbnail' ); ?>
				<?php } ?>
	
				</div>
	
				<p>
					<input type="button" class="button button-secondary taxonomy_media_button" id="taxonomy_media_button" name="taxonomy_media_button" value="<?php _e( 'Add Image', 'taxt-domain' ); ?>">
					<input type="button" class="button button-secondary taxonomy_media_remove" id="taxonomy_media_remove" name="taxonomy_media_remove" value="<?php _e( 'Remove Image', 'taxt-domain' ); ?>">
				</p>
	
			</div></td>
		</tr>
	<?php
	}

	function updated_marchand_and_producer_image ( $term_id, $tt_id ) {
		if( isset( $_POST['image_id'] ) && '' !== $_POST['image_id'] ){
			$image = $_POST['image_id'];
			update_term_meta ( $term_id, 'image_id', $image );
		} else {
			update_term_meta ( $term_id, 'image_id', '' );
		}
	}

	function display_marchand_and_producer_image_column_heading( $columns ) {
		$columns['category_image'] = __( 'Image', 'taxt-domain' );
		return $columns;
	}
	
	function display_marchand_and_producer_image_column_value( $columns, $column, $id ) {
		if ( 'category_image' == $column ) {
			$image_id = esc_html( get_term_meta($id, 'image_id', true) );
			
			$columns = wp_get_attachment_image ( $image_id, array('50', '50') );
		}
		return $columns;
	}

	/**
	 * Add emails meta for sellers
	 */
	function pq_update_seller_contact_info ( $term, $taxonomy ) { ?>
		<tr class="form-field term-group-wrap">
			<th scope="row">
				<label for="pq_seller_email"><?php _e( 'Email(s) (séparés par des virgules)', 'panier-quebecois' ); ?></label>
			</th>
			<td>
	
				<?php $seller_email = get_term_meta ( $term -> term_id, 'pq_seller_email', true ); ?>
				<input type="text" id="pq_seller_email" name="pq_seller_email" value="<?php echo $seller_email; ?>">
	
			</div></td>
		</tr>
		<tr class="form-field term-group-wrap">
			<th scope="row">
				<label for="pq_seller_sms"><?php _e( 'Numéro(s) de téléphone. IMPORTANT: suivre le format suivant: "+15141234567" et séparer par des virgules', 'panier-quebecois' ); ?></label>
			</th>
			<td>
	
				<?php $seller_email = get_term_meta ( $term -> term_id, 'pq_seller_sms', true ); ?>
				<input type="text" id="pq_seller_sms" name="pq_seller_sms" value="<?php echo $seller_email; ?>">
	
			</div></td>
		</tr>
		<tr class="form-field term-group-wrap">
			<th scope="row">
				<label for="pq_seller_needs_units"><?php _e( 'Besoin des unités?', 'panier-quebecois' ); ?></label>
			</th>
			<td>
	
				<?php $seller_needs_units = get_term_meta ( $term -> term_id, 'pq_seller_needs_units', true ); ?>
				<input type="checkbox" id="pq_seller_needs_units" name="pq_seller_needs_units" value="<?php echo $seller_needs_units; ?>" <?php checked($seller_needs_units); ?>>
	
			</div></td>
		</tr>
		<tr class="form-field term-group-wrap">
			<th scope="row">
				<label for="pq_seller_is_ordered_on_spot"><?php _e( 'Commandé sur place?', 'panier-quebecois' ); ?></label>
			</th>
			<td>
	
				<?php $seller_is_ordered_on_spot = get_term_meta ( $term -> term_id, 'pq_seller_is_ordered_on_spot', true ); ?>
				<input type="checkbox" id="pq_seller_is_ordered_on_spot" name="pq_seller_is_ordered_on_spot" value="<?php echo $seller_is_ordered_on_spot; ?>" <?php checked($seller_is_ordered_on_spot); ?>>
	
			</div></td>
		</tr>
	<?php
	}

	function pq_updated_seller_contact_info ( $term_id, $tt_id ) {
		if( isset( $_POST['pq_seller_email'] ) && '' !== $_POST['pq_seller_email'] ){
			$seller_email = $_POST['pq_seller_email'];
			update_term_meta ( $term_id, 'pq_seller_email', $seller_email );
		} else {
			update_term_meta ( $term_id, 'pq_seller_email', '' );
		}

		if( isset( $_POST['pq_seller_sms'] ) && '' !== $_POST['pq_seller_sms'] ){
			$seller_email = $_POST['pq_seller_sms'];
			update_term_meta ( $term_id, 'pq_seller_sms', $seller_email );
		} else {
			update_term_meta ( $term_id, 'pq_seller_sms', '' );
		}

		$seller_needs_units = isset($_POST['pq_seller_needs_units']) ? 1 : 0;
		update_term_meta ( $term_id, 'pq_seller_needs_units', $seller_needs_units );

		$seller_is_ordered_on_spot = isset($_POST['pq_seller_is_ordered_on_spot']) ? 1 : 0;
		update_term_meta ( $term_id, 'pq_seller_is_ordered_on_spot', $seller_is_ordered_on_spot );
	}
}

/**
 * Add the wp_editor to the meta boxes
 */

function pq_add_frontend_quantity( $post ) {
	$product = wc_get_product($post->ID);
	$content = $product->get_meta( '_frontend_quantity' );
	
	echo '<div class="frontend_quantity">';
	
    wp_editor( 
		$content, 
		'_frontend_quantity', 
		array(
			'textarea_rows' => 1,
			'media_buttons' => false,
		)
	);

    echo '</div>';
}

function pq_add_ingredients( $post ) {
	$product = wc_get_product($post->ID);
	$content = $product->get_meta( '_pq_ingredients' );
	
	echo '<div class="pq_ingredients">';
	
    wp_editor( 
		$content, 
		'_pq_ingredients', 
		array(
			'textarea_rows' => 8,
			'media_buttons' => false,
			'wpautop'       => false,
		)
	);

    echo '</div>';
}

function pq_add_instructions( $post ) {
	$product = wc_get_product($post->ID);
	$content = $product->get_meta( '_pq_instructions' );
	
	echo '<div class="pq_instructions">';
	
    wp_editor( 
		$content, 
		'_pq_instructions', 
		array(
			'textarea_rows' => 1,
			'media_buttons' => false,
		)
	);

    echo '</div>';
}

function pq_add_producer_info( $post ) {
	$product = wc_get_product($post->ID);
	$content = $product->get_meta( '_pq_producer_info' );
	
	echo '<div class="pq_producer_info">';
	
    wp_editor( 
		$content, 
		'_pq_producer_info', 
		array(
			'textarea_rows' => 1,
			'media_buttons' => false,
		)
	);

    echo '</div>';
}


