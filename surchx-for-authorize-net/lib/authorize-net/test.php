<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require 'vendor/autoload.php';

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
define("AUTHORIZENET_LOG_FILE", "phplog");
define("MERCHANT_LOGIN_ID", "9pPtfD9Y53");
define("MERCHANT_TRANSACTION_KEY", "2EU345gng65XPr63");
define("SAMPLE_AMOUNT", "101");
define("RESPONSE_OK", "Payment Done");


function chargeCreditCard($amount) {
	// Common setup for API credentials
	$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
	$merchantAuthentication->setName(MERCHANT_LOGIN_ID);
	$merchantAuthentication->setTransactionKey(MERCHANT_TRANSACTION_KEY);
	$refId = 'ref' . time();

	 //While testing, create the payment data for a credit card
	$creditCard = new AnetAPI\CreditCardType();
	$creditCard->setCardNumber("4111111111111111");
	$creditCard->setExpirationDate("1227");
	$creditCard->setCardCode("123");
	$paymentOne = new AnetAPI\PaymentType();
	$paymentOne->setCreditCard($creditCard);
	$order = new AnetAPI\OrderType();
	$order->setDescription("New Order Description");
	 //create a transaction
	$transactionRequestType = new AnetAPI\TransactionRequestType();
	$transactionRequestType->setTransactionType("authCaptureTransaction");
	$transactionRequestType->setAmount($amount);
	$transactionRequestType->setOrder($order);
	$transactionRequestType->setPayment($paymentOne);

	 //Prepare customer information object from your form $_POST data
	$cust = new AnetAPI\CustomerAddressType();
	$cust->setFirstName('Jagdeep');
	$cust->setLastName('Gill');
	$cust->setAddress('Mohali');
	$cust->setCity('Mohali');
	$cust->setState('Mohali');
	$cust->setCountry("India");
	$cust->setZip("150010");
	$cust->setPhoneNumber("123132134");
	$cust->setEmail("Jagdeep.singh@mobilyte.com");

	$transactionRequestType->setBillTo($cust);
	$request = new AnetAPI\CreateTransactionRequest();

	$request->setMerchantAuthentication($merchantAuthentication);
	$request->setRefId($refId);
	$request->setTransactionRequest($transactionRequestType);

	$controller = new AnetController\CreateTransactionController($request);
	 
	 //Get response from Authorize.net
 	$response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
	 if ($response != null) {
		 if ($response->getMessages()->getResultCode() == RESPONSE_OK) {
		 	$tresponse = $response->getTransactionResponse();
			 if ($tresponse != null && $tresponse->getMessages() != null) {
				 echo " Transaction Response code : " . $tresponse->getResponseCode() . "\n";
				 echo "Charge Credit Card AUTH CODE : " . $tresponse->getAuthCode() . "\n";
				 echo "Charge Credit Card TRANS ID : " . $tresponse->getTransId() . "\n";
				 echo " Code : " . $tresponse->getMessages()[0]->getCode() . "\n";
				 echo " Description : " . $tresponse->getMessages()[0]->getDescription() . "\n";
			 }else{
				 echo "Transaction Failed \n";
				 if ($tresponse->getErrors() != null) {
				 	echo " Error code : " . $tresponse->getErrors()[0]->getErrorCode() . "\n";
				 	echo " Error message : " . $tresponse->getErrors()[0]->getErrorText() . "\n";
				 }
			 }
		} else {
			echo "Transaction Failed \n";
			$tresponse = $response->getTransactionResponse();
			if ($tresponse != null && $tresponse->getErrors() != null) {
				echo " Error code : " . $tresponse->getErrors()[0]->getErrorCode() . "\n";
				echo " Error message : " . $tresponse->getErrors()[0]->getErrorText() . "\n";
		 	} else {
				echo " Error code : " . $response->getMessages()->getMessage()[0]->getCode() . "\n";
				echo " Error message : " . $response->getMessages()->getMessage()[0]->getText() . "\n";
		 	}
		}
	} else {
	 	echo "No response returned \n";
	}
	 	return $response;
}


$amount = SAMPLE_AMOUNT;

chargeCreditCard($amount);










die("adsdas");
