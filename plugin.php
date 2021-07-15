<?php
/*
Plugin Name: Supertext Translation
Plugin URI: http://www.supertext.ch
Description: This plugin allows you to order human translations for your pages and posts using Supertexts professional translation services.
Text Domain: supertext
Domain Path: /resources/languages
Author: Supertext AG
Version: 4.02
Author URI: http://www.supertext.ch
License: GPLv2 or later
*/

define('SUPERTEXT_PLUGIN_VERSION', '4.02');
define('SUPERTEXT_PLUGIN_REVISION', 42);
define('SUPERTEXT_BASE_PATH', __DIR__);
define('SUPERTEXT_VIEW_PATH', __DIR__ . '/views/');
define('SUPERTEXT_RESOURCE_URL', plugin_dir_url(__FILE__) . 'resources');

// Autoload loading namespaced classes
require_once SUPERTEXT_BASE_PATH . '/autoload.php';

// Load Comotive helper- and Supertext implementation namepsace
foreach (array('Comotive', 'Supertext') as $namespace) {
  $loader = new SplClassLoader_fc082b29bf388c112fcdefde6b4fe1e7($namespace, __DIR__ . '/src');
  $loader->register();
}

// Initialize the plugin
add_action('plugins_loaded', function () {
  $plugin = new \Supertext\Core();
  $plugin->load();
});

// Register the install- and deinstallation hooks
register_activation_hook(__FILE__, array('\Supertext\Core', 'onActivation'));
register_deactivation_hook(__FILE__, array('\Supertext\Core', 'onDeactivation'));
register_uninstall_hook(__FILE__, array('\Supertext\Core', 'onUninstall'));

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links');

function add_action_links($links)
{
  $settingsLinks = array(
    '<a href="' . admin_url('options-general.php?page=supertext-settings') . '">Settings</a>',
  );
  return array_merge($links, $settingsLinks);
}