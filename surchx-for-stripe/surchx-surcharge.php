<?php
/*
 * Plugin Name: SurchX for WooCommerce with Stripe
 * Description: SurchX allows you to pass your credit card processing fees to your customers (surcharge). We keep you in compliance with the more than 65 regulatory bodies with their fingers in the pie. It is much more complicated than you would think.
 * Author: SurchX
 * Author URI: https://www.surchx.com/
 * Version: 1.1.0
 * Requires WooCommerce
 * WC requires at least: 3.0
 * WC tested up to: 3.5.5
 * Text Domain: surchx-for-stripe
 * Copyright: © 2018 Surchx Stripe Payment Gateway.
 * License: General Public License v1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define("SURCHX_STRIPE_PLUGIN_PATH",dirname(__FILE__));
define("SURCHX_STRIPE_PLUGIN_ID",basename(dirname(__FILE__))."/".basename(__FILE__));
define("SURCHX_PLUGIN_OPTIONS", "woocommerce_stripe_settings");

class SurchxSurchargeStripe {
  function __construct() {

    // Don't run anything else in the plugin, if WooCommerce not active
    add_action( 'admin_init', array( $this, 'check_woocommerce_runtime' ) );
    if ( ! self::check_woocommerce_plugin() ) {
      return false;
    }

    require (SURCHX_STRIPE_PLUGIN_PATH.'/include/surchx-transaction-fee.php');
    require (SURCHX_STRIPE_PLUGIN_PATH.'/surchx-gateways.php');
    add_action( 'wp_enqueue_scripts', array( $this, 'surchx_plugin_enqueue_style' ) );
  }


  static function surchx_show_notice() {
?>
        <div class="error notice">
            <p><?php _e( 'PHP version 7.2 is requireed for SurchX Surcharge plugin!', 'surchx-for-stripe' ); ?></p>
        </div>
<?php
  }


  // The primary sanity check, automatically disable the plugin on activation if it doesn't meet minimum requirements.
  static function surchx_activation_check() {
    if ( ! self::check_woocommerce_plugin() ) {
      deactivate_plugins( plugin_basename( __FILE__ ) );
      wp_die( __( 'WooCommerce plugin is require for WooCommerce Surchx for Stripe!', 'surchx-for-stripe' ) );
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
      <p><strong>WooCommerce plugin is require for WooCommerce Surchx for Stripe!</strong></p>
      </div>';
  }

  static function check_woocommerce_plugin() {
    /*
      in_array( 'woocommerce/woocommerce.php',apply_filters('active_sitewide_plugins', get_site_option( 'active_sitewide_plugins' )))
      || in_array( 'woocommerce/woocommerce.php',apply_filters('active_plugins', get_site_option( 'active_plugins' )))){
      print_r(is_plugin_active('woocommerce/woocommerce.php'))
     */
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
    wp_enqueue_style( 'surchx-stripe-style', plugins_url( 'css/surchx-style.css', __FILE__ ));

    wp_register_script( 'woocommerce_stripe_payform', plugins_url( 'js/payform.js', __FILE__ ), array( 'jquery') );

    wp_enqueue_script( 'woocommerce_stripe_payform' );

    wp_register_script( 'surchx_stripe_main', plugins_url( 'js/surchx-main.js', __FILE__ ), array( 'jquery') );

    // this seems to be the more appropriate way to test if the gateway is enabled
    $payment_gateways = WC()->payment_gateways->payment_gateways();
    $enable_surchx = $payment_gateways['surchx-stripe']->get_option('enabled');

    $testmode = $payment_gateways['surchx-stripe']->get_option('testmode');

    $config = array(
        'admin_ajax_url' => admin_url( 'admin-ajax.php' ),
        'enable_surchx' => $enable_surchx,
        'testmode' => $testmode
    );
    wp_localize_script( 'surchx_stripe_main', 'surchx_main_params', $config);

    wp_enqueue_script( 'surchx_stripe_main' );

  }
}

global $suchxMain;

$SurchxSurchargeStripe = new SurchxSurchargeStripe();

register_activation_hook( __FILE__, array( 'SurchxSurchargeStripe', 'surchx_activation_check' ) );

add_filter("plugin_action_links_".plugin_basename(__FILE__), 'surchx_stripe_plugin_add_settings_link');

// addd link to configrations page under plugin listing
function surchx_stripe_plugin_add_settings_link( $links ) {

  $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout">'.__( 'Settings' ).'</a>';
  array_push( $links, $settings_link );

  return $links;

}
