=== Emailchef for WooCommerce === 

Contributors: dueclic 
Tags: emailchef,newsletter,woocommerce,ecommerce,email 
Requires at least:6.0
Tested up to: 6.7
Stable tag: 5.5.0
WC requires at least: 8.3.1 
WC tested up to: 9.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Using this WooCommerce plugin, Emailchef can communicate with your online store and it creates easy, simply and
automatic targeted campaigns.

== Description ==

When you own an e-commerce website, email marketing becomes one of the most powerful and effective tools to boost sales
and to earn your customers loyalty.

Using this WooCommerce plugin, Emailchef can communicate with your online store and it creates easy, simply and
automatic targeted campaigns.

The Emailchef for WooCommerce plugin enables you to:

* **Transfer information about your customers and their orders** to your Emailchef account

* You can **create segmentations of customers** according to their orders, abandoned carts, etc.

* **Manage and sync newsletter subscriptions** between WooCommerce and Emailchef.

* **Save money with automatic newsletters** to keep your customers involved

* Create **targeted marketing actions** to save **abandoned shopping carts**, rewards for **recurring orders** and **
  more** follow up practices

== Frequently Asked Questions == 

= Can I customize custom fields data? = 

Yes, you can do it. You must to use the <code>
emailchef_customer_data</code> filter hook. Below you can see a useful code snippet as example of use (you must to put
this in a custom plugin or the <code>functions.php</code> file of your active theme):

<code>

// $emailchef_customer_id could be 0 in case of contact creation

function emailchef_customer_data($customer, $list_id, $emailchef_customer_id = 0){ $custom_field_placeholder = 'source';
if (isset($customer[$custom_field_placeholder])){ $customer[$custom_field_placeholder] = 'newvalue'; } return $customer;
}

add_filter('emailchef_customer_data', 'emailchef_customer_data', 10, 4);
</code>

= Can I change Abandoned Cart image size? = 

Yes, you can do it. You must to use the <code>
emailchef_abandoned_cart_image_size</code> filter hook. Below you can see a useful code snippet as example of use (you
must to put this in a custom plugin or the <code>functions.php</code> file of your active theme):


<code>

// By default the size is: 'woocommerce_thumbnail'

function emailchef_abandoned_cart_image_size($size){ return 'newsize'; }

add_filter('emailchef_abandoned_cart_image_size', 'emailchef_abandoned_cart_image_size', 10, 1);
</code>

== Changelog ==

= 5.5.0 =
* Small fixes

= 5.4 =
* Responsive fixes in Admin Area

= 5.3 =
* API keys introduction
* UI changes
* bugfixes

= 5.1 =
* HPos Compatibility

= 5.0 =
* REST API - bugfixes

= 4.9 = 
* small fixes

= 4.8 =
* rebrand product name
* Extended compatibility to WP 6.5.1 and WC 8.3.1

= 4.7 =
* fix register_rest_route, permission_callback missing notice

= 4.6 =
* Tested up WooCommerce 8.0.2
* Tested up WordPress 6.3

= 4.5 =
* Tested up WooCommerce 7.5.1
* Tested up WordPress 6.2

= 4.4 =
* Tested up WooCommerce 7.4.0
* PHP 8 fixes

= 4.3 =
* Tested up WooCommerce 7.1.0
* Policy Mode API changes

= 4.2 =

* Tested up WordPress 6.1

= 4.1 = 

* Tested up WooCommerce 7.0.0
* Extended compatibility to PHP 8.1
* Reduce Login API usage

= 4.0 =

* Tested up WooCommerce 6.9.0

= 3.6 =

* Tested up WooCommerce 6.7.0
* change logs

= 3.4 =

* Tested up WooCommerce 6
* Tested up WordPress 5.9

= 3.0 =

* Added two filters:
    - emailchef_customer_data
    - emailchef_abandoned_cart_image_size

= 2.9 =

* Tested Up WooCommerce 5.5.1
* Tested Up WordPress 5.8

= 2.8 =

* Bugfixes

= 2.7 =

* Bugfixes

= 2.6 =

* Tested Up WooCommerce 5.3
* Added Store Registration Date

= 2.5 =

* Tested Up WooCommerce 5.1.0 and WordPress 5.7.0

= 2.4 =

* Tested Up WooCommerce 4.9

= 2.3 =

* Bugfix: last order status couldn't be proper sent

= 2.2 =

* Several bugfixes

= 2.1 =

* Several bugfixes

= 2.0 =

* Several bugfixes

= 1.7 =

* Extend compatibility to WordPress 5.4.1 and WooCommerce 4.1.1

= 1.6 =

* Added CLI import tool
* Extend compatibility to WordPress 5.3 and WooCommerce 3.7

= 1.5 =

* New deferred import with Emailchef

= 1.1 =

* New connection with Emailchef
* Extended compatibility to WP 4.9.2 and WooCommerce 3.2.6
* Bugfixes

= 1.0 =

* Initial release.

== Screenshots ==

1. Emailchef account login using API Keys
2. List settings
3. Subscription settings
4. Abandoned cart settings
5. Logs management

== Upgrade Notice ==

= 1.5 =

* New deferred import with Emailchef

= 1.1 =

* New connection with Emailchef
* Extended compatibility to WP 4.9.2 and WooCommerce 3.2.6
* Bugfixes

= 1.0 =

* Initial release.
