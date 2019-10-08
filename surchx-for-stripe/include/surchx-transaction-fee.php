<?php
/*
 * Description: Plugin file for Surchx API & transaction fee.
 * Author: MB
 *
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

/*****************************API Request to get transaction fee**********************/
/////////////////////////////////////////////////////////////////////////////////////*/

if ( ! function_exists( 'surchx_stripe_api_request' ) ) :
    function surchx_stripe_api_request($data,$payment_method){

        //error_log("surchx " . http_build_query($data) . "\n", 3, "/tmp/surchx.log");

        $response = array();
        if(empty($payment_method)){
            $response['error']="Payment method param not found!";
            return $response;
        }

        $wooOptions = get_option('woocommerce_'.$payment_method.'_settings',array());
        //error_log("surchx options " . http_build_query($wooOptions) . "\n", 3, "/tmp/surchx.log");
        if($wooOptions['testmode']=='yes'){
            //error_log("surchx testmode\n", 3, "/tmp/surchx.log");
            if(empty($wooOptions['test_surchx_token'])){
                //error_log("surchx testmode no token\n", 3, "/tmp/surchx.log");
                $response['error']="SurchX test token not found!";
                return $response;
            }else{
                $surchx_token = $wooOptions['test_surchx_token'];
            }

            $surchx_endpoint = 'https://api-test.surchx.com';

        }else{
            //error_log("surchx livemode\n", 3, "/tmp/surchx.log");

            if(empty($wooOptions['live_surchx_token'])){
                //error_log("surchx livemode no token\n", 3, "/tmp/surchx.log");
                $response['error']="SurchX live token not found!";
                return $response;
            }else{
                $surchx_token = $wooOptions['live_surchx_token'];
            }

            $surchx_endpoint = 'https://api.surchx.com';
        }

        $url    	= $surchx_endpoint.'/v1/ch';
        $headers	= array();
        $headers['Content-type']	= 'application/json';
        $headers['Authorization']	= 'Bearer '.$surchx_token;
        $headers['X-Requested-With']	= 'xhr';

        //error_log("surchx req prep" . $url . "\n", 3, "/tmp/surchx.log");

        $reqData = array(
            'headers'		=> $headers,
            'method'		=> 'POST',
            'httpversion'	=> '1.0',
            'body'			=> json_encode($data),
        );

        //error_log("surchx req " . http_build_query($reqData) . "\n", 3, "/tmp/surchx.log");

        $output = wp_safe_remote_post( $url, $reqData );

        if( is_wp_error( $output ) ) {
            $errmsg = $output->get_error_message();
            error_log("surchx_stripe_api_request error " . $errmsg . "\n", 3, "/tmp/surchx.log");
            error_log("surchx_stripe_api_request error " . $errmsg, 0);
            $response['error'] = $errmsg;
        } else {
            if($output){
                $response['success'] = json_decode($output['body']);
                if (empty($output['body'])) {
                    $msg = $output['response'];
                    error_log("surchx_stripe_api_request error " . http_build_query($msg) . "\n", 3, "/tmp/error.log");
                    $response['error']	 = $msg;
                } else {
                    error_log("surchx_stripe_api_request success " . $output['body'] . "\n", 3, "/tmp/error.log");
                    $response['error']	 = "";
                }
            } else {
                $response['error']	 = 	"No response from SurchX. Please try again!";
            }
        }
        return $response;
    }

endif;


if ( ! function_exists( 'surchx_stripe_sid_request' ) ) :
	function surchx_stripe_sid_request($data,$payment_method){

		$wooOptions = get_option('woocommerce_'.$payment_method.'_settings',array());
		if($wooOptions['testmode']=='yes'){
			if(empty($wooOptions['test_surchx_token'])){
				$response['error']="SurchX test token not found!";
				return $response;
			}else{
				$surchx_token = $wooOptions['test_surchx_token'];
			}

			if(empty($wooOptions['test_surchx_endpoint'])){
				$response['error']="Surchx Fee test Endpoint not found!";
				return $response;
			}else{
				$surchx_endpoint = $wooOptions['test_surchx_endpoint'];
			}
		}else{
			if(empty($wooOptions['live_surchx_token'])){
				$response['error']="SurchX live token not found!";
				return $response;
			}else{
				$surchx_token = $wooOptions['live_surchx_token'];
			}

			if(empty($wooOptions['live_surchx_endpoint'])){
				$response['error']="Surchx Fee live Endpoint not found!";
				return $response;
			}else{
				$surchx_endpoint = $wooOptions['live_surchx_endpoint'];
			}
		}

		$url    	= $surchx_endpoint.'/v1/ch/capture';
		$headers = array();
		$headers['Content-type']	= 'application/json';
		$headers['Authorization']	= 'Bearer '.$surchx_token;
		$headers['X-Requested-With']	= 'xhr';

		$output = wp_remote_post( $url,array(
			'headers'		=> $headers,
			'method'		=> 'POST',
			'httpversion'	=> '1.0',
			'body'			=> json_encode($data),
		));

        if( is_wp_error( $output ) ) {
            $errmsg = $output->get_error_message();
            error_log("surchx_stripe_sid_request error " . $errmsg . "\n", 3, "/tmp/surchx.log");
            error_log("surchx_stripe_sid_request error " . $errmsg, 0);
            return $errmsg;
        } else {
            if($output){
                $res = json_decode($output['body']);
                if (empty($output['body'])) {
                    $msg = $output['response'];
                    error_log("surchx_stripe_sid_request error " . http_build_query($msg) . "\n", 3, "/tmp/error.log");
                } else {
                    error_log("surchx_stripe_sid_request success " . $output['body'] . "\n", 3, "/tmp/error.log");
                }
                return $res;
            } else {
                return "No response from SurchX. Please try again!";
            }
        }
	}

endif;

if ( ! function_exists( 'surchx_stripe_add_transaction_fee_ajax' ) ):
	function surchx_stripe_add_transaction_fee_ajax(){

		$cardnum 			= $_POST['cardnum'];
		$cardnumber 		= substr($cardnum, 0, 6);
		$payment_method 	= $_POST['payment_method'];
		$postcode 			= $_POST['postcode'];
		 $card_processing_fee = WC()->session->get( '_card_processing_fee' );

		 $amount = WC()->cart->cart_contents_total + WC()->cart->shipping_total + WC()->cart->tax_total + WC()->cart->shipping_tax_total;

		$wooOptions = get_option('woocommerce_'.$payment_method.'_settings',array());

		$array = wc_get_base_location();
		if(isset($_POST['s_country'])){
		if($array['country'] == 'US' && $array['s_country']=="US"){
			$country_code = "840";
		}else{
			$responseSurchX['error']="Transaction Fee not available!";
			echo json_encode(array("code"=>202,"error"=>$responseSurchX['error'])); die();
			//WC()->session->set( '_card_processing_fee', 0);

			return $responseSurchX;
		}
		}else{
			if($array['country'] == 'US'){
			$country_code = "840";
		}else{
			$responseSurchX['error']="Transaction Fee not available!";
			echo json_encode(array("code"=>202,"error"=>$responseSurchX['error'])); die();
			//WC()->session->set( '_card_processing_fee', 0);

			return $responseSurchX;
		}
		}

		if($wooOptions['testmode']=='yes'){
			$campaign = array("developerMode", "plugin:woocommerce");
		}else{
			$campaign = array("plugin:woocommerce");
		}
            $sTxId = WC()->session->get( '_surchx_id' );
            $data = array(
                'data' 	=> $campaign,
                'country' 	=> $country_code,
                'region' 	=> $postcode,
                //'processor' => "stripe",
                'nicn' 		=> $cardnumber,
                'amount' 	=> $amount
            );
            if($sTxId!="") {
                $data['sTxId'] = $sTxId;
            }
            if(isset($_POST['payment_method']) && $_POST['payment_method'] !="" && isset($_POST['cardnum']) && $_POST['cardnum'] !="" && isset($_POST['postcode']) && $_POST['postcode'] !=""){
		$responseSurchX = surchx_stripe_api_request($data,$payment_method);
                //WC()->session->set( '_card_processing_fee', 0);
		if(isset($responseSurchX['success'])){
			$body = $responseSurchX['success'];
			if($body->sTxId!=""){
				WC()->session->set( '_card_processing_fee', $body->transactionFee);
    			WC()->session->set( '_surchx_card_num',$cardnum );
    			WC()->session->set( '_surchx_id',$body->sTxId ) ;

                error_log("surchx respond with " . http_build_query($body) . "\n", 3, "/tmp/surchx.log");

                echo json_encode(array("code"=>200,"data"=>$body)); die();

			}else{

					WC()->session->set( '_card_processing_fee', 0);
				echo json_encode( array("code"=>202, "error"=>$body->message) ); die();
			}

		}else{
            error_log("surchx respond error " . http_build_query($responseSurchX) . "\n", 3, "/tmp/surchx.log");

			echo json_encode(array("code"=>202,"error"=>$responseSurchX['error'])); die();
		}
     }

	}
	add_action( 'wp_ajax_surchx_stripe_add_transaction_fee_ajax', 'surchx_stripe_add_transaction_fee_ajax' );
	add_action( 'wp_ajax_nopriv_surchx_stripe_add_transaction_fee_ajax', 'surchx_stripe_add_transaction_fee_ajax' );
endif;




/*****************************API Request to get transaction fee**********************/
///////////////////////////////////////////////////////////////////////////////////////

add_action( 'woocommerce_update_cart_action_cart_updated', 'suchx_stripe_on_action_cart_updated', 20, 1 );
function suchx_stripe_on_action_cart_updated( $cart_updated ){

	//WC()->session->set( '_card_processing_fee',0);

}

function suchx_stripe_on_cart_empty() {
	global $woocommerce;

    if ( !sizeof($woocommerce->cart->cart_contents) ) {
        WC()->session->set( '_card_processing_fee',0);
    }

}
add_action( 'wp_head', 'suchx_stripe_on_cart_empty' );


add_action( 'woocommerce_cart_calculate_fees','suchx_stripe_woocommerce_custom_surcharge' );
function suchx_stripe_woocommerce_custom_surcharge() {
    global $woocommerce;

    $payment_gateways = WC()->payment_gateways->payment_gateways();
    $enable_surchx = $payment_gateways['surchx-stripe']->get_option('enabled');
    $card_processing_fee = WC()->session->get( '_card_processing_fee' );
    if ($enable_surchx == 'yes') {
        	$order = new WC_Order();
		if ( is_cart()) {
			$woocommerce->cart->add_fee( 'Transaction Fee',0, false, '' );
    	}else if (is_checkout()){
			// Make sure that you return false here.  We can't double tax people!
			$order->update_meta_data( '_card_processing_fee', $card_processing_fee );
			$woocommerce->cart->add_fee( 'Transaction Fee',$card_processing_fee, false, '' );
		}else{
            if($_POST && isset($_POST['post_data'])){
                parse_str($_POST['post_data'], $post_data);
                if (is_ajax() && isset($_POST['postcode']) && $_POST['postcode']!="" && isset($post_data['stripe_surchx_ccNo']) && $post_data['stripe_surchx_ccNo']!="") {
					$order->update_meta_data( '_card_processing_fee', $card_processing_fee );
					// Make sure that you return false here.  We can't double tax people!
					$woocommerce->cart->add_fee( 'Transaction Fee',$card_processing_fee, false, '' );
				}else{

                    $woocommerce->cart->add_fee( 'Transaction Fee',0, false, '' );
				}
			}else{
                WC()->session->set( '_card_processing_fee', 0);
                $woocommerce->cart->add_fee( 'Transaction Fee',0, false, '' );
			}
		}
	}
}

if ( ! function_exists( 'surchx_stripe_remove_transaction_fee_ajax' ) ):

	function surchx_stripe_remove_transaction_fee_ajax(){

    	WC()->session->__unset( '_card_processing_fee');

    	WC()->session->__unset( '_surchx_card_num');

    	WC()->session->__unset( '_surchx_id');

    	echo json_encode(array("code"=>200)); die();
	}
	add_action( 'wp_ajax_surchx_stripe_remove_transaction_fee_ajax', 'surchx_stripe_remove_transaction_fee_ajax' );
	add_action( 'wp_ajax_nopriv_surchx_stripe_remove_transaction_fee_ajax', 'surchx_stripe_remove_transaction_fee_ajax' );

endif;

// ADDING NEW COLUMNS FOR TRANSACTION FEE
add_filter( 'manage_edit-shop_order_columns', 'surchx_stripe_shop_order_column', 20 );
function surchx_stripe_shop_order_column($columns)
{
    $reordered_columns = array();

    // Inserting columns to a specific location
    foreach( $columns as $key => $column){
        $reordered_columns[$key] = $column;
        if( $key ==  'order_status' ){
            // Inserting after "Status" column
            $reordered_columns['surchx_fee'] = __( 'Transaction Fee','theme_domain');

        }
    }
        return $reordered_columns;
}

// Adding custom fields meta data for each new column
add_action( 'manage_shop_order_posts_custom_column' , 'surchx_stripe_list_column_content', 20, 2 );
function surchx_stripe_list_column_content( $column, $post_id )
{

    switch ( $column )
    {
        case 'surchx_fee' :

            $the_order = wc_get_order( $post_id );

            if(!empty($the_order->get_items('fee'))){
                foreach( $the_order->get_items('fee') as $item_id => $item_fee ){

                   // The fee name
                   if( $item_fee->get_name()=="Transaction Fee" ){
                        echo $fee_total = wc_price($item_fee->get_total());
                   }else{
					   echo "-";
					}
                }
            }else{
            	echo "-";
            }

            break;
    }
}
