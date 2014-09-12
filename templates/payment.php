<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div id="stripe_pub_key" class="hidden" style="display:none" data-publishablekey="<?php echo esc_attr($publishable_key;) ?>"> </div>
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
