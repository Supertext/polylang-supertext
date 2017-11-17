=== Supertext Translation ===
Contributors: supertext, msebel, comotive
Tags: internationalization, polylang, translation, service, supertext
Requires at least: 4.0
Tested up to: 4.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to order human translations for your pages and posts using Supertexts professional translation services.

== Description ==

This plugin allows the user to send a WordPress post or page to Supertext for translation.
Once completed, the translation will be automatically inserted into a new page or post in your WordPress installation.
This works for every post type that is translatable by Polylang.

Supertext can translate content, titles, excerpts, image captions, shortcode arguments and custom fields.
Simply order a translation instead of creating a new page with Polylang.
You will be presented with services, prices and deadlines. Select the right one for you and you'll be notified by email once your translated page or post has been uploaded to your website.

**Please note**

Translatable custom fields must be defined in the settings. Supported plugins at the moment are:

*   Advanced Custom Fields
*   Yoast SEO
*   All In One SEO pack

If the field is not available in one of the lists, you can define it by adding self defined custom fields.

Plugin has been tested with following page builders:

*   Visual Composer
*   Beaver Builder
*   BE page builder
*   Divi page builder
*   SiteOrigin page builder

== Installation ==

How to install the plugin

1. Upload polylang-supertext to the /wp-content/plugins/ directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Supertext to provide API keys and language mappings. If relevant, define translatable custom fields and shortcode arguments.
4. Go to a page/post you would like to be translated by Supertext
5. Select the service you want from the prices and deadlines presented. Supertext will translate your page/post and notify you once the translation has been uploaded to your website.

== Frequently Asked Questions ==

= What do I need to use this plugin? =

A valid Supertext customer account for every WordPress user and the corresponding API key.
You can create an account at [Supertext] (https://www.supertext.ch/en/signup) if you don't already have one.
Get the API key from the Settings page: [Account Settings] (https://www.supertext.ch/customer/accountsettings)

== Screenshots ==

1. Just order your translation using the "Order translation" link instead of creating the new content yourself
2. First order step for one post where you can select the content to be translated and the target language
3. You need to order more than one post? Just use the bulk action "Order translation"
4. First order step for multiple posts where you can select the content to be translated and the target language
5. Second order step where you can select the service and deadline
6. Order confirmation

== Changelog ==

= 3.9 =
* Added support for media translation

= 3.8 =
* Optimized tab initialisation (and with this change fixed issue when adding a new field to flexible content layout, ACF bug)
* Changed acf settings structure

= 3.7 =
* Added support for ACF flexible content
* Removed deprecated code
* Changed content selection layout (first order step)

= 3.6 =
* Fixed taxonomy text accessor bug
* Added service type setting

= 3.5 =
* Added support for WooCommerce product categories and tags

= 3.4 =
* Fixed post creation bug
* Various code improvements

= 3.3 =
* Added support for WooCommerce
* Fixed write back bug
* Various code improvements

= 3.2 =
* Added support for PHP 7.1
* Various code improvements

= 3.1 =
* Fixed ACF content accessor to support accordions
* Various code improvements

= 3.0 =
* Extended settings page to add default settings
* Allow the use of the plugin even though not all languages are supported
* Enhanced write back errors
* Various code improvements

= 2.9 =
* Fixed migration issue
* Added new/missing texts

= 2.8 =
* Added new feature to send translation changes back to Supertext
* Added support for multisite
* Added new default shortcode and custom fields settings
* Various code improvements and fixes

= 2.7 =
* Extended shortcode replacement
* Added support for Divi page builder

= 2.6 =
* Fixed GitHub issue 2 - replaced callback url used for writing translations back with ajax admin url
* Clean database options on uninstall
* Added support for Beaver Builder standard version

= 2.5 =
* Corrected language mapping with Supertext API
* Extended content step
* Various code improvements and fixes

= 2.4 =
* Added shortcode replacement support for PHP with object context bug
* Extended order creation request with additional plugin information
* Added german formal language file
* Added content accessors (for translation of categories and tags)

= 2.3 =
* Added new translations
* Added support for Polylang Pro
* Extended order item list
* Extended plugin activation with adding defaults settings depending on installed plugins

= 2.2 =
* Fixed plugin defined content accessor

= 2.1 =
* Added support for BE page builder
* Made API url configurable
* Added support for Salient Visual Composer
* Various code improvements and fixes

= 2.0 =
* Extended plugin to be able to order multiple posts/pages
* Changed order process
* Introduced Gulp for minification of JavaScript and SASS files
* Various code improvements

= 1.9 =
* Added support for Visual Composer
* Extended Beaver Builder support with shortcode replacement
* Extended post creation for translation post
* Various code improvements

= 1.8 =
* Added support for Beaver Builder
* Extended custom field settings page (with possibility to add own defined custom fields)
* Various code improvements

= 1.7 =
* Changed layout of translation status column in posts tables
* Fixed minor ACF bug

= 1.6 =
* Added ability to define shortcode attribute encodings
* Enhanced callback error handling
* Extended callback to return appropriate http response codes
* Enhanced token generation and validation
* Added new setting to automatically publish translations and allow Supertext to override published content

= 1.5 =
* Added new translation
* Enhanced error messages and handling
* Extended Shortcode replacement

= 1.4 =
* Settings and support for Custom Fields
* Added support for re-translation
* Post creation without redirect to page-new.php
* Added translation status column on manage posts tables
* Fixed encoding when replacing Shortcodes
* Various code improvements

= 1.3 =
* Settings and support for Shortcodes
* Text and UI changes
* Various code improvements

= 1.2 =
* Better user information when the translation is created
* Logging all communication with Supertext and display it in the article sidebar
* Always show a translation status and order number in "to be translated" articles
* Detailed and translated language information on offer page
* Removed order confirm question

= 1.1 =
* Switched to english, changed german translation
* Minor text and UI fixes
* Various small UI improvements

= 1.0 =
* Initial, german only version of the plugin
