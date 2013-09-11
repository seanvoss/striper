<?php
/*
Plugin Name: Striper (Gateway using Stripe.js)
Plugin URI: http://seanvoss.com/striper
Description: Provides a Credit Card Payment Gateway through Stripe for woo-commerece.
Version: 0.1
Author: Sean Voss
Author URI: http://seanvoss.com/striper

*/

/*
 * Title   : Stripe Payment extension for WooCommerce
 * Author  : Sean Voss
 * Url     : http://seanvoss.com/woostriper
 * License : http://seanvoss.com/woostriper/legal
 */

function striper_init_your_gateway() 
{
    if (class_exists('WC_Payment_Gateway'))
    {
        include_once('stripe_gateway.php');
    }
}

add_action('plugins_loaded', 'striper_init_your_gateway', 0);
