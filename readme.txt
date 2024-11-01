=== USPS Simple Shipping for Woocommerce ===
Contributors: dangoodman
Tags: USPS, WooCommerce USPS Shipping, Live USPS rates
Requires PHP: 7.2
Requires at least: 4.6
Tested up to: 6.6
WC requires at least: 5.0
WC tested up to: 9.3


The USPS Simple plugin calculates rates for domestic shipping dynamically using the USPS API.


== Description ==

USPS Simple integrates the US postal domestic service as a new shipping method in WooCommerce. The plugin retrieves real-time rates through the USPS API.

By default, the plugin individually calculates the shipping price for each item in the cart, simulating separate shipments. The total price displayed to the customer is the sum of the individual item prices.

For regular-sized items, there is an option to group them based on their weight. If this feature is enabled, the dimensions of the grouped items are disregarded in the calculation.

To ensure compatibility, please set the WooCommerce currency to the US dollar and ensure that the base country is the USA.


= USPS Simple supports the following services: =

<ul>
<li>Priority Mail Express</li>
<li>Priority Mail Express, Hold for Pickup</li>
<li>Priority Mail Express, Sunday/Holiday</li>
<br>

<li>Priority Mail</li>
<li>Priority Mail, Hold For Pickup</li>
<li>Priority Mail Keys and IDs</li>
<li>Priority Mail Regional Rate Box A</li>
<li>Priority Mail Regional Rate Box A, Hold For Pickup</li>
<li>Priority Mail Regional Rate Box B</li>
<li>Priority Mail Regional Rate Box B, Hold For Pickup</li>
<br>

<li>First-Class Mail Postcards</li>
<li>First-Class Mail Stamped Postcards</li>
<li>First-Class Mail Large Postcards</li>
<li>First-Class Mail Letter</li>
<li>First-Class Mail Metered Letter</li>
<li>First-Class Mail Large Envelope</li>
<br>

<li>USPS Ground Advantage</li>
<br>

<li>USPS Retail Ground</li>
<br>

<li>Media Mail Parcel</li>
<br>

<li>Library Mail Parcel</li>
</ul>


== Installation ==

1. Upload the plugin folder to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Now you need configure the plugin: Enter your postcode and check option "Enable this shipping method". You can use the default User ID or enter yours.


== Screenshots ==

1. Configuration
2. Cart


== Changelog ==

= 1.10.8 =
* Tested with WooCommerce 9.3.

= 1.10.7 =
* Tested with WooCommerce 9.2.

= 1.10.6 =
* Trim the debug details on copy to make wp.org recognize it as a code fragment.

= 1.10.5 =
* Tested with WordPress 6.6, WooCommerce 9.1.

= 1.10.4 =
* Tested with WooCommerce 8.9, 9.0.

= 1.10.3 =
* Tested with WordPress 6.5, WooCommerce 8.8.

= 1.10.2 =
* Tested with WooCommerce 8.7.

= 1.10.1 =
* Tested with WooCommerce 8.4.

= 1.10.0 =
* Skip calculations if no zip code provided.
* Fix debug mode error on missing product dimensions.

= 1.9.4 =
* Tested with WordPress 6.4, WooCommerce 8.3.
* Raise the min required WooCommerce version to 5.0.

= 1.9.3 =
* Tested with WooCommerce 8.1.

= 1.9.2 =
* Tested with WordPress 6.3, WooCommerce 8.0.

= 1.9.1 =
* Fixed the default titles of the First-Class Mail Postcard services.
* Tested with WooCommerce 7.9.

= 1.9.0 =
* Replace First-Class Mail Parcel and Package with the new USPS Ground Advantage service (enabled by default).
* Tested with WooCommerce 7.8.

= 1.8.3 =
* Declare HPOS compatibility.
* Tested with WordPress 6.2, WooCommerce 7.7.

= 1.8.2 =
* Media Mail & Library Mail: always show the lowest of the rates to work around the USPS API inconsistency.

= 1.8.1 =
* Enable the plugin for Guam.

= 1.8.0 =
* Fix the Media Mail and Library Mail options not showing up since 22-Jan-2023 due to the USPS API updates.
* Tested with WooCommerce 7.3.

= 1.7.4 =
* Avoid 'Non-numeric value encountered' PHP warnings on missing product dimensions.
* Tested with WooCommerce 7.1.
* Raise the minimum required versions of WordPress and WooCommerce to 4.6 and 3.2 respectively.

= 1.7.3 =
* Tested with WooCommerce 7.0, WordPress 6.1.

= 1.7.2 =
* Tested with WooCommerce 6.9.

= 1.7.1 =
* Fix an error when item quantity is fractional.

= 1.7.0 =
* Workaround a USPS API error for items less than 0.25 inch.
* Check shipped items dimensions against the First-Class Mail size constraints (if 'Quote regular items by weight' is disabled).
* Avoid an additional call to the USPS API if Retail Ground is disabled.
* Fill the shipping origin postcode from Store Address by default.
* Enable the plugin upon install.
* Remove the '(USPS Simple)' delivery option label suffix.
* Require PHP 7.2+.
* Tested with WooCommerce 6.7.

= 1.6.2 =
* Tested with WooCommerce 6.5, WordPress 6.0.

= 1.6.1 =
* Tested with WooCommerce 6.3.

= 1.6.0 =
* Add the 'First-Class Package Service – Retail' service.
* Small backend cosmetic changes.

= 1.5.7 =
* Avoid 'Non-numeric value encountered' PHP warnings on missing product dimensions or weight.
* Tested with WordPress 5.9, WooCommerce 6.1.

= 1.5.6 =
* Replace the default USPS API user id to fix the authorization issue.
* Tested with WooCommerce 5.6.
* Fix the debug info drawer won't expand after cart update.

= 1.5.5 =
* Tested with WooCommerce 5.6.

= 1.5.4 =
* Tested with WordPress 5.8, WooCommerce 5.5.

= 1.5.3 =
* Tested with WooCommerce 5.3.

= 1.5.2 =
* Tested with WooCommerce 5.1, WordPress 5.7.

= 1.5.1 =
* Reword commercial rates description.
* Refactor USPS API response handling a bit.

= 1.5.0 =
* Check prerequisites on load, in a user-friendly way.

= 1.4.0 =
* Replace the deprecated WC_Product->length/width/height properties access with get_XXX() calls.
* Switch to the HTTPS USPS API endpoint.
* Disable cache and show debug data on the cart and checkout pages if the WooCommerce shipping debug mode is enabled.
* Tested with WooCommerce 4.8, WordPress 5.6.

= 1.3.1.1 =
* Tested with WooCommerce 4.7.

= 1.3.1 =
* Tested with WooCommerce 4.6.
* Minor changes for better USPS API response parsing.

= 1.3 =
* Tested with WordPress 5.5 and WooCommerce 4.5.
* Refresh the settings page look a bit.

= 1.2.6 =
* Compatible with woocommerce 2.6

= 1.2.5 =
* Fix First-Class Mail Parcel price calculator.
* Added First-Class Mail Large Envelope, Letter and Postcards.

= 1.2.4 =
* API Request updated

= 1.2.3 =
* Fix - Incorrect work of "Quote regular items by weight" with zero size items.

= 1.2.2 =
* Removed deprecated USPS services: 
  Priority Mail Regional Rate Box C;
  Priority Mail Regional Rate Box C, Hold For Pickup;
* Added First-Class Mail Metered Letter;
* Rebranding of Standard Post as Retail Ground.

= 1.2.1 =
* Fix - warning message in cart.

= 1.2.0 =
* Added services:
  Priority Mail, Hold For Pickup;
  Priority Mail Regional Rate Box A, Hold For Pickup;
  Priority Mail Regional Rate Box B, Hold For Pickup;
  Priority Mail Regional Rate Box C, Hold For Pickup.

= 1.1.1 =
* Added mail class id

= 1.1.0 =
* Added grouping by weight.

= 1.0.1 =
* Fix - Standard Post really works.

= 1.0 =
* Supported services: Priority Mail Express, Priority Mail, First-Class Mail, Standard Post, Media Mail, Library Mail