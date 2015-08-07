<?php
/*
Plugin Name: Supertext API for Polylang
Plugin URI: http://www.supertext.ch
Description: This plugins allows translation of posts and pages with the Supertext API
Author: comotive GmbH, Supertext AG
Version: 1.2
Author URI: http://www.supertext.ch
License: GPLv2 or later
*/

define('SUPERTEXT_PLUGIN_REVISION', 4);
define('SUPERTEXT_POLYLANG_BASE_PATH', __DIR__);
define('SUPERTEXT_POLYLANG_VIEW_PATH', __DIR__ . '/views/');
define('SUPERTEXT_POLYLANG_RESOURCE_URL', plugin_dir_url(__FILE__) . 'resources');

// Autoload loading namespaced classes
require_once SUPERTEXT_POLYLANG_BASE_PATH . '/autoload.php';

// Load Comotive helper- and Supertext implementation namepsace
foreach (array('Comotive', 'Supertext') as $namespace) {
  $loader = new SplClassLoader_fc082b29bf388c112fcdefde6b4fe1e7($namespace, __DIR__ . '/src');
  $loader->register();
}

// Initialize the plugin
add_action('plugins_loaded', function() {
  $plugin = new \Supertext\Polylang\Core(__DIR__);
  $plugin->load();
});

// Register the install- and deinstallation hooks
register_activation_hook(__FILE__, array('\Supertext\Polylang\Core', 'onActivation'));
register_deactivation_hook(__FILE__, array('\Supertext\Polylang\Core','onDeactivation'));