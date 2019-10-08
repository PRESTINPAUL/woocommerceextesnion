<?php
/*
 * Description: Plugin file for stripe payment method integartion.
 * Author: SurchX
 * Stripe SDK Version: 2.0.1
 *
*/
require __DIR__ . '/lib/stripe/vendor/autoload.php';
use \Lindelius\JWT\StandardJWT;
if ( ! defined( 'ABSPATH' ) ) { exit; }

	class WC_Surchx_Stripe_Gateway extends WC_Payment_Gateway {

 		/**
 		 * Class constructor, more about it in Step 3
		  */

 		public function __construct() {

			$this->id 			= 'surchx-stripe';
			$this->icon 		= plugins_url( 'icon/cards.png', __FILE__ );
			$this->has_fields 	= true;
			$this->method_title = 'SurchX for WooCommerce with Stripe';
			$this->method_description = 'SurchX configured for Stripe payment gateway. Pass credit card processing fees to your customers (surcharging). Compliance Guaranteed.';

			$this->supports = array(
				'products'
			);

			// Method with all the options fields
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();
			$this->title 			= $this->get_option( 'title' );
			$this->description 		= $this->get_option( 'description' );
			$this->enabled 			= $this->get_option( 'enabled' );
			$this->testmode 		= 'yes' === $this->get_option( 'testmode' );
			$this->private_key 		= $this->testmode ? $this->get_option('test_private_key') : $this->get_option( 'private_key' );
			$this->publishable_key 	= $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );

			// This action hook saves the settings
			add_action('woocommerce_update_options_payment_gateways_' . $this->id,array( $this,'process_admin_options' ) );

			// custom JavaScript to obtain a token
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

			// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
		 }

		/**
 		 * Plugin options
		  */


     		public function init_form_fields(){
    			$get_surchx_setting = get_option( 'woocommerce_surchx-stripe_settings' );
    			$test_surchx_token = $get_surchx_setting['test_surchx_token'];
    			$live_surchx_token = $get_surchx_setting['live_surchx_token'];
				$live_expiry_date = 'Plugin Live Date - TBD';
				$test_expiry_date = 'Token Expiration Date - TBD';

    			$this->form_fields = array(
    				'enabled' => array(
    					'title'       => 'Enable/Disable',
    					'label'       => 'Enable Stripe Gateway',
    					'type'        => 'checkbox',
    					'description' => '',
    					'default'     => 'yes'
    				),
    				'title' => array(
    					'title'       => 'Title',
    					'type'        => 'text',
    					'description' => 'This controls the title which the user sees during checkout.',
    					'default'     => 'Credit Card (for Stripe - by SurchX)',
    					'desc_tip'    => true,
    				),
    				'description' => array(
    					'title'       => 'Description',
    					'type'        => 'textarea',
    					'description' => 'This controls the description which the user sees during checkout.',
    					'default'     => 'This is the amount it costs us to process your card.',
    				),
    				'testmode' => array(
    					'title'       => 'Test mode',
    					'label'       => 'Enable Test Mode',
    					'type'        => 'checkbox',
    					'description' => 'This will enable the payment gateway to use test API keys. Enabling Test Mode will also automatically enable SurchX to send requests to test endpoint.',
    					'default'     => 'yes',
    					'desc_tip'    =>  true,
    				),
    				'test_publishable_key' => array(
    					'title'       => 'Test Publishable key',
    					'type'        => 'text'
    				),
    				'test_private_key' => array(
    					'title'       => 'Test Secret key',
    					'type'        => 'password',
    				),
    				'publishable_key' => array(
    					'title'       => 'Live Publishable Key',
    					'type'        => 'text'
    				),
    				'private_key' => array(
    					'title'       => 'Live Secret key',
    					'type'        => 'password'
    				),
    			);

				if(!empty($test_surchx_token) && !empty($live_surchx_token)){
					try{$testDecodedJwt = StandardJWT::decode($test_surchx_token);
						$testIat = $testDecodedJwt->getClaim('iat');}catch(Exception $e) {$testIat = '';}
					try{$testDecodedJwt = StandardJWT::decode($live_surchx_token);
						$liveIat = $testDecodedJwt->getClaim('iat');}catch(Exception $e) {$liveIat = '';}
					if(!empty($testIat)){
						$jwt_decoded_date = gmdate("Y-m-d\TH:i:s\Z", $testIat);
						$test_date = date('l jS F (m/d/Y)', strtotime('+60 days',strtotime( $jwt_decoded_date )));
						$test_expiry_date = 'Token Expiration Date - '.$test_date;		
					}else{
						$test_expiry_date = 'Test Token is not valid!';
					
					}
					if(!empty($liveIat)){
						$jwt_decoded_date = gmdate("Y-m-d\TH:i:s\Z", $liveIat);
						$live_date = date('l jS F (m/d/Y)', strtotime('+31 days',strtotime( $jwt_decoded_date )));
						$live_expiry_date = 'Token Expiration Date - '.$live_date;		
					}else{
						$live_expiry_date = 'Live Token is not valid!';
					
					}
				}else if(!empty($test_surchx_token)){
    				try{$testDecodedJwt = StandardJWT::decode($test_surchx_token);
    				$iat = $testDecodedJwt->getClaim('iat');}catch(Exception $e) {$iat = '';}
    				if(!empty($iat)){
    					$jwt_decoded_date = gmdate("Y-m-d\TH:i:s\Z", $iat);
    					$test_date = date('l jS F (m/d/Y)', strtotime('+60 days',strtotime( $jwt_decoded_date )));
						$live_date = date('l jS F (m/d/Y)', strtotime('+31 days',strtotime( $jwt_decoded_date )));
						$test_expiry_date = 'Token Expiration Date - '.$test_date;
						$live_expiry_date = 'Plugin Live Date - '.$live_date;
    				}else{
						$test_expiry_date = 'Test Token is not valid!';
						$live_expiry_date = 'Plugin Live Date - TBD';
					}
				}else if(!empty($live_surchx_token)){
    				try{$liveDecodedJwt = StandardJWT::decode($live_surchx_token);
    				$iat = $liveDecodedJwt->getClaim('iat');}catch(Exception $e) {$iat = '';}
    				if(!empty($iat)){
    					$jwt_decoded_date = gmdate("Y-m-d\TH:i:s\Z", $iat);
						$live_date = date('l jS F (m/d/Y)', strtotime('+31 days',strtotime( $jwt_decoded_date )));
						$live_expiry_date = 'Plugin Live Date - '.$live_date;
					}
					else{
						$live_expiry_date = 'Live Token is not valid!';
					}
    			}
				$this->form_fields['test_surchx_token'] = array(
					'title'       => 'Test SurchX Token',
					'type'        => 'text',
					'description' => $test_expiry_date
				);
				$this->form_fields['live_surchx_token'] = array(
					'title'       => 'Live SurchX Token',
					'type'        => 'text',
					'description' => $live_expiry_date
				);
				}
						/*
		 * Add custom credit card form
		 */
		public function payment_fields() {

			if ( $this->description ) {

				if ( $this->testmode ) {
					$this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="https://api-docs.surchx.com/testtransactiondata/" target="_blank">documentation</a>.';
					$this->description  = trim( $this->description );
				}

				echo wpautop( wp_kses_post( $this->description ) );
			}

			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

			do_action( 'woocommerce_credit_card_form_start', $this->id );

			echo '<div class="form-row form-row-wide">
					<label>Card Number <span class="required">*</span></label>
					<input id="stripe_surchx_ccNo" class="surchx_ccNo" name="stripe_surchx_ccNo" type="text" value="" autocomplete="off" maxlength="19" placeholder="xxxx xxxx xxxx xxxx" inputmode="numeric">
				</div>
				<div class="form-row form-row-first">
					<label>Expiry Date <span class="required">*</span></label>
					<input id="stripe_surchx_expdate" name="stripe_surchx_expdate" type="text" autocomplete="off" placeholder="MM / YYYY" inputmode="numeric">
				</div>
				<div class="form-row form-row-last">
					<label>Card Code (CVV) <span class="required">*</span></label>
					<input id="stripe_surchx_cvv" type="password" name="stripe_surchx_cvv" autocomplete="off" placeholder="CVV" inputmode="numeric">
				</div>
				<div class="clear"></div><input type="hidden" name="_card_processing_fee" value="2" id="woocommerce-Price-amount">';

			do_action( 'woocommerce_credit_card_form_end', $this->id );

			echo '<div class="clear"></div></fieldset>';

			echo '<input id="surchxStripeToken" type="hidden" name="surchxStripeToken">';

			echo "<script>
						payform.cardNumberInput(document.getElementById('stripe_surchx_ccNo'));
						payform.expiryInput(document.getElementById('stripe_surchx_expdate'));
						payform.cvcInput(document.getElementById('stripe_surchx_cvv'));
				</script>";
		}

		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
		public function payment_scripts() {

			// we need JavaScript to process a token only on cart/checkout pages, right?
			if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
				return;
			}

			// if our payment gateway is disabled, we do not have to enqueue JS too
			if ( 'no' === $this->enabled ) {
				return;
			}

			/*if ( 'no' === $this->enable_surchx ) {
				return;
			}*/

			// no reason to enqueue JavaScript if API keys are not set
			if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
				return;
			}

			// do not work with card detailes without SSL unless your website is in a test mode
			if ( ! $this->testmode && ! is_ssl() ) {

				return;
			}

			// let's suppose it is our payment processor JavaScript that allows to obtain a token
			wp_enqueue_script( 'surchx_js', 'https://js.stripe.com/v2/' );

			// and this is our custom JS in your plugin directory that works with token.js
			wp_register_script( 'woocommerce_surchx', plugins_url( 'js/surchx-stripe.js', __FILE__ ), array( 'jquery') );

			// in most payment processors you have to use PUBLIC KEY to obtain a token
			wp_localize_script( 'woocommerce_surchx', 'surchx_params', array('publishableKey' => $this->publishable_key) );

			wp_enqueue_script( 'woocommerce_surchx' );

		}

		/*
		* Fields validation, more in Step 5
		*/
		public function validate_fields(){

			if( empty( $_POST[ 'stripe_surchx_ccNo' ]) || empty( $_POST[ 'stripe_surchx_expdate' ]) || empty( $_POST[ 'stripe_surchx_cvv' ])  ) {
				wc_add_notice(  '<strong>Card Details</strong> are required!', 'error' );
				return false;
			}

			return true;

		}

		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {

			global $woocommerce;

			// we need it to get any order detailes
			$order = wc_get_order( $order_id );

			if(empty($_POST['surchxStripeToken'])){

				wc_add_notice(  'Connection error. Token not found!', 'error' );
				return;

			}

			require_once(SURCHX_STRIPE_PLUGIN_PATH.'/lib/stripe/init.php');

		 	//set api key
		    $stripe = array(
		      "secret_key"      => $this->private_key,
		      "publishable_key" => $this->publishable_key
		    );


		    \Stripe\Stripe::setApiKey($stripe['secret_key']);


		    //add customer to stripe
		    $email = $_POST['billing_email'];
		    $token = $_POST['surchxStripeToken'];

		    // Error through on email empty.
		    if(empty($email)){
		    	wc_add_notice(  'Connection error. Billing email not found.', 'error' );
				return;
		    }

		    $success  = 0;
		    $chargeID = '';
		    try {
			   	//charge a credit or a debit card
			    $charge = \Stripe\Charge::create(array(
			        'source' 		=> $token,
			        'amount'   		=> $order->get_total()*100,
			        'currency' 		=> get_option('woocommerce_currency'),
			        'description' 	=> "WooCommerce payment for Order ID: ".$order_id,
			        'metadata' 		=> array(
								            'order_id' => $order_id
								        )
			    ));

			 	$chargeID = $charge->id;
			    $success  = 1;

			}  catch (Exception $e) {
				  // Something else happened, completely unrelated to Stripe
				  $error = $e->getMessage();
			}

		    if($success == 1){

				$order->payment_complete();

				$order->reduce_order_stock();

				// some notes to customer (replace true with false to make it private)
				$order->add_order_note( 'Hey, your order is paid! Thank you!', true );

				if($this->enabled=='yes' && WC()->session->get('_surchx_id')!=""){
					$idData = array(
						"mTxId"		=> $order_id,
						"sTxId"		=> WC()->session->get('_surchx_id'),
						"authCode"	=> $chargeID
					);
					$payment_method = $_POST['payment_method'];
					$get_sTxId 		= surchx_stripe_sid_request($idData,$payment_method);

				}
				// Empty cart
				$woocommerce->cart->empty_cart();

				WC()->session->__unset( '_card_processing_fee');

    			WC()->session->__unset( '_surchx_card_num');

    			WC()->session->__unset( '_surchx_id');

    			// Redirect to the thank you page
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url( $order )
				);

			}else{
				wc_add_notice(  $error, 'error' );
				return;
			}

		}

		/*
		 * In case you need a webhook
		 */
		public function webhook() {

				$order = wc_get_order( $_GET['id'] );
				$order->payment_complete();
				$order->reduce_order_stock();

				update_option('webhook_debug', $_GET);
		}
	}
