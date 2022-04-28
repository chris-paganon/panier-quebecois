<?php
/**
 * Partial Payment Template
 * @since 1.0
 * @version 1.0
 */
if ( !WC()->cart->needs_payment() ) {

  // There is no need for partial payments of orders that have no cost.
  return;
}

global $mycred_remove_partial_payment;

// Make sure we can make a partial payment
if ( !mycred_partial_payment_possible() ) {

  // If you prefer, you could display some sort of information to those who can
  // not make a partial payment before returning.

  return;
}

// The <div> element with the ID "mycred-partial-payment-woo" MUST REMAIN!
// Any changes you make must be made inside this div element!

?>
<div id="mycred-partial-payment-woo">
  <h3>
    <?php mycred_partial_payment_title(); ?>
  </h3>
  <?php if (! empty(mycred_partial_payment_desc())) : ?>
  <p>
    <?php mycred_partial_payment_desc(); ?>
  </p>
  <?php
  endif;

  global $mycred_partial_payment;

  $user_id = get_current_user_id();
  $settings = mycred_part_woo_settings();

  $mycred = mycred( $mycred_partial_payment[ 'point_type' ] );
  if ( $mycred->exclude_user( $user_id ) ) return;

  $step = ( $mycred_partial_payment[ 'step' ] != '' && $mycred_partial_payment[ 'step' ] > 0 ) ? $mycred->number( $mycred_partial_payment[ 'step' ] ) : false;

  $total = mycred_part_woo_get_total();

  $balance = $mycred->get_users_balance( $user_id );
  $max = $mycred->number( $total / $mycred_partial_payment[ 'exchange' ] );
  if ( $balance < $max )
    $max = $balance;

  $min = ( ( $mycred_partial_payment[ 'min' ] > 0 ) ? $mycred_partial_payment[ 'min' ] : 0 );
  //set max to percentage value in setting 
  $max = ( ( $mycred_partial_payment[ 'max' ] < 100 ) ? ( $max / 100 ) * $mycred_partial_payment[ 'max' ] : $max );

  ?>
  <div id="mycred-partial-payment-wrapper" class="uses-<?php echo $settings['selecttype']; ?>">
    <div id="mycred-partial-payment-total">
      <p><?php echo wc_price( $max * $mycred_partial_payment['exchange'] ); ?>
        <?php _e( 'de rabais', 'mycredpartwoo' ); ?>
      </p>
    </div>
    <div id="mycred-range-selector">
      <input 
				type="<?php if ( $settings['selecttype'] == 'input' ) echo 'number'; else echo 'range'; ?>" 
				min="<?php echo $mycred->number( $min ); ?>" 
				max="<?php echo $max; ?>" 
				<?php if ( $settings['selecttype'] == 'input' ) echo 'class="input-text"'; else echo 'class="input-range"'; ?>
				<?php if ( $step !== false ) : ?>
				step="<?php echo esc_attr( $step ); ?>" 
				placeholder="<?php printf( __( 'Increments of %s', 'mycredpartwoo' ), $mycred->format_creds( $step ) ); ?>" 
				<?php endif; ?>
				value="<?php echo $max; ?>" 
				style="width:100%;" />
    </div>
    <div id="mycred-range-action">
      <button class="button button-primary btn btn-primary" type="button" id="mycred-apply-partial-payment"><?php echo esc_attr( $mycred_partial_payment['button'] ); ?></button>
    </div>
  </div>
</div>
