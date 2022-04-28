<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * My loyalty helper functions
 */
class PQ_loyalty_helper {
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

  public static function image_url( $image_name = '' ) {
    switch ( $image_name ) {
      case 'coin':
        $url = wp_get_attachment_url( 13917 );
        break;
      default:
        $url = wp_get_attachment_url( 13917 );
    }

    return $url;
  }

  public static function get_image( $width = '55px', $image_name = '', $class = '' ) {
    $image_url = PQ_loyalty_helper::image_url( $image_name );

    if ( !empty( $class ) ) {
      $class = 'class ="' . $class . '"';
    }

    $img = '<img src="' . $image_url . '" width="' . $width . '" ' . $class . '>';
    return $img;
  }
}