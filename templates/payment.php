<?php
/*
 * Title   : Stripe Payment extension for WooCommerce
 * Author  : Sean Voss
 * Url     : http://seanvoss.com/woostriper
 * License : http://seanvoss.com/woostriper/legal
 */
?>

<div id="stripe_pub_key" class="hidden" style="display:none" data-publishablekey="<?=$this->publishable_key ?>"> </div>
<div class="clear"></div>
<span class='payment-errors required'></span>
<p class="form-row">
  <label>Card Number <span class="required">*</span></label>
  <input class="input-text" type="text" size="19" maxlength="19" data-stripe="number" style="border-radius:6px;width:400px;"/>
</p>
<div class="clear"></div>
<p class="form-row form-row-first">
  <label>Expiration Month <span class="required">*</span></label>
  <select data-stripe="exp-month">
      <option value=1>01</option>
      <option value=2>02</option>
      <option value=3>03</option>
      <option value=4>04</option>
      <option value=5>05</option>
      <option value=6>06</option>
      <option value=7>07</option>
      <option value=8>08</option>
      <option value=9>09</option>
      <option value=10>10</option>
      <option value=11>11</option>
      <option value=12>12</option>
  </select>
</p>
<p class="form-row form-row-last">
  <label>Expiration Year  <span class="required">*</span></label>
  <select data-stripe="exp-year">
<?php
    $today = (int)date('Y', time());
    for($i = 0; $i < 10; $i++)
    {
?>
        <option value="<?php echo $today; ?>"><?php echo $today; ?></option>
<?php
        $today++;
    }
?>
    </select>
</p>
<div class="clear"></div>
<p class="form-row form-row-first">
    <label>Card Verification Number <span class="required">*</span></label>
    <input class="input-text" type="text" maxlength="4" data-stripe="cvc" value=""  style="border-radius:6px"/>
</p>
<div class="clear"></div>

<script>

  var initStriper = function(){
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
            card_name += $(el).val() + ' ';
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
      if(jQuery('.payment_methods input:checked').val() !== 'Striper')
      {
        return true;
      }

      // Make sure there's not an old token on the form
      Stripe.setPublishableKey($('#stripe_pub_key').data('publishablekey'));
      Stripe.createToken($form, stripeResponseHandler);
      return false;
    });


    $('body').on('click', '#place_order,form.checkout input:submit', function(){
      if(jQuery('.payment_methods input:checked').val() !== 'Striper')
      {
        return true;
      }
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

if(typeof jQuery=='undefined')
{
    var headTag = document.getElementsByTagName("head")[0];
    var jqTag = document.createElement('script');
    jqTag.type = 'text/javascript';
    jqTag.src = 'https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js';
    jqTag.onload = initStriper;
    headTag.appendChild(jqTag);
} else {
   initStriper()
}
</script>
