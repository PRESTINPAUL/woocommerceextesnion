<?php
/*
 * Plugin Name: SurchX for WooCommerce with Authorize.net
 * Description: SurchX allows you to pass your credit card processing fees to your customers (surcharge). We keep you in compliance with the more than 65 regulatory bodies with their fingers in the pie. It is much more complicated than you would think.
 * Author: SurchX
 * Author URI: https://www.surchx.com/
 * Version: 2.0.0
 * Requires WooCommerce
 * WC requires at least: 3.0
 * WC tested up to: 3.5
 * Text Domain: surchx-surcharge
 * Copyright: © 2018 Surchx authorize Payment Gateway.
 * License: General Public License v1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define("SURCHX_AUTHORIZE_PLUGIN_PATH",dirname(__FILE__));
define("SURCHX_AUTHORIZE_OPTIONS",get_option('woocommerce_surchx-authorize_settings',array()));

class SurchxSurchargeAuthorize {
    function __construct() {

        // Don't run anything else in the plugin, if WooCommerce not active
        add_action( 'admin_init', array( $this, 'check_woocommerce_runtime' ) );

        if ( ! self::check_woocommerce_plugin() ) {
            return false;
        }

        require (SURCHX_AUTHORIZE_PLUGIN_PATH.'/include/surchx-transaction-fee.php');
        require (SURCHX_AUTHORIZE_PLUGIN_PATH.'/surchx-gateways.php');

        add_action( 'wp_enqueue_scripts', array( $this, 'surchx_plugin_enqueue_style' ) );

    }


    static function surchx_show_notice() {
        ?>
        <div class="error notice">
            <p><?php _e( 'PHP version 7.2 is requireed for SurchX Surcharge plugin!', 'suchx-plugin' ); ?></p>
        </div>
        <?php
    }


    // The primary sanity check, automatically disable the plugin on activation if it doesn't meet minimum requirements.
    static function surchx_activation_check() {
        if ( ! self::check_woocommerce_plugin() ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( __( 'WooCommerce plugin is require for Surchx for Authorize.net!', 'suchx-plugin' ) );
        }
    }

    // The backup sanity check, in case the plugin is activated in a weird way,
    function check_woocommerce_runtime() {
        if ( ! self::check_woocommerce_plugin() ) {
            add_action( 'admin_notices', array( $this, 'disabled_notice' ) );
        }

    }

    function disabled_notice() {
        echo '<div class="notice notice-warning is-dismissible error">
                <p><strong>WooCommerce plugin is require for Surchx for Authorize.net!</strong></p>
                </div>';
    }

    static function check_woocommerce_plugin() {

        /*if(!in_array( 'woocommerce/woocommerce.php',apply_filters('active_plugins', get_option( 'active_plugins' )))){
            return false;
        }
        return true;*/

        if ( ! function_exists( 'is_plugin_active' ) )
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            if(is_plugin_active('woocommerce/woocommerce.php')){
                return true;
            }
            return false;
        }


    static function check_php_version() {

        if (version_compare(phpversion(), '7.2.0', '<')) {
            return false;
        }

        return true;
    }

    /**
     * Enqueue scripts and styles
     */
    function surchx_plugin_enqueue_style() {

        wp_enqueue_style( 'surchx-authorize-style', plugins_url( 'css/surchx-style.css', __FILE__ ));

        wp_register_script( 'woocommerce_authorize_payform', plugins_url( 'js/payform.js', __FILE__ ), array( 'jquery') );

        wp_enqueue_script( 'woocommerce_authorize_payform' );

        wp_register_script( 'surchx_authorize_main', plugins_url( 'js/surchx-main.js', __FILE__ ), array( 'jquery') );

        $payment_gateways = WC()->payment_gateways->payment_gateways();
        $enable_surchx = $payment_gateways['surchx-authorize']->get_option('enabled');

        $testmode = $payment_gateways['surchx-authorize']->get_option('testmode');

        $config = array(
        'admin_ajax_url' => admin_url( 'admin-ajax.php' ),
        'enable_surchx' => $enable_surchx,
        'testmode' => $testmode
        );
        wp_localize_script( 'surchx_authorize_main', 'surchx_authorize_main_params', array('admin_ajax_url' => admin_url( 'admin-ajax.php' ),'enable_surchx' => $enable_surchx) );

        wp_enqueue_script( 'surchx_authorize_main' );

    }
}

global $suchxMain;

$SurchxSurchargeAuthorize = new SurchxSurchargeAuthorize();

register_activation_hook( __FILE__, array( 'SurchxSurchargeAuthorize', 'surchx_activation_check' ) );

add_filter("plugin_action_links_".plugin_basename(__FILE__), 'surchx_authorize_plugin_add_settings_link');

// addd link to configrations page under plugin listing
function surchx_authorize_plugin_add_settings_link( $links ) {

    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout">'.__( 'Settings' ).'</a>';
    array_push( $links, $settings_link );

    return $links;

}

function surchx_my_enqueue($hook) {
    // Only add to the edit.php admin page.
    // See WP docs.
    // if ('edit.php' !== $hook) {
    //     return;
    // }
    wp_enqueue_script('my_custom_script', plugins_url( 'js/surchx-admin.js', __FILE__ ));
}

add_action('admin_head', 'surchx_my_enqueue');