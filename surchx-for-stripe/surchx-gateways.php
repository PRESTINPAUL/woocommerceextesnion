<?php
/*
 * Description: Plugin file for stripe payment method integartion.
 * Author: Surchx
 * Stripe SDK Version: 2.0.1
 *
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'surchx_stripe_add_gateway_class' );

function surchx_stripe_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Surchx_Stripe_Gateway'; // your class name is here
	return $gateways;
}

function surchx_stripe_admin_enqueue_scripts($hook) {

    if ('woocommerce_page_wc-settings' !== $hook) {
        return;
    }
    wp_enqueue_script( 'surchx_stripe_admin_main', plugins_url( 'js/surchx-admin.js', __FILE__ ) );
}

add_action('admin_enqueue_scripts', 'surchx_stripe_admin_enqueue_scripts');


add_action( 'plugins_loaded', 'surchx_stripe_init_gateway_class' );
function surchx_stripe_init_gateway_class() {
 
	require (SURCHX_STRIPE_PLUGIN_PATH.'/surchx-stripe.php');
}