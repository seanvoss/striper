  jQuery(function($) {
    Stripe.setPublishableKey('<?= $this->publishable_key ?>');
    var $form = $('.checkout');

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
    $('body').on('click', '.checkout input:submit', function(){
      // Make sure there's not an old token on the form
      $('.checkout').find('[name=stripeToken]').remove()
    })

    // Bind to the checkout_place_order event to add the token
    $('.checkout').bind('checkout_place_order', function(e){
      $form.find('.payment-errors').html('');
      $form.block({message: null,overlayCSS: {background: "#fff url(" + woocommerce_params.ajax_loader_url + ") no-repeat center",backgroundSize: "16px 16px",opacity: .6}});

      // Pass if we have a token
      if( $form.find('[name=stripeToken]').length)
        return true;

      Stripe.createToken($form, stripeResponseHandler)
      // Prevent the form from submitting with the default action
      return false;
    });
  });
