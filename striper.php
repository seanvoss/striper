<?php
/*
Plugin Name: Striper (Gateway using Stripe.js)
Plugin URI: http://blog.seanvoss.com/product/striper
Description: Provides a Credit Card Payment Gateway through Stripe for woo-commerece.
Version: 0.30
Author: Sean Voss
Author URI: https://blog.seanvoss.com/
License : https://blog.seanvoss.com/product/striper
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function striper_init_your_gateway() 
{
    if (class_exists('WC_Payment_Gateway') && version_compare(WC_VERSION, '2.1', '>='))
        include_once('stripe_gateway.php');
}

add_action('plugins_loaded', 'striper_init_your_gateway', 0);
