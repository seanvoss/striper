<?php
/*
Plugin Name: Striper (Gateway using Stripe.js)
Plugin URI: http://blog.seanvoss.com/product/striper
Description: Provides a Credit Card Payment Gateway through Stripe for woo-commerece.
Version: 0.28
Author: Sean Voss
Author URI: https://blog.seanvoss.com/

*/

/*
 * Title   : Stripe Payment extension for WooCommerce
 * Author  : Sean Voss
 * Url     : https://blog.seanvoss.com/product/striper
 * License : https://blog.seanvoss.com/product/striper
 */

function striper_init_your_gateway() 
{
    if (class_exists('WC_Payment_Gateway'))
    {
        include_once('stripe_gateway.php');
    }
}

add_action('plugins_loaded', 'striper_init_your_gateway', 0);
