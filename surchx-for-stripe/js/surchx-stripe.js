var successCallback = function(token) {
	
	var checkout_form = jQuery( 'form.woocommerce-checkout' );
	checkout_form.find('#surchxStripeToken').val(token);
	setTimeout(function() {
            // deactivate the tokenRequest function event
			checkout_form.off( 'checkout_place_order', tokenRequest );
			checkout_form.submit();
       	}, 300);
};
 
var errorCallback = function(data) {
    console.log(data);
    alert(data.error.message+" Please try again later.");
};

//callback to handle the response from stripe
function stripeResponseHandler(status, response) {
	jQuery(".suchx-blockUI").remove();
    if (response.error) {
       errorCallback(response);
    } else {
        var form$ = jQuery("form.woocommerce-checkout");
       	var token = response['id'];    
     	successCallback(token);
        return true;
    }
}
 
var tokenRequest = function() {

	var payment_method = jQuery('input[name="payment_method"]:checked').val();
	if(payment_method=='surchx-stripe'){

		jQuery(".woocommerce").append(loader);
	
		var CCnumber 			= jQuery("#stripe_surchx_ccNo").val().replace(/\s+/g, '');
		var suchx_expdateVars 	= jQuery("#stripe_surchx_expdate").val().split("/");
		var cvv 				= jQuery("#stripe_surchx_cvv").val().trim();
		var exp_month 			= suchx_expdateVars[0].trim();
		var exp_year 			= suchx_expdateVars[1].trim();

		Stripe.setPublishableKey(surchx_params.publishableKey);
		Stripe.createToken({
	            number: CCnumber,
	            cvc: cvv,
	            exp_month: exp_month,
	            exp_year: exp_year,
	        },  stripeResponseHandler);
		
		return false;
		
	}else{

		return true;
	}
	
};
 
jQuery(function($){
	var checkout_form = $( 'form.woocommerce-checkout' );
	checkout_form.on( 'checkout_place_order', tokenRequest );
});