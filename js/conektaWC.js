
//TODO: eliminar los campos de tarjeta para que no se envien en el post


jQuery(document).ready(function () {
	'use strict';
    
	var form, first_name;
    var last_name;
    var expiry;
    
    var conektaSuccessResponseHandler = function (response) {
		
	    var token_id = response.id;
        
        form.find("#place_order").prop("disabled", false);
	    jQuery('input[name="conektaTokenId"]').val(token_id);
        jQuery('form.checkout').removeClass('processing'); 
        jQuery('form[name="checkout"]').submit();
        
	};
    var conektaErrorResponseHandler = function (response) {
        form.find("#place_order").prop("disabled", false);
	    form.find('.card-errors').text(response.message);

	};
    
	jQuery('form.checkout').submit(function (event) {
        
		form = jQuery(this);

        if ( (jQuery('input[name="conektaCustId"]').val() == "true" || jQuery('input#conekta-card-new-card').is(':checked') ) &&  jQuery('input[name="conektaTokenId"]').val().substr(0, 4) != "tok_" && jQuery('input#payment_method_conekta_card').is(':checked')) {
            jQuery('form.checkout').addClass('processing'); 
            Conekta.setPublishableKey(jQuery('input[name="pbkey"]').val());
            
            form.find("#place_order").prop("disabled", true);
            first_name = jQuery('#billing_first_name').val();
            last_name = ' ' + jQuery('#billing_last_name').val();
            expiry = jQuery('#conekta_card-card-expiry').val().replace(/ /g, '').split("/");

            jQuery('input[name="card-name"]').val(first_name + last_name);
            jQuery('input[name="exp-month"]').val(Number(expiry[0]));
            jQuery('input[name="exp-year"]').val(Number(expiry[1]));

            form.prepend('<span class="card-errors"></span>');

            Conekta.token.create(form, conektaSuccessResponseHandler, conektaErrorResponseHandler);
            return false;

        } else {
            jQuery('form.checkout').removeClass('processing'); 
            return false;
        }
        
	});


});


