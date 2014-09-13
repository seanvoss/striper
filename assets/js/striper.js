jQuery(function($) {
		 
	Stripe.setPublishableKey(striperCfg.publishableKey);
		 
    var 
		checkoutForm = $('form.checkout'),
		errorBox = $('#striper-errorbox')
	;

    // Add additional information to be passed to Stripe
    var stripeMap = {

        billing_address_1:  'address_line1',
        billing_address_2:  'address_line2',
        billing_city:       'address_city',
        billing_country:    'address_country',
        billing_state:      'address_state',
        billing_postcode:   'address_zip',
    }
    var card_name = '';
    $('form.checkout').find('input[id*=billing_],select[id*=billing_]').each(function(idx,el){
        var mapped = stripeMap[el.id];
        if (mapped)
        {
            $(el).attr('data-stripe',mapped);

        }
        if(el.id == 'billing_first_name' || el.id == 'billing_last_name')
        {
            // If the billing first and last name fields were pre-populated (if the user was logged in)
            // the fields will have values
            var billingFirstName = $('#billing_first_name').val();
            var billingLastName = $('#billing_last_name').val();

            // Set the card name from the pre-populated fields
            card_name = $('#billing_first_name').val() + ' ' + $('#billing_last_name').val();


            // If the first name is changed
            $('#billing_first_name').blur(function () {
              
              // update the first name
              billingFirstName = $(this).val();
              
              // update the card name with the new first name
              card_name = billingFirstName + " " + billingLastName;
              
              // Update the hidden Stripe card name input with the new card name
              $('#stripeCardName').attr('value', card_name);
            });


            // If the last name is changed
            $('#billing_last_name').blur(function () {
              
              // update the last name
              billingLastName = $('#billing_last_name').val();
              
              // update the card name with the new last name
              card_name = billingFirstName + " " + billingLastName;

              // Update the hidden Stripe card name input with the new card name
              $('#stripeCardName').attr('value', card_name);
            });
        }
    });
    if (!$('#stripeCardName').length)
    {
        $('<input id="stripeCardName" class="input-text" type="hidden" data-stripe="name" value="'+card_name+'"/>').appendTo(checkoutForm);
    }

	/* $('body').on('click', '#place_order,form#order_review input:submit', function(){
		// Make sure there's not an old token on the form
		createStripeToken();
		return false;
    }); */

    $('body').on('click', '#place_order, form.checkout input:submit', function() {
      // Make sure there's not an old token on the form
      $('form.checkout').find('[name=stripeToken]').remove()
    })


    // Bind to the checkout_place_order event to add the token
	$('form.checkout').on('checkout_place_order_' + striperCfg.gatewayId, function(e) {
      
		//checkoutForm.block({message: null,overlayCSS: {background: "#fff url(" + woocommerce_params.ajax_loader_url + ") no-repeat center",backgroundSize: "16px 16px",opacity: .6}});

		// Pass if we have a token
		if ( checkoutForm.find('[name=stripeToken]').length) {
			//checkoutForm.unblock();
			return true;
		}
			
		// CC fields validation with Js
		errorBox.empty();
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
			return false;
		}
		
		var tokenCreationArgs = {
			number: cardNumber, 
			exp_month: expiry.month,
			exp_year: expiry.year
		};
		if (cvc.length > 0)
			tokenCreationArgs.cvc = cvc;
		
		Stripe.createToken(tokenCreationArgs, stripeResponseHandler);
		
		return false;
    });
	
	function stripeResponseHandler(status, response) {
		console.log(status, response);

		if (status == 200) {
			checkoutForm.append($('<input type="hidden" name="stripeToken" />').val(response.id));
			checkoutForm.submit();
		} else {
			errorBox.text(response.error.message);
			//checkoutForm.unblock();
		}
	}
	
 });
 