==== Striper - Stripe Integration for WooCommerce ====
Contributors: seanvoss
Tags: woocommerce, stripe, payments, free stripe integration
Requires at least: 3.0
Tested up to: 3.6
Stable tag: 0.29
Donate link: https://blog.seanvoss.com/shop/striper/
License: GPLv2 or later

Striper for WooCommerce allows your users to pay via Stripe. 

== Description ==

The Stripe plugin extends WooCommerce allowing you to take payments directly on your store via Stripe’s API. Stripe is available in the United States, Canada, the UK, Australia, Belgium, France, Germany, Ireland, The Netherlands and more

== Installation ==

1. Install the WooCommerce Stripe Plugin
2. Activate the plugin
3. Go to the WooCommerce Settings Page 
4. Access Payment Gateways Tab
5. Select "Striper"
6. Check the Enable/Disable Checkbox.
7. Enter the settings that you would like you to use

== Future Plans ==
Add Subscriptions to eliminate the need for WooSubscriptions.

== Frequently Asked Questions ==
None at this time

== Changelog ==

= 0.29 =
* Fixing bug where Striper is not the payment type selected.
= 0.28 =
* Changing the success url, & removing pass by reference
= 0.27 =
* Add new #id for checkout and ability to capture user at Stripe
= 0.26 =
* Add option to setInterval JS
= 0.25 =
* Fix if Stripe is invoked elsewhere
= 0.24 =
* Fix for versions < PHP 5.3
= 0.23 =
* Added non-default image url
= 0.22 =
* Missed country and state from prior commit
= 0.21 =
* Pass the name and address of the purchasing user to stripe
= 0.20 =
* Pull out the seperate JS
= 0.19 =
* Revent to 0.16, users continue having issues
= 0.18 =
* Fixes intermittant infinite loop, and fixes the image submit button
= 0.17 =
* Drastic change in how the JS code executes
= 0.16 =
* Doubling up on the JS code, hopefully to satisfy all users setups.
= 0.15 =
* Fixes plugin for new WooCommerce, upgrade to WooCommerce 2.0.20
= 0.14 =
* Fixes Auth vs Charge & Moves Javascript to external file
= 0.12 =
* Fixes disabling other payment types when enabled
= 0.11 =
* Moves Capture to default functionality, change price after authorize, it will automatically refund the difference
= 0.10 =
* Adds Capture to Admin Box
= 0.9 =
* Minor Fixes
= 0.8 =
* Fixes loading jQuery if not present
= 0.7 =
* Fixes email payment
= 0.6 =
* Fixes Making more selective about which .checkout to use
= 0.5 =
* Fixes Live Plugin Key
= 0.4 =
* Update Plugin Namespace.
* Note: If upgrading will have to re-input stripe keys
= 0.3 =
* Update Links
= 0.2 =
* Readme Update
= 0.1 =
* Initial Release

== Screenshots ==
