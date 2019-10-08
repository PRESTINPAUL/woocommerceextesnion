var successCallback = function() {
	
	var checkout_form = jQuery( 'form.woocommerce-checkout' );

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
     	successCallback();
        return true;
    }
}
 
var tokenRequest = function() {

	return true;
	
};
 
jQuery(function($){
	var checkout_form = $( 'form.woocommerce-checkout' );
	checkout_form.on( 'checkout_place_order', tokenRequest );
});