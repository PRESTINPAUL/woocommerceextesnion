<?php
/*
 * Description: Plugin file for authorize.net payment method integartion.
 * Author: SurchX
 * Stripe SDK Version: 2.0.1
 *
*/
require __DIR__ . '/lib/authorize-net/vendor/autoload.php';
use \Lindelius\JWT\StandardJWT;
if ( ! defined( 'ABSPATH' ) ) { exit; }
	// Common setup for API credentials

	require_once(SURCHX_AUTHORIZE_PLUGIN_PATH.'/lib/authorize-net/vendor/autoload.php');

	use net\authorize\api\contract\v1 as AnetAPI;
	use net\authorize\api\controller as AnetController;

	class WC_Surchx_Authorize_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
 
			$this->id 			= 'surchx-authorize'; 
			$this->icon 		= plugins_url( 'icon/cards.png', __FILE__ ); 
			$this->has_fields 	= true; 
			$this->method_title = 'SurchX for WooCommerce with Authorize.Net';
			$this->method_description = 'SurchX configured for Authorize.Net payment gateway. Pass credit card processing fees to your customers (surcharging). Compliance Guaranteed.'; 

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
			$this->testmode 		= $this->get_option( 'testmode' );
			$this->transaction_key 	= $this->get_option('transaction_key');
			$this->login_key 		= $this->get_option( 'login_key' ) ;
		 
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
			$get_surchx_setting = get_option( 'woocommerce_surchx-authorize_settings' );
			$test_surchx_token = $get_surchx_setting['test_surchx_token'];
			$live_surchx_token = $get_surchx_setting['live_surchx_token'];
			$live_expiry_date = 'Plugin Live Date - TBD';
				$test_expiry_date = 'Token Expiration Date - TBD';
			$this->form_fields = array(
				'enabled' => array(
					'title'       => 'Enable/Disable',
					'label'       => 'Enable Authorize.Net Gateway',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'yes'
				),
				'title' => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'Credit Card (for Authorize.Net - by SurchX)',
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => 'This is the amount it costs us to process your card.',
				),
				'testmode' => array(
					'title'       => 'Mode',
					'label'       => 'Production/Sandbox Mode',
					'type'        => 'select',
					'description' => 'Selecting sandbox mode will enable the payment gateway to use test API keys. Selecting sandbox mode will also automatically enable SurchX to send requests to test endpoint.',
					'default'     => '1',
					'desc_tip'    =>  true,
					'options'       => array(
								    	'0'	=> __( 'Production', 'wps' ),
								        '1'	=> __( 'Sandbox', 'wps' ),
								    )
				),
				'login_key' => array(
					'title'       => 'Login ID',
					'type'        => 'text'
				),
				'transaction_key' => array(
					'title'       => 'Transaction Key',
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
		 
		 	$fieldsetID = "wc-". esc_attr( $this->id ) ."-cc-form";

			echo '<fieldset id="'. $fieldsetID . '" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
			
			do_action( 'woocommerce_credit_card_form_start', $this->id );

			echo '<div class="form-row form-row-wide">
					<label>Card Number <span class="required">*</span></label>
					<input id="authorize_surchx_ccNo" class="surchx_ccNo" name="authorize_surchx_ccNo" type="text" value="" autocomplete="off" maxlength="19" placeholder="xxxx xxxx xxxx xxxx" inputmode="numeric">
				</div>
				<div class="form-row form-row-first">
					<label>Expiry Date <span class="required">*</span></label>
					<input id="authorize_surchx_expdate" name="authorize_surchx_expdate" type="text" autocomplete="off" placeholder="MM / YYYY" inputmode="numeric">
				</div>
				<div class="form-row form-row-last">
					<label>Card Code (CVV) <span class="required">*</span></label>
					<input id="authorize_surchx_cvv" type="password" name="authorize_surchx_cvv" autocomplete="off" placeholder="CVV" inputmode="numeric">
				</div>
				<div class="clear"></div><input type="hidden" name="_card_processing_fee" value="2" id="woocommerce-Price-amount">';
		 
			do_action( 'woocommerce_credit_card_form_end', $this->id );
		 
			echo '<div class="clear"></div></fieldset>';

			echo "<script>
						payform.cardNumberInput(document.getElementById('authorize_surchx_ccNo'));
						payform.expiryInput(document.getElementById('authorize_surchx_expdate'));
						payform.cvcInput(document.getElementById('authorize_surchx_cvv'));
						
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
 
 	
			// no reason to enqueue JavaScript if API keys are not set
			if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
				return;
			}
		 
			// do not work with card detailes without SSL unless your website is in a test mode
			if ( $this->testmode == 0 && ! is_ssl() ) {

				return;
			}

			// and this is our custom JS in your plugin directory that works with token.js
			wp_register_script( 'woocommerce_surchx', plugins_url( 'js/surchx-authorize.js', __FILE__ ), array( 'jquery') );

			wp_enqueue_script( 'woocommerce_surchx' );

		}

		/*
		* Fields validation, more in Step 5
		*/
		public function validate_fields(){
		 
			if( empty( $_POST[ 'authorize_surchx_ccNo' ]) || empty( $_POST[ 'authorize_surchx_expdate' ]) || empty( $_POST[ 'authorize_surchx_cvv' ])  ) {
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

			if (version_compare(phpversion(), '7.2.0', '<')) {
				wc_add_notice(  "<strong>Error:</strong> PHP version 7.2.0 is required! Current version is ".phpversion()."." , 'error' );
						
	            return;
	        }

			// we need it to get any order detailes
			$order 		= wc_get_order( $order_id );

			$ccNo 		= str_replace(' ','',$_POST['authorize_surchx_ccNo']);
			$expdate 	= $_POST['authorize_surchx_expdate'];
			$cvv 		= str_replace(' ','',$_POST['authorize_surchx_cvv']);

			$expdateArr = explode(" / ", $expdate);
			$expdate 	= $expdateArr[0].$expdateArr[1];

			$billing_first_name	= $_POST['billing_first_name'];
			$billing_last_name 	= $_POST['billing_last_name'];
			$billing_address_1 	= $_POST['billing_address_1'];
			$billing_city 		= $_POST['billing_city'];
			$billing_state 		= $_POST['billing_state'];
			$billing_postcode 	= $_POST['billing_postcode'];
			$billing_country 	= $_POST['billing_country'];
			$billing_phone 		= $_POST['billing_phone'];
			$billing_email 		= $_POST['billing_email'];
			

			$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
			$merchantAuthentication->setName($this->login_key);
			$merchantAuthentication->setTransactionKey($this->transaction_key);
			$refId = 'ref' . time();

			//While testing, create the payment data for a credit card
			$creditCard = new AnetAPI\CreditCardType();
			$creditCard->setCardNumber($ccNo);
			$creditCard->setExpirationDate($expdate);
			$creditCard->setCardCode($cvv);

			$paymentOne = new AnetAPI\PaymentType();
			$paymentOne->setCreditCard($creditCard);
			$orderSet = new AnetAPI\OrderType();
			$orderSet->setDescription("New Order Description");

			//create a transaction
			$transactionRequestType = new AnetAPI\TransactionRequestType();
			$transactionRequestType->setTransactionType("authCaptureTransaction");
			$transactionRequestType->setAmount($order->get_total());
			$transactionRequestType->setOrder($orderSet);
			$transactionRequestType->setPayment($paymentOne);

			//Prepare customer information object from your form $_POST data
			$cust = new AnetAPI\CustomerAddressType();
			$cust->setFirstName($billing_first_name);
			$cust->setLastName($billing_last_name);
			$cust->setAddress($billing_address_1);
			$cust->setCity($billing_city);
			$cust->setState($billing_state);
			$cust->setCountry($billing_country);
			$cust->setZip($billing_postcode );
			$cust->setPhoneNumber($billing_phone);
			$cust->setEmail($billing_email);

			$transactionRequestType->setBillTo($cust);
			$request = new AnetAPI\CreateTransactionRequest();

			$request->setMerchantAuthentication($merchantAuthentication);
			$request->setRefId($refId);
			$request->setTransactionRequest($transactionRequestType);

			define('SANDBOX', "https://apitest.authorize.net");
			define('PRODUCTION', "https://api2.authorize.net");

			//Get response from Authorize.net
			$controller = new AnetController\CreateTransactionController($request);

			if($this->testmode==1){
		 		$response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
				
			}else{

		 		$response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
			}
			 
			
			if ($response != null) {

				 if ($response->getMessages()->getResultCode() == "Ok") {
				 	$tresponse = $response->getTransactionResponse();
					 if ($tresponse != null && $tresponse->getMessages() != null) {

						$order->payment_complete();

						$order->reduce_order_stock();

						// some notes to customer (replace true with false to make it private)
						$order->add_order_note( 'Hey, your order is paid! Thank you! '.$tresponse->getMessages()[0]->getDescription(), true );

						$idData = array(
							"mTxId"		=> $order_id,
							"sTxId"		=> WC()->session->get('_surchx_id'),
							"authCode"	=> $tresponse->getAuthCode()
						);
						
						$payment_method 		= $_POST['payment_method'];

						$get_sTxId = surchx_authorize_sid_request($idData,$payment_method );

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
						wc_add_notice(  " Error code : " . $tresponse->getErrors()[0]->getErrorCode() . "\n"." Error message : " . $tresponse->getErrors()[0]->getErrorText() . "\n", 'error' );
						return;
						
					}
				} else {
					
					$tresponse = $response->getTransactionResponse();
					if ($tresponse != null && $tresponse->getErrors() != null) {

						wc_add_notice(  " Error code : " . $tresponse->getErrors()[0]->getErrorCode() . "\n"." Error message : " . $tresponse->getErrors()[0]->getErrorText() . "\n", 'error' );
						return;
						
				 	} else {

				 		wc_add_notice(  " Error code : " .$response->getMessages()->getMessage()[0]->getCode() . "\n"." Error message : " . $response->getMessages()->getMessage()[0]->getText() . "\n", 'error' );
						return;

				 	}
				}
			} else {

			 	wc_add_notice(  "No response returned \n", 'error' );
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

