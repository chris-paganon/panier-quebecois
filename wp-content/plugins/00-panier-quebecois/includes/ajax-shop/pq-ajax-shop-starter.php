<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/* Created shortcode [product_data] for displaying products */
add_shortcode( 'product_data', 'custom_product_function' );

function custom_product_function() {
  ob_start(); ?>
  <!--/* Woocommerce featured products loop */-->
  <div class='awwm-product-loop'>
    <div class='parent-cat panier-perso'>
      <?php
      // Defining filters in array for mapping
      $filters = ( !empty( $_GET[ 'filters' ] ) ) ? explode( ',', $_GET[ 'filters' ] ) : array();
      $filters_tax_query_array = pq_get_filters_tax_query_array( $filters );


      /**
       * Display the featured prodcuts
       */
      $tax_query = array(
        'relation' => 'AND',
        array(
          'taxonomy' => 'product_visibility',
          'field' => 'name',
          'terms' => 'featured',
        ),
        array(
          'taxonomy' => 'product_visibility',
          'field'    => 'term_taxonomy_id',
          'terms'    => array(7, 9),
          'operator' => 'NOT IN',
        ),
      );

      foreach ( $filters_tax_query_array as $filters_tax_query ) {
        $tax_query[] = $filters_tax_query;
      }

      $featured_products_query = new WP_Query( array(
        'post_type'      => 'product',
        'posts_per_page' => 50,
        'tax_query'      => $tax_query,
        'orderby'        => 'menu_order',
			  'order'          => 'ASC'
      ));

      if ( $featured_products_query->have_posts() ) { ?>
        <div class="selection parent">
          <a id="selection" class="pq_anchor"></a>
          <div class="selection categoryTitle infiniteScroll">
            <h3><?php echo get_option( 'pq_featured_products_title' ) ?></h3>
            <hr>
          </div>
          <div class="selection woocommerce columns-6 infiniteScroll">
            <?php
            woocommerce_product_loop_start();
            while ( $featured_products_query->have_posts() ): $featured_products_query->the_post();
              wc_get_template_part( 'content', 'product' );
            endwhile;
            woocommerce_product_loop_end();
            ?>
          </div>
        </div><?php
      }
      wp_reset_postdata(); ?>
      <?php

      /**
       * Display the baskets
       */
      $tax_query = array(
        'relation' => 'AND',
        array(
          'taxonomy' => 'product_cat',
          'field' => 'id',
          'terms' => 171,
        ),
        array(
          'taxonomy' => 'product_visibility',
          'field'    => 'term_taxonomy_id',
          'terms'    => array(7, 9),
          'operator' => 'NOT IN',
        ),
      );

      foreach ( $filters_tax_query_array as $filters_tax_query ) {
        $tax_query[] = $filters_tax_query;
      }

      $baskets_query_args = array(
        'posts_per_page' => 50,
        'tax_query' => $tax_query,
        'meta_query' => array(
          array(
            'key' => '_wc_pb_bundle_stock_quantity',
            'value' => 0,
            'compare' => '!='
          )
        ),
        'post_type' => 'product',
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'post_status' => 'publish'
      );
      $baskets_query = new WP_Query( $baskets_query_args );

      if ( $baskets_query->have_posts() ) { 

        $basket_term = get_term(171, 'product_cat');?>

        <div class="mainCategory___this panier categoryTitle infiniteScroll elementor-menu-anchor"><a id="panier" class="pq_anchor"></a><h3><?php echo $basket_term->name; ?></h3><hr></div>

        <div class="subCategory___this subCategory___panierTarget panier woocommerce columns-6 infiniteScroll">
          <?php woocommerce_product_loop_start(); ?>
          <?php while ( $baskets_query->have_posts() ) : $baskets_query->the_post(); ?>
            <?php wc_get_template_part( 'content', 'product' ); ?>
          <?php endwhile; // end of the loop. ?>
          <?php woocommerce_product_loop_end(); ?>
        </div><?php
      }
      wc_reset_loop();
      wp_reset_postdata();
      

      /**
       * Display the vegetables
       */
      $vegetables_slug = 'legumes';

			$vegetables_children_args = array(
				'taxonomy' => 'product_cat',
				'hide_empty' => true,
				'parent'   => 177,
			);

      ?>
      <div class="mainCategory___this legumes produit-unite categoryTitle infiniteScroll elementor-menu-anchor"><a id="legumesTarget" class="pq_anchor"></a><h3>Légumes</h3><hr></div>
      <?php

			$vegetables_children_cats = get_terms( $vegetables_children_args );
			$count_vegetables_children_cat = count( $vegetables_children_cats );

      $tax_query = array(
        array(
          'taxonomy' => 'product_visibility',
          'field'    => 'term_taxonomy_id',
          'terms'    => array(7, 9),
          'operator' => 'NOT IN',
        ),
      );

      foreach ( $filters_tax_query_array as $filters_tax_query ) {
        $tax_query[] = $filters_tax_query;
      }

      //If no sub categories of vegetables, display all of them
      if ( $count_vegetables_children_cat == 0 ) {
				
				$vegetables_query_args = array(
					'posts_per_page' => 200,
          'tax_query'      => $tax_query,
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'product_cat'    => $vegetables_slug,
					'orderby'        => 'menu_order',
					'order'          => 'ASC'
				);
				$vegetables_query = new WP_Query( $vegetables_query_args );

				if ( $vegetables_query->have_posts() ) {
					?>
					<div class="subCategory___this subCategory___legumesTarget legumes woocommerce columns-6 infiniteScroll child">
						<?php woocommerce_product_loop_start(); ?>
							<?php while ( $vegetables_query->have_posts() ) : $vegetables_query->the_post(); ?>
								<?php wc_get_template_part( 'content', 'product' ); ?>
							<?php endwhile; // end of the loop. ?>
						<?php woocommerce_product_loop_end(); ?>
					</div>
					<?php
				}
				wc_reset_loop();
				wp_reset_postdata();
      
      //Display child categories (only) of vegetables if they exist
			} else {
				foreach ( $vegetables_children_cats as $vegetables_children_cat ) {

					$vegetables_products_args = array(
						'posts_per_page' => 200,
            'tax_query'      => $tax_query,
						'post_type'      => 'product',
						'post_status'    => 'publish',
						'product_cat'    => $vegetables_children_cat->slug,
						'orderby'        => 'menu_order',
						'order'          => 'ASC'
					);
					$vegetables_products = new WP_Query( $vegetables_products_args );
	
					if ( $vegetables_products->have_posts() ) {

						echo '<div class="subCategory___this subCategory___legumesTarget '.$vegetables_children_cat->slug. ' '.$vegetables_slug.' categoryTitle infiniteScroll"><a id="'.$vegetables_children_cat->slug.'Target" class="pq_anchor"></a><div class="subchild"></div><h4>'.$vegetables_children_cat->name.' </h4></div>';
						?>
						<div class="product___this product___legumesTarget product___<?php echo $vegetables_children_cat->slug?>Target woocommerce columns-6 infiniteScroll child">
							<?php woocommerce_product_loop_start(); ?>
								<?php while ( $vegetables_products->have_posts() ) : $vegetables_products->the_post(); ?>
									<?php wc_get_template_part( 'content', 'product' ); ?>
								<?php endwhile; // end of the loop. ?>
							<?php woocommerce_product_loop_end(); ?>
						</div>
						<?php
					}
					wc_reset_loop();
          wp_reset_postdata();
				}
			} ?>
    </div>
    
    <!--/* Page load spinner while loding products */-->
    <button class="pq_load_more_button"><?php echo esc_html__('Voir tous les produits', 'panier-quebecois');?></button>
    <div class="pq_load_more_notice"><p><?php echo esc_html__('Utilisez les catégories si cette page est lente', 'panier-quebecois');?></p></div>
    <div id="overlay" style="display:none;">
      <div class="cv-spinner"> <span class="spinner"></span> </div>
    </div>
    <div class="noMoreProductsMessage"></div>
  </div>
  <?php

  return ob_get_clean();
}

/* Created shortcode [product_cat_list] for displaying product categories */
add_shortcode( 'product_cat_list', 'prod_cats_list' );

function prod_cats_list() {
  ob_start();
  $url = site_url( '/produits/', 'https' ); 
  $filters = ( !empty( $_GET[ 'filters' ] ) ) ? $_GET[ 'filters' ] : false;
  
  if ( ! empty($_GET[ 'filters' ]) ) {
    $url .= '?filters=' . $_GET[ 'filters' ];
  }

  $queried_object = get_queried_object();
  $query_id = $queried_object->term_id;
  ?>

  <div class="awwm-cat-loop elementor-element elementor-element-46b8941 elementor-nav-menu__align-left elementor-widget elementor-widget-nav-menu" data-id="46b8941" data-element_type="widget" id="my-marketplace-pc-menu" data-widget_type="nav-menu.default">
    <div class="elementor-widget-container">
      <nav migration_allowed="1" migrated="0" role="navigation" class="elementor-nav-menu--main elementor-nav-menu__container elementor-nav-menu--layout-vertical e--pointer-text e--animation-none">
        <ul class="elementor-nav-menu sm-vertical">
          <li data-sequence="1" class="lesProducts menu-item menu-item-type-custom menu-item-object-custom current-menu-item"> <a href="<?php echo $url;?>" aria-current="page" class="elementor-item elementor-item-anchor menu-link" producttarget="selectionTargetSidebar"><?php _e( 'Tous les produits' );?></a> </li>
          <li data-sequence="1" class="lesProducts menu-item menu-item-type-custom menu-item-object-custom current-menu-item"> <a href="<?php echo $url;?>#selection" aria-current="page" class="elementor-item elementor-item-anchor menu-link" producttarget="selectionTargetSidebar"><?php _e( 'En vedette' );?></a> </li>
          <li data-sequence="2" class="lesProducts menu-item menu-item-type-custom menu-item-object-custom current-menu-item"> <a href="<?php echo $url;?>#panier" aria-current="page" class="elementor-item elementor-item-anchor menu-link" producttarget="panierTargetSidebar"><?php _e( 'Les paniers' );?></a></li>

          <?php
          
          $args = array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => 153,
            'exclude' => array( 237 ),
          );
          $main_product_cat = get_terms( $args );
          $tempCounter = 0;

          foreach ( $main_product_cat as $parent_product_cat ) {

            $child_args = array(
              'taxonomy' => 'product_cat',
              'hide_empty' => true,
              'parent' => $parent_product_cat->term_id,
            );
            $child_product_cats = get_terms( $child_args );
            $tempCounter++;

            if ( $child_product_cats ) { 
              
              $category_is_active = false;
              if ( $query_id === $parent_product_cat->term_id ) {
                $category_is_active = true;
              }
              
              ?>

              <li data-sequence="<?= 2 + $tempCounter ?>" class="lesProducts menu-item menu-item-type-custom menu-item-object-custom current-menu-item current-menu-ancestor current-menu-parent menu-item-has-children<?php if ($category_is_active) echo ' pq-active';?>"> <a href="<?php echo esc_url( add_query_arg('filters', $filters ,get_term_link($parent_product_cat))); ?>" producttarget="<?php echo $parent_product_cat->slug;?>TargetSidebar" aria-current="page" class="elementor-item elementor-item-anchor menu-link has-submenu" aria-haspopup="true" aria-expanded="false"><?php echo $parent_product_cat->name;?> <span class="sub-arrow"><i class="fas fa-caret-down"></i></span> </a> <?php echo '<ul class="sub-menu elementor-nav-menu--dropdown">';

              foreach ( $child_product_cats as $child_product_cat ) { 
                
                if ( $child_product_cat->count > 0 ) { 
                  ?>
                  <li> <a href="<?php echo esc_url( add_query_arg('filters', $filters , get_term_link($parent_product_cat))); ?>#<?php echo $child_product_cat->slug;?>" producttarget="<?php echo $child_product_cat->slug;?>TargetSidebar"><?php echo $child_product_cat->name;?></a></li>
                  <?php
                }
              }
              echo '</ul>';
            } else { 
              ?>
              <li data-sequence="<?= 2 + $tempCounter ?>" class="lesProducts menu-item menu-item-type-custom menu-item-object-custom"> <a href="<?php echo esc_url( add_query_arg('filters', $filters , get_term_link($parent_product_cat))); ?>" producttarget="<?php echo $parent_product_cat->slug;?>TargetSidebar" class="elementor-item elementor-item-anchor menu-link"><?php echo $parent_product_cat->name;?></a>
              <?php
            }
            echo '</li>';
          }?>
        </ul>
      </nav>
    </div>
  </div>
  <?php

  return ob_get_clean();
}

/* Created shortcode [product_filter_list] for displaying product filters */
add_shortcode( 'product_filter_list', 'prod_filters_list' );

function prod_filters_list() {
  ob_start(); ?>

  <div class="awwm-filter-loop elementor-element elementor-element-46b8941 elementor-nav-menu__align-left elementor-widget elementor-widget-nav-menu" data-id="46b8941" data-element_type="widget" id="my-marketplace-filter-menu" data-widget_type="nav-menu.default">
    <div class="elementor-widget-container">
      <nav migration_allowed="1" migrated="0" role="navigation" class="elementor-nav-menu--main elementor-nav-menu__container elementor-nav-menu--layout-vertical e--pointer-text e--animation-none">
        <ul class="elementor-nav-menu sm-vertical">

          <li class="lesProducts menu-item menu-item-type-custom menu-item-object-custom">
            <label for="prod_filter-bio">
              <?php $filtersArray = (isset($_GET['filters'])) ? explode(',', $_GET['filters']) : array(); ?>
              <input type="checkbox" class='prod_filter' name="prod_filter-bio" value="bio" <?= (in_array('bio', $filtersArray)) ? 'checked' : '' ?>>
              <span class="customcheckbox"></span> <span class="checkedlabel">Bio</span> 
            </label>
            <br>
          </li>

          <li class="lesProducts menu-item menu-item-type-custom menu-item-object-custom">
            <label for="prod_filter-quebec">
              <?php $filtersArray = (isset($_GET['filters'])) ? explode(',', $_GET['filters']) : array(); ?>
              <input type="checkbox" class='prod_filter' name="prod_filter-quebec" value="quebec" <?= (in_array('quebec', $filtersArray)) ? 'checked' : '' ?>>
              <span class="customcheckbox"></span> <span class="checkedlabel">Provenance Québec</span> 
            </label>
            <br>
          </li>

          <li class="lesProducts menu-item menu-item-type-custom menu-item-object-custom">
            <label for="prod_filter-marche">
              <?php $filtersArray = (isset($_GET['filters'])) ? explode(',', $_GET['filters']) : array(); ?>
              <input type="checkbox" class='prod_filter' name="prod_filter-marche" value="marche-jean-talon" <?= (in_array('marche-jean-talon', $filtersArray)) ? 'checked' : '' ?>>
              <span class="customcheckbox"></span> <span class="checkedlabel">Marché Jean-Talon</span> 
            </label>
            <br>
          </li>

          <li class="lesProducts menu-item menu-item-type-custom menu-item-object-custom">
            <label for="prod_filter-marche">
              <?php $filtersArray = (isset($_GET['filters'])) ? explode(',', $_GET['filters']) : array(); ?>
              <input type="checkbox" class='prod_filter' name="prod_filter-marche" value="vegan" <?= (in_array('vegan', $filtersArray)) ? 'checked' : '' ?>>
              <span class="customcheckbox"></span> <span class="checkedlabel">Végé/Vegan</span> 
            </label>
            <br>
          </li>
        
        </ul>
      </nav>
    </div>
  </div>
  <?php
  return ob_get_clean();
}


/* Display Jean Talon en ligne logo if necessary */
add_shortcode( 'pq_jt_en_ligne', 'pq_jt_en_ligne_function' );

function pq_jt_en_ligne_function() {
  $filtersArray = ( !empty( $_GET[ 'filters' ] ) ) ? explode( ',', $_GET[ 'filters' ] ) : array();
  
  if ( in_array( 'marche-jean-talon', $filtersArray ) ) {
    $image_url = wp_get_attachment_url( 40145 );
    $img = '<img src="' . $image_url . '" class="jt_en_ligne_logo">';

    echo $img;
  }
}


// Ajax action
function ajax_get_all_products() {
  include_once( PQ_INCLUDE_AJAXSHOP_DIR . 'pq-ajax-get-all-products.php' );
  exit;
}

// if the ajax call will be made from JS executed when user is logged into WP,
add_action( 'wp_ajax_ajax_get_all_products', 'ajax_get_all_products' );
// if the ajax call will be made from JS executed when no user is logged into WP,
add_action( 'wp_ajax_nopriv_ajax_get_all_products', 'ajax_get_all_products' );
