=== Supertext Translation ===
Contributors: msebel, comotive, supertext
Tags: internationalization, polylang, translation, service, supertext
Requires at least: 3.8
Tested up to: 4.4
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

Please note:
Translatable custom fields must be defined in the settings using the list of available custom fields.
Supported plugins at the moment are:

- Advanced Custom Fields
- Yoast SEO

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

== Changelog ==

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
