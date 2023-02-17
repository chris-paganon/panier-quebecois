<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

// Setting up filters array for filtering products according to user selection
$filters = ( !empty( $_POST[ 'filters' ] ) ) ? explode( ',', $_POST[ 'filters' ] ) : array();
$filters_tax_query_array = pq_get_filters_tax_query_array( $filters );

$parent_product_cat = get_term_by( 'id', 153, 'product_cat' );

// Calling child category if parent term has any
$child_args = array(
  'taxonomy' => 'product_cat',
  'hide_empty' => true,
  'parent' => $parent_product_cat->term_id,
  'exclude' => array( 237, 177 ), //Ignore Christmas and vegetables sub categories
);

$child_product_cats = get_terms( $child_args );
$countChild = count( $child_product_cats );

// Starting child term products loop
foreach ( $child_product_cats as $child_product_cat ) {

  $subchild_args = array(
    'taxonomy' => 'product_cat',
    'hide_empty' => true,
    'parent' => $child_product_cat->term_id,
  );

  $sub_child_product_cats = get_terms( $subchild_args );
  $countSubChild = count( $sub_child_product_cats );

  if ( $countSubChild == 0 ) {

    // Starting child term products loop
    $childprodargs = pq_get_wp_query_arguments($child_product_cat->slug, $filters_tax_query_array, true);
    
    $childproducts = new WP_Query( $childprodargs );
    if ( $childproducts->have_posts() ) { ?>
    
      <div id="<?php echo $child_product_cat->slug ?>Target" class="mainCategory___this <?php echo $child_product_cat->slug . ' ' . $parent_product_cat->slug ?> categoryTitle infiniteScroll"><div class="child"></div><h3><?php echo $child_product_cat->name; ?></h3><hr></div>

      <div class="product___this product___<?php echo $child_product_cat->slug?>Target <?php echo $parent_product_cat->slug?> woocommerce columns-6 infiniteScroll child">
        <?php woocommerce_product_loop_start(); ?>
        <?php while ( $childproducts->have_posts() ) : $childproducts->the_post(); ?>
          <?php wc_get_template_part( 'content', 'product' ); ?>
        <?php endwhile; // end of the loop. ?>
        <?php woocommerce_product_loop_end(); ?>
      </div><?php
    }

    wc_reset_loop();
    wp_reset_postdata();
  }

  // Starting sub child products loop
  foreach ( $sub_child_product_cats as $subchild_key => $subchild_product_cat ) {

    // Setting up query for loading products from sub child terms
    $subchildprodargs = pq_get_wp_query_arguments($subchild_product_cat->slug, $filters_tax_query_array, true);

    $subchildproducts = new WP_Query( $subchildprodargs );

    if ( $subchildproducts->have_posts() ) { 
      
      reset($sub_child_product_cats);
      if ( $subchild_key == key($sub_child_product_cats) ) {
        ?>
        <div id="<?php echo $child_product_cat->slug ?>Target" class="mainCategory___this <?php echo $child_product_cat->slug . ' ' . $parent_product_cat->slug ?> categoryTitle infiniteScroll"><div class="child"></div><h3><?php echo $child_product_cat->name; ?></h3><hr></div>
        <?php
      }
      ?>

      <div id="<?php echo  $subchild_product_cat->slug ?>Target" class="subCategory___this subCategory___<?php echo $child_product_cat->slug ?>Target <?php echo $subchild_product_cat->slug . ' ' . $child_product_cat->slug . ' ' . $parent_product_cat->slug ?> categoryTitle infiniteScroll"><div class="subchild"></div><h4><?php echo $subchild_product_cat->name; ?></h4></div>

      <div class="product___this product___<?php echo $subchild_product_cat->slug;?>Target <?php echo $subchild_product_cat->slug;?> <?php echo $child_product_cat->slug;?> <?php echo $parent_product_cat->slug;?> woocommerce columns-6 infiniteScroll subchild">
        <?php woocommerce_product_loop_start(); ?>
        <?php while ( $subchildproducts->have_posts() ) : $subchildproducts->the_post(); ?>
          <?php wc_get_template_part( 'content', 'product' );?>
        <?php endwhile; // end of the loop. ?>
        <?php woocommerce_product_loop_end(); ?>
      </div><?php
    }

    wc_reset_loop();
    wp_reset_postdata();
  }
}
?>
