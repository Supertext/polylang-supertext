# Supertext Translation

## Description

This plugin allows the user to send a WordPress post or page to Supertext for translation.
Once completed, the translation will be automatically inserted into a new page or post in your WordPress installation.
This works for every post type that is translatable by Polylang.

Supertext can translate content, titles, excerpts, image captions, shortcode arguments and custom fields.
Simply order a translation instead of creating a new page with Polylang.
You will be presented with services, prices and deadlines. Select the right one for you and you'll be notified by email once your translated page or post has been uploaded to your website.

**Please note**

Translatable custom fields must be defined in the settings. Supported plugins at the moment are:
- Advanced Custom Fields
- Yoast SEO
- All In One SEO pack

If the field is not available in one of the lists, you can define it by adding self defined custom fields.

Plugin has been tested with following page builders:
- Visual Composer
- Beaver Builder
- BE page builder
- Divi page builder
- SiteOrigin page builder

## Installation

How to install the plugin

1. Upload polylang-supertext to the /wp-content/plugins/ directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to Settings > Supertext to provide API keys and language mappings. If relevant, define translatable custom fields and shortcode arguments.

## Frequently Asked Questions

**What do I require to use this plugin?**

A valid Supertext customer account for every WordPress user and the corresponding API key.
You can create an account at [Supertext](https://www.supertext.ch/en/signup) if you don't already have one.
Get the API key from the Settings page: [Account Settings](https://www.supertext.ch/customer/accountsettings)

**How do I order a translation?**

1. Go to the post you would like to be translated by Supertext.
2. Click on _Order translation_ underneath the target language. ![Order translation](https://ps.w.org/polylang-supertext/assets/screenshot-1.png)
3. Select the content you want to be translated.
4. Select the service you want from the prices and deadlines presented. Supertext will translate your post and notify you once the translation has been uploaded to your website.

**How do I order multiple translation?**

1. Go to the post overview and select the posts you would like to be translated by Supertext.
2. Select the _Order translation_ bulk action and click on _Apply_. ![Order translation](https://ps.w.org/polylang-supertext/assets/screenshot-3.png)
3. Select the content you want to be translated and the target language.
4. Select the service you want from the prices and deadlines presented. Supertext will translate your post and notify you once the translation has been uploaded to your website.

**How to translate the site title and tagline?**

The site title and the tagline are part of the general settings of your WordPress. You can translate these texts using the _String translations_ feature of Polylang:
Languages -> Strings translations

Did you translate the site title but it is still displayed in only one language? In this case you can check if the themes header is using the correct WordPress functions to get the title/tagline (wp_title() or get_bloginfo()).

**How can I order a translation for image texts only?**

1. Go to the media library (Media->Library).
2. Select the images you want to order.
3. Select Order translation from the Bulk Actions drop down. ![Order translation](https://ps.w.org/polylang-supertext/assets/wp_translate_media.png)
4. Click on Apply next to the drop down.
5. And follow the order process steps.