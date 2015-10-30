=== Polylang-Supertext ===
Contributors: msebel, comotive, supertext
Tags: internationalization, polylang, translation, service, supertext
Requires at least: 3.8
Tested up to: 4.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Translate pages, posts with the supertext translation service. Works with polylang.


== Description ==

This plugin can only be used with polylang. It allows the user to send a certain post or page to
the supertext translation service. After translation, the page or post is automatically translated
in your WordPress installation. It works for every post type that is translatable by polylang.

You can let supertext translate content, title, excerpt and image captions at this moment.
Just choose to "get translation offer" instead of creating the new page with polylang. See the offers,
choose one and you'll be notified once your translated page or post has been inserted to your website.


== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `polylang-supertext` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Supertext Settings to provide API keys and language mappings
4. Go to a page/post you would like to be translated by supertext
5. Place an offer, accept it and wait until supertext has automatically translated your page/post

== Frequently Asked Questions ==

= What do I need to use this plugin? =

A valid supertext customer account per WordPress user and the corresponding API key.
You can signup on [Supertext](https://www.supertext.ch/en/signup) if you don't already have a Supertext-Account.
Get the API key on the Settings page: [Account Settings](https://www.supertext.ch/customer/accountsettings)

== Changelog ==

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
