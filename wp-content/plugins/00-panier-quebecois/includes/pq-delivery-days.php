<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class PQ_delivery_days {
    /**
     * Variables
     */
    protected static $_instance = null;

    public static $delivery_days = array(
        'Monday',
        'Thursday',
    );
    public static $deadline_hour = 23;
    public static $deadline_minute = 59;
    public static $deadline_day_difference = 1;

    /**
     * Initiate a single instance of the class
     */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }


    /**
     * Get the next delivery deadline
     */
    public static function pq_next_delivery_deadline() {
        $delivery_days = self::$delivery_days;

        $deadline_hour = self::$deadline_hour;
        $deadline_minute = self::$deadline_minute;
        $deadline_day_difference = self::$deadline_day_difference;

        $wordpress_timezone = new DateTimeZone( get_option( 'timezone_string' ) );
        $now = new DateTime( '', $wordpress_timezone );

        $next_delivery_deadline = new DateTime( '+ 8 weeks', $wordpress_timezone );

        foreach ( $delivery_days as $delivery_day ) {
            $delivery_date = new DateTime ( $delivery_day, $wordpress_timezone );
            $delivery_deadline_day = $delivery_date->modify('- ' . $deadline_day_difference . ' days');
            $delivery_deadline = $delivery_deadline_day->setTime($deadline_hour, $deadline_minute);

            if ( $delivery_deadline > $now && $delivery_deadline < $next_delivery_deadline ) {
                $next_delivery_deadline = $delivery_deadline;
            }
        }

        return $next_delivery_deadline;
    }


    /**
     * Get next delivery day
     */
    public static function pq_next_delivery_day() {
        $next_delivery_deadline = self::pq_next_delivery_deadline();
        $deadline_day_difference = self::$deadline_day_difference;

        $next_delivery_day = $next_delivery_deadline->modify('+ ' . $deadline_day_difference . ' days');
        $next_delivery_day = $next_delivery_day->setTime(00, 00, 00);

        return $next_delivery_day;
    }

    /**
     * Get next delivery day formatted in french
     */
    public static function pq_get_next_delivery_day_fr() {
        
        $next_delivery_day = self::pq_next_delivery_day();
        $wordpress_timezone = new DateTimeZone( get_option( 'timezone_string' ) );
        $today = new DateTime( 'today', $wordpress_timezone );

        if ( $today == $next_delivery_day ) {
            $next_delivery_day_formatted = 'aujourd\'hui';
        } else {
            $fmt_fr = new IntlDateFormatter( 'fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, get_option( 'timezone_string' ), IntlDateFormatter::GREGORIAN, 'EEEE' );
            $next_delivery_day_formatted = $fmt_fr->format( $next_delivery_day );
        }

        return $next_delivery_day_formatted;
    }

    /**
     * Get deadline formatted for header
     */
    public static function pq_get_delivery_deadline_fr() {
        $next_delivery_day = self::pq_next_delivery_day();
        $next_delivery_deadline = self::pq_next_delivery_deadline();

        $wordpress_timezone = new DateTimeZone( get_option( 'timezone_string' ) );
        $tomorrow = new DateTime( 'tomorrow', $wordpress_timezone );
        $today = new DateTime( 'today', $wordpress_timezone );

        if ( $tomorrow == $next_delivery_day ) {
            $deadline_formatted = 'avant minuit';
        } else {
            $deadline_formatted = 'maintenant';
        }

        return $deadline_formatted;
    }
}