<?php
/*
 * Description: Plugin filw for stripe payment method integartion.
 * Author: Surchx
 * Stripe SDK Version: 2.0.1
 *
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'surchx_authorize_add_gateway_class' );

function surchx_authorize_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Surchx_Authorize_Gateway'; // your class name is here
	return $gateways;
}

function stripe_authorize_admin_enqueue_scripts($hook) {

    if ('woocommerce_page_wc-settings' !== $hook) {
        return;
    }
    wp_enqueue_script( 'surchx_admin_main', plugins_url( 'js/surchx-admin.js', __FILE__ ) );
}

add_action('admin_enqueue_scripts', 'stripe_authorize_admin_enqueue_scripts');


add_action( 'plugins_loaded', 'surchx_authorize_init_gateway_class' );
function surchx_authorize_init_gateway_class() {
 
	require (SURCHX_AUTHORIZE_PLUGIN_PATH.'/surchx-authorize-net.php');
}


