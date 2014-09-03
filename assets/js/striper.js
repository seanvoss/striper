var initStriper = function() {
    jQuery(function($) {
    var $form = $('form.checkout,form#order_review');

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
        $('<input id="stripeCardName" class="input-text" type="hidden" data-stripe="name" value="'+card_name+'"/>').appendTo($form);
    }

    var stripeResponseHandler = function(status, response) {

    if (response.error) {

      // Show the errors on the form
      $form.find('.payment-errors').text(response.error.message);
      // Unblock the form to re-enter data
      $form.unblock();

    } else {
      // Append the Token
      $form.append($('<input type="hidden" name="stripeToken" />').val(response.id));

      //Re-Submit
      $form.submit();

    }
  };

    $('body').on('click', '#place_order,form#order_review input:submit', function(){
      // Make sure there's not an old token on the form
      Stripe.setPublishableKey($('#stripe_pub_key').data('publishablekey'));
      Stripe.createToken($form, stripeResponseHandler);
      return false;
    });


    $('body').on('click', '#place_order,form.checkout input:submit', function(){
      // Make sure there's not an old token on the form
      $('form.checkout').find('[name=stripeToken]').remove()
    })


    // Bind to the checkout_place_order event to add the token
    $('form.checkout').bind('#place_order,checkout_place_order_Striper', function(e){

      if($('input[name=payment_method]:checked').val() != 'Striper'){
          return true;
      }

      $form.find('.payment-errors').html('');
      $form.block({message: null,overlayCSS: {background: "#fff url(" + woocommerce_params.ajax_loader_url + ") no-repeat center",backgroundSize: "16px 16px",opacity: .6}});

      // Pass if we have a token
      if( $form.find('[name=stripeToken]').length)
        return true;

      Stripe.setPublishableKey($('#stripe_pub_key').data('publishablekey'));
      Stripe.createToken($form, stripeResponseHandler)
      // Prevent the form from submitting with the default action
      return false;
    });
  });
};
