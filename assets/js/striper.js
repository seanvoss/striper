jQuery(function($) {
		 
	Stripe.setPublishableKey(striperCfg.publishableKey);
		
    var 
		checkoutForm = $('form.checkout'),
		stripeTokenHiddenInput = $('<input type="hidden" name="stripeToken">')//,
		errorBox = (function() {
			var 
				box = $('<ol id="striper-errorbox"></ol>')
			;
			return {
				hide: function() {
					box.empty().detach();
				},
				push: function(errMsg) {
					box.append('<li>' + errMsg + '</li>');
					return this;
				},
				show: function() {
					box.prependTo('#striper-cc-form');
				},
				errors: function() {
					return box.children().length > 0;
				}
			};
		})()
	;

	$('form.checkout').on('checkout_place_order_' + striperCfg.gatewayId, getStripeToken);
	$('body').on('click', '#place_order, form.checkout input:submit', function() { /* Make sure there's not an old token on the form*/ stripeTokenHiddenInput.detach(); });
	/* $('body').on('click', '#place_order,form#order_review input:submit', function(){ // Make sure there's not an old token on the form createStripeToken(); return false; }); */
	
	function getStripeToken() {
		errorBox.hide();
		
		blockUI();

		// Pass if we have a token
		if ( checkoutForm.find('[name=stripeToken]').length) {
			unblockUI();
			return true;
		}
			
		var cardNumber = $('#' + striperCfg.gatewayId + '-card-number').val();
		if (!$.payment.validateCardNumber(cardNumber))
			errorBox.push('Invalid credit card number');
		
		var 
			expiryString = $('#' + striperCfg.gatewayId + '-card-expiry').val(),
			expiryDate = $.payment.cardExpiryVal(expiryString)
		;
		if (!$.payment.validateCardExpiry(expiryDate.month, expiryDate.year))
			errorBox.push('Ivalidy credit card expiry date');
			
		var cvc = $('#' + striperCfg.gatewayId + '-card-cvc').val();
		if (cvc.length > 0)
			if (!$.payment.validateCardCVC(cvc))
				errorBox.push('Invalid credit card CVC');
		
		if (errorBox.errors()) {
			errorBox.show();
			unblockUI();
			return false;
		}
		
		var tokenCreationArgs = {
			number: cardNumber, 
			exp_month: expiryDate.month,
			exp_year: expiryDate.year
		};
		if (cvc.length > 0)
			tokenCreationArgs.cvc = cvc;
		
		Stripe.card.createToken(tokenCreationArgs, stripeResponseHandler);
		
		return false;
    }
	
	function stripeResponseHandler(status, response) {
		console.log(status, response);

		if (status == 200) {
			checkoutForm
				.append(stripeTokenHiddenInput.val(response.id))
				.submit()
			;
		} else {
			errorBox.push(response.error.message).show();
			unblockUI();
		}
	}
	
	function blockUI() {
		checkoutForm.block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + woocommerce_params.ajax_loader_url + ') no-repeat center',
				backgroundSize: '16px 16px',
				opacity: .6
			}
		});
	}
	
	function unblockUI() {
		checkoutForm.unblock();
	}

});
 