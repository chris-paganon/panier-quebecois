<?php
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * Replace default category archive queries with empty query if it has child categories
 * Add filters to the query otherwise
 */
add_action( 'pre_get_posts', 'pq_replace_with_empty_category_query' );

function pq_replace_with_empty_category_query( $query ) {
	if ( $query->is_main_query() && !is_admin() && $query->is_archive() ) {

		$category_slug = $query->get('product_cat', false);

		if ( $category_slug ) {

			$child_categories = pq_get_child_categories($category_slug);
			$tax_query = $query->get( 'tax_query' ) ?: array();

			if ( $child_categories ) {
				$tax_query[] = array(
					'taxonomy' => 'does_not_exist',
					'terms' => 'nope',
					'field' => 'slug',
				);
			} else {
				$filters = ( !empty( $_GET[ 'filters' ] ) ) ? explode( ',', $_GET[ 'filters' ] ) : array();

				if ( !empty($filters) ) {
					$filters_tax_query_array = pq_get_filters_tax_query_array( $filters );
					$tax_query[] = $filters_tax_query_array;
				}
			}
	
			$query->set('tax_query', $tax_query);
			$meta_query = pq_get_meta_query();
			$query->set('meta_query', $meta_query);
		}
	}
}


/**
 * Display custom product sub categories for product categories archives only
 */
add_action( 'elementor/widget/before_render_content', 'pq_maybe_replace_category_render' );

function pq_maybe_replace_category_render( $widget ) {
	$settings = $widget->get_settings();
	
	if ( ! empty($settings['query_post_type']) && $settings['query_post_type'] == 'current_query' ) {
		$query = $GLOBALS['wp_query'];

		if ( $query->is_main_query() && !is_admin() && $query->is_archive() ) {
			$category_slug = $query->get('product_cat', false);

			if ( $category_slug ) {
				echo pq_maybe_display_child_categories( $category_slug );
			}
		}
	}
}


/**
 * Display custom product child categories only if category has children
 */
function pq_maybe_display_child_categories( $category_slug ) {
	
	$category = get_term_by( 'slug', $category_slug, 'product_cat' );
	if ( $category->parent != 153 ) return '';

	$child_categories = pq_get_child_categories($category_slug);

	if ( $child_categories ) {
		return pq_display_child_categories( $child_categories );
	} else {
		return '';
	}
}


/**
 * Display children categories
 */
function pq_display_child_categories( $child_categories ) {

	?>
	<div class='awwm-product-loop'>
    	<div class='parent-cat panier-perso'>
			<?php
			foreach ( $child_categories as $child_category ) {

				$filters = ( !empty( $_GET[ 'filters' ] ) ) ? explode( ',', $_GET[ 'filters' ] ) : array();			
				$filters_tax_query_array = pq_get_filters_tax_query_array( $filters );

				$products_query_arg = pq_get_wp_query_arguments($child_category->slug, $filters_tax_query_array);

				// Starting child term products loop
				$products_query = new WP_Query( $products_query_arg );

				if ( $products_query->have_posts() ) {
					echo '<div class="subCategory___this subCategory___' . $child_category->slug . 'Target ' . $child_category->slug . ' categoryTitle infiniteScroll"><a id="' . $child_category->slug . '" class="pq_anchor"></a><div class="child"></div><h4>' . $child_category->name . '</h4></div>'; ?>

					<div class="product___this product___<?php echo $child_category->slug?>Target woocommerce columns-6 infiniteScroll child">
						<?php woocommerce_product_loop_start(); ?>
						<?php while ( $products_query->have_posts() ) : $products_query->the_post(); ?>
							<?php wc_get_template_part( 'content', 'product' ); ?>
						<?php endwhile; // end of the loop. ?>
						<?php woocommerce_product_loop_end(); ?>
					</div><?php
				}

				wc_reset_loop();
				wp_reset_postdata();
			}
			?>
		</div>
	</div>
	<?php
}


/**
 * Mapping filters URL parameters to taxonomy slugs 
 */
function pq_get_filters_mapping() {
	$filter_mapping = array(
		'bio' => 'food_restrictions',
		'quebec' => 'country_origin',
		'marche-jean-talon' => 'pq_commercial_zone',
		'marche-atwater' => 'pq_commercial_zone',
		'vegan' => 'food_restrictions',
	);

	return $filter_mapping;
}


/**
 * Get filters array ready to plug into tax query
 */
function pq_get_filters_tax_query_array( $filters ) {

	$filter_mapping = pq_get_filters_mapping();

	$filters_tax_query_array = array();

	// Rendering array according to selected filter
	foreach( $filters as $filter ) {

		if( isset($filter_mapping[$filter]) ) {
			$filters_tax_query_array[] = array(
			'taxonomy' => $filter_mapping[$filter],
			'field' => 'slug',
			'terms' => ($filter != 'vegan') ? $filter : array('vegan', 'vege'),
			'operator' => 'IN'
			);
		}
	}

	return $filters_tax_query_array;
}


/**
 * Get meta query for products in stock and maybe if use is outside Montreal
 */
function pq_get_meta_query() {
	$meta_query = array(
	'relation' => 'AND',
		array(
			'key'     => '_stock_status',
			'value'   => 'instock',
		)
	);
	if ( is_delivery_zone_outside_mtl() ) {
		$meta_query[] = array(
			'key'     => '_pq_available_long_distance',
			'value'   => 1,
		);
	}
	return $meta_query;
}


/**
 * Get child categories (return false if it has no children)
 */
function pq_get_child_categories( $category_slug ) {
	$category = get_term_by( 'slug', $category_slug, 'product_cat' );

	$category_id = $category->term_id;

	$child_categories = get_terms( array(
		'taxonomy' => 'product_cat',
		'hide_empty' => true,
		'parent' => $category_id,
	));

	foreach ( $child_categories as $key => $child_category ) {
		if ( $child_category->count === 0 ) {
			unset($child_categories[$key]);
		}
	}

	if ( empty($child_categories) ) {
		return false;
	} else {
		return $child_categories;
	}
}


/**
 * Get arguments for wp_query
 */
function pq_get_wp_query_arguments($term_slug, $filters_tax_query_array) {
	$tax_query = array(
		'relation' => 'AND',
		array(
			'taxonomy' => 'product_cat',
			'field' => 'slug',
			'terms' => $term_slug,
		),
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => array(7, 9),
			'operator' => 'NOT IN',
		),
	);

	// Setting up tax_query for filtering products
	foreach ( $filters_tax_query_array as $filters_tax_query ) {
		$tax_query[] = $filters_tax_query;
	}

	$meta_query = pq_get_meta_query();

	$pq_wp_query_args = array(
		'posts_per_page' => 300,
		'tax_query' => $tax_query,
		'meta_query' => $meta_query,
		'post_type' => 'product',
		'orderby' => 'menu_order',
		'order' => 'ASC',
		'pq_categories_query' => 1,
	);

	return $pq_wp_query_args;
}


/**
 * Add filters in main menu links with products
 */
add_filter( 'nav_menu_link_attributes', 'pq_add_filters_to_main_menu', 10, 4 );

function pq_add_filters_to_main_menu( $atts, $item, $menu_args, $depth ) {

	$menu_name = $menu_args->menu;
	
	if ( $menu_name == 'produits' || $menu_name == 'menu-mobile' ) {
		
		$link = $atts['href'];
		
		if ( !empty($_GET[ 'filters' ]) && (stripos($link, 'categorie-produit') !== false || stripos($link, 'produits') !== false) ) {
			
			$filters_html = '?filters=' . $_GET[ 'filters' ];
			$anchor_pos = stripos($link, '#');

			if ( $anchor_pos !== false ) {
				$link = substr_replace($link, $filters_html, $anchor_pos, 0);
			} else {
				$link .= $filters_html;
			}

			$atts['href'] = $link;
		}
	}

	return $atts;
}


/**
 * Hide variable products from on sale archive page if all variations are not on sale
 */
add_action( 'pre_get_posts', 'pq_remove_variable_products_not_onsale' );

function pq_remove_variable_products_not_onsale( $query ) {
	
	if ( !is_admin() && $query->is_archive() ) {
		$query_vars = $query->query_vars;
		
		if ( isset($query_vars['post_type']) && $query_vars['post_type'] == 'product' && ! isset($query_vars['pq_categories_query']) ) {

			$products = $query_vars['post__in'];
			foreach ( $products as $key => $product_id ) {
				$product = wc_get_product($product_id);
				$product_variations = $product->get_children();

				if ( ! empty($product_variations) ) {

					$is_variation_onsale = true;
					foreach ( $product_variations as $product_variation_id ) {
						$product_variation = wc_get_product($product_variation_id);
						if ( ! $product_variation->is_on_sale() ) {
							$is_variation_onsale = false;
						}
					}
					if ( ! $is_variation_onsale ) {
						unset($products[$key]);
					}
				}
			}
			$query->set('post__in', $products);
		}
	}
}