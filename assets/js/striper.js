jQuery(function($) {
		 
	Stripe.setPublishableKey(striperCfg.publishableKey);
		
	errorBox = $('<ol id="striper-errorbox"></ol>').appendTo('body');
	//errorBox = $('#striper-errorbox');
    var 
		checkoutForm = $('form.checkout'),
		stripeTokenHiddenInput = $('<input type="hidden" name="stripeToken">')//,
		//errorBox = $('#striper-errorbox')
	;

    // Bind to the checkout_place_order event to add the token
	$('form.checkout').on('checkout_place_order_' + striperCfg.gatewayId, function(e) {
      
		//checkoutForm.block({message: null,overlayCSS: {background: "#fff url(" + woocommerce_params.ajax_loader_url + ") no-repeat center",backgroundSize: "16px 16px",opacity: .6}});

		// Pass if we have a token
		if ( checkoutForm.find('[name=stripeToken]').length) {
			//checkoutForm.unblock();
			return true;
		}
			
		// CC fields validation with Js
		//errorBox.empty();
		var errors = [];
		errorBox.append('<li>foo</li>');
		console.log(errorBox);
		
		var cardNumber = $('#' + striperCfg.gatewayId + '-card-number').val();
		if (!$.payment.validateCardNumber(cardNumber))
			errors.push('Invalid credit card number');
		
		var 
			expiry = $('#' + striperCfg.gatewayId + '-card-expiry').val(),
			expiryParts = $.payment.cardExpiryVal(expiry)
		;
		if (!$.payment.validateCardExpiry(expiryParts.month, expiryParts.year))
			errors.push('Ivalidy credit card expiry date');
			
		var cvc = $('#' + striperCfg.gatewayId + '-card-cvc').val();
		if (cvc.length > 0)
			if (!$.payment.validateCardCVC(cvc))
				errors.push('Invalid credit card CVC');
		
		if (errors.length > 0) {
			//checkoutForm.unblock();
			errorBox.append('<li>errors</li>');
			console.log('js errors');
			//return false;
		}
		
		var tokenCreationArgs = {
			number: cardNumber, 
			exp_month: expiry.month,
			exp_year: expiry.year
		};
		if (cvc.length > 0)
			tokenCreationArgs.cvc = cvc;
		
		Stripe.card.createToken(tokenCreationArgs, stripeResponseHandler);
		
		return false;
    });
	
	function stripeResponseHandler(status, response) {
		console.log(status, response);

		if (status == 200) {
			checkoutForm
				.append(stripeTokenHiddenInput.val(response.id))
				.submit()
			;
		} else {
			errorBox.append('<li>' + response.error.message + '</li>');
			//checkoutForm.unblock();
		}
	}
	
	/* $('body').on('click', '#place_order,form#order_review input:submit', function(){
		// Make sure there's not an old token on the form
		createStripeToken();
		return false;
    }); */

	$('body').on('click', '#place_order, form.checkout input:submit', function() {
		// Make sure there's not an old token on the form
		stripeTokenHiddenInput.detach();
    })
	
 });
 