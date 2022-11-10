=== eMailChef for WooCommerce === 

Contributors: dueclic 
Tags: emailchef,eMailChef,email marketing,mail,email,newsletter,woocommerce,e-commerce,ecommerce,email automation, email campaigns 
Requires at least:5.0.0 
Tested up to: 6.1
Stable tag: 4.3
WC requires at least: 5.0.0 
WC tested up to: 7.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Using this WooCommerce plugin, eMailChef can communicate with your online store and it creates easy, simply and
automatic targeted campaigns.

== Description ==

When you own an e-commerce website, email marketing becomes one of the most powerful and effective tools to boost sales
and to earn your customers loyalty.

Using this WooCommerce plugin, eMailChef can communicate with your online store and it creates easy, simply and
automatic targeted campaigns.

The eMailChef for WooCommerce plugin enables you to:

* **Transfer information about your customers and their orders** to your eMailChef account

* You can **create segmentations of customers** according to their orders, abandoned carts, etc.

* **Manage and sync newsletter subscriptions** between WooCommerce and eMailChef.

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

* New deferred import with eMailChef

= 1.1 =

* New connection with eMailChef
* Extended compatibility to WP 4.9.2 and WooCommerce 3.2.6
* Bugfixes

= 1.0 =

* Initial release.

== Screenshots ==

1. Access eMailChef account

2. Configure eMailChef account

3. Create a new eMailChef list

== Upgrade Notice ==

= 1.5 =

* New deferred import with eMailChef

= 1.1 =

* New connection with eMailChef
* Extended compatibility to WP 4.9.2 and WooCommerce 3.2.6
* Bugfixes

= 1.0 =

* Initial release.
