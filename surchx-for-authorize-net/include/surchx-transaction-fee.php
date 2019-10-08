<?php
/*
 * Description: Plugin file for Surchx API & transaction fee.
 * Author: MB
 *
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

/*****************************API Request to get transaction fee**********************/
/////////////////////////////////////////////////////////////////////////////////////*/

if ( ! function_exists( 'surchx_authorize_api_request' ) ) :
	function surchx_authorize_api_request($data,$payment_method){


 		$response = array();
		if(empty($payment_method)){
			$response['error']="Payment method param not found!";
			return $response;
		}


		$wooOptions = get_option('woocommerce_'.$payment_method.'_settings',array());
		if($wooOptions['testmode']==1){
			if(empty($wooOptions['test_surchx_token'])){
				$response['error']="SurchX test token not found!";
				return $response;
			}else{
				$surchx_token = $wooOptions['test_surchx_token'];
			}
			$surchx_endpoint = 'https://api-test.surchx.com';
		}else{
			if(empty($wooOptions['live_surchx_token'])){
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

		$output = wp_remote_post( $url,array(
			'headers'		=> $headers,
			'method'		=> 'POST',
			'httpversion'	=> '1.0',
			'body'			=> json_encode($data),
		));

		if($output){

			$response['success'] = json_decode($output['body']);
			$response['error']	 = "";
			return $response;

		}else{
			$response['error']	 = 	"No response from SurchX. Please try again!";
			return $response;
		}
		return ;
	}

endif;


if ( ! function_exists( 'surchx_authorize_sid_request' ) ) :
	function surchx_authorize_sid_request($data,$payment_method){

		$wooOptions = get_option('woocommerce_'.$payment_method.'_settings',array());
		if($wooOptions['testmode']==1){
			if(empty($wooOptions['test_surchx_token'])){
				$response['error']="SurchX test token not found!";
				return $response;
			}else{
				$surchx_token = $wooOptions['test_surchx_token'];
				$surchx_endpoint = "https://api-test.surchx.com";
			}
		}else{
			if(empty($wooOptions['live_surchx_token'])){
				$response['error']="SurchX live token not found!";
				return $response;
			}else{
				$surchx_token = $wooOptions['live_surchx_token'];
				$surchx_endpoint = "https://api.surchx.com";
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

		if($output){
			$res = json_decode($output['body']);
			return $res;
		}
		return;
	}

endif;

if ( ! function_exists( 'surchx_authorize_add_transaction_fee_ajax' ) ):
	function surchx_authorize_add_transaction_fee_ajax(){

		$amount = WC()->cart->cart_contents_total + WC()->cart->shipping_total + WC()->cart->tax_total + WC()->cart->shipping_tax_total;
		$call_from = '';

		if($_POST['postcode'] == 'admin'){
			$call_from = 'admin';
			$id = $_POST['post_id'];
			$order = wc_get_order( $id );
            $_POST['postcode'] = $order->get_shipping_postcode();
            if(empty($_POST['postcode'])){
            $_POST['postcode'] = $order->get_billing_postcode();
            }
            global $wpdb;
            $get_current_fee = $wpdb->get_row("SELECT meta_value from wp_woocommerce_order_itemmeta join wp_woocommerce_order_items  where wp_woocommerce_order_items.order_item_id = wp_woocommerce_order_itemmeta.order_item_id AND wp_woocommerce_order_items.order_id = '".$id."' AND wp_woocommerce_order_items.order_item_name = 'Transaction Fee' AND wp_woocommerce_order_itemmeta.meta_key='_fee_amount'");

           $saved_trans_fee = $get_current_fee->meta_value;
           if($saved_trans_fee > 0){
           	$amount = $order->get_total() - $saved_trans_fee;
           }else{
           	$amount = $order->get_total();
           }
		}

		$cardnum = str_replace(' ', '', $_POST['cardnum']);
		$cardnumber 		= substr($cardnum, 0, 6);
		$payment_method 	= $_POST['payment_method'];
		$postcode 			= $_POST['postcode'];
		 $card_processing_fee = WC()->session->get( '_card_processing_fee' );

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

		if($wooOptions['testmode']==1){
            $campaign = array("developerMode", "plugin:woocommerce");
		}else{
			$campaign = array("plugin:woocommerce");
		}
		$sTxId = WC()->session->get( '_surchx_id' );

		$data = array(
			'data' 	=> $campaign,
			'country' 	=> $country_code,
			'region' 	=> $postcode,
			'nicn' 		=> $cardnumber,
			'amount' 	=> $amount
		);
		//if($sTxId!="") {
			//$data['sTxId'] = $sTxId;
		//}
		if($call_from == 'admin'){
			if($id!="") {
				$data['mTxId'] = $id;
			}
		} else{
			if($sTxId!="") {
				$data['sTxId'] = $sTxId;
			}
		}
  if(isset($_POST['payment_method']) && $_POST['payment_method'] !="" && isset($_POST['cardnum']) && $_POST['cardnum'] !="" && isset($_POST['postcode']) && $_POST['postcode'] !=""){
		$responseSurchX = surchx_authorize_api_request($data,$payment_method);
		//WC()->session->set( '_card_processing_fee', 0);
		if($responseSurchX['success']){
			$body = $responseSurchX['success'];

			if($body->message=="ok"){

				WC()->session->set( '_card_processing_fee',$body->transactionFee );
    			WC()->session->set( '_surchx_card_num',$cardnum );
    			WC()->session->set( '_surchx_id',$body->sTxId ) ;

    			if($call_from == 'admin'){
    			addtransfeetoDB($id);
    			$newTotal = $order->calculate_totals();
                $order->set_total($newTotal);
                $order->save();
    			}

    			echo json_encode(array("code"=>200,"data"=>$body)); die();

			}else{

					WC()->session->set( '_card_processing_fee', 0);
				echo json_encode( array("code"=>202, "error"=>$body->message) ); die();
			}

		}else{


			echo json_encode(array("code"=>202,"error"=>$responseSurchX['error'])); die();
		}
     }

	}
	add_action( 'wp_ajax_surchx_authorize_add_transaction_fee_ajax', 'surchx_authorize_add_transaction_fee_ajax' );
	add_action( 'wp_ajax_nopriv_surchx_authorize_add_transaction_fee_ajax', 'surchx_authorize_add_transaction_fee_ajax' );
endif;




/*****************************API Request to get transaction fee**********************/
///////////////////////////////////////////////////////////////////////////////////////

add_action( 'woocommerce_update_cart_action_cart_updated', 'suchx_authorize_on_action_cart_updated', 20, 1 );
function suchx_authorize_on_action_cart_updated( $cart_updated ){

	//WC()->session->set( '_card_processing_fee',0);

}










function suchx_on_cart_empty() {
	global $woocommerce;

    if ( !sizeof($woocommerce->cart->cart_contents) ) {
        WC()->session->set( '_card_processing_fee',0);
    }

}
add_action( 'wp_head', 'suchx_on_cart_empty' );

add_action( 'woocommerce_cart_calculate_fees','suchx_authorize_woocommerce_custom_surcharge' );
function suchx_authorize_woocommerce_custom_surcharge() {
	global $woocommerce;

	$payment_gateways = WC()->payment_gateways->payment_gateways();
	$enable_surchx = $payment_gateways['surchx-authorize']->get_option('enabled');
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
                if (is_ajax() && isset($_POST['postcode']) && $_POST['postcode']!="" && isset($post_data['authorize_surchx_ccNo']) && $post_data['authorize_surchx_ccNo']!="") {
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

if ( ! function_exists( 'surchx_authorize_remove_transaction_fee_ajax' ) ):

	function surchx_authorize_remove_transaction_fee_ajax(){

    	WC()->session->__unset( '_card_processing_fee');

    	WC()->session->__unset( '_surchx_card_num');

    	WC()->session->__unset( '_surchx_id');

    	echo json_encode(array("code"=>200)); die();
	}
	add_action( 'wp_ajax_surchx_authorize_remove_transaction_fee_ajax', 'surchx_authorize_remove_transaction_fee_ajax' );
	add_action( 'wp_ajax_nopriv_surchx_authorize_remove_transaction_fee_ajax', 'surchx_authorize_remove_transaction_fee_ajax' );

endif;

// ADDING NEW COLUMNS FOR TRANSACTION FEE
add_filter( 'manage_edit-shop_order_columns', 'surchx_authorize_shop_order_column', 20 );
function surchx_authorize_shop_order_column($columns)
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
add_action( 'manage_shop_order_posts_custom_column' , 'surchx_authorize_list_column_content', 20, 2 );
function surchx_authorize_list_column_content( $column, $post_id )
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


function addtransfeetoDB($id){
$transaction_fee = WC()->session->get( '_card_processing_fee' );
$order = wc_get_order( $id );
$update_flag = 0;
foreach( $order->get_items('fee') as $item_id => $item_fee ){
    $fee_name = $item_fee->get_name();
    if($fee_name == 'Transaction Fee'){
    	$update_flag = 1;
    	wc_update_order_item_meta( $item_id, '_fee_amount', $transaction_fee);
    	wc_update_order_item_meta( $item_id, '_line_total', $transaction_fee);
    }
}
if($update_flag == 0){
	$item_fee = new WC_Order_Item_Fee();
	$item_fee->set_name( "Transaction Fee" ); // Generic fee name
	$item_fee->set_amount( $transaction_fee ); // Fee amount
	$item_fee->set_tax_class( '' ); // default for ''
	$item_fee->set_tax_status( 'none' ); // or 'none'
	$item_fee->set_total( $transaction_fee ); // Fee amount
	$order->add_item( $item_fee );
	$order->calculate_totals();
	$order->save();
}
}

if ( ! function_exists( 'surchx_remove_admin_transaction_fee_ajax' ) ):

	function surchx_remove_admin_transaction_fee_ajax(){

		$id = $_POST['order_id'];
		if($id){
    	WC()->session->__unset( '_card_processing_fee');
    	WC()->session->__unset( '_surchx_card_num');
    	WC()->session->__unset( '_surchx_id');
    	$transaction_fee = 0;
    	$order = wc_get_order( $id );
    	$update_flag = 0;
    	foreach( $order->get_items('fee') as $item_id => $item_fee ){
    		$fee_name = $item_fee->get_name();
    		if($fee_name == 'Transaction Fee'){
    			$update_flag = 1;
    			wc_update_order_item_meta( $item_id, '_fee_amount', $transaction_fee);
    			wc_update_order_item_meta( $item_id, '_line_total', $transaction_fee);
    		}
    	}
    	$order = wc_get_order( $id );
		$newTotal = $order->calculate_totals();
		$order->set_total($newTotal);
		$order->save();

    	echo json_encode(array("code"=>200)); die();
    }
	}
	add_action( 'wp_ajax_surchx_remove_admin_transaction_fee_ajax', 'surchx_remove_admin_transaction_fee_ajax' );
	add_action( 'wp_ajax_nopriv_surchx_remove_admin_transaction_fee_ajax', 'surchx_remove_admin_transaction_fee_ajax' );
endif;
