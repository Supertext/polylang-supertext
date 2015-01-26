<?php
/*
Plugin Name: Supertext API for Polylang
Plugin URI: http://www.comotive.ch
Description: This plugins allows translation of posts and pages with the Supertext API
Author: comotive GmbH
Version: 1.0
Author URI: http://www.comotive.ch
*/

// Autoload loading namespaced classes
require_once __DIR__ . '/autoload.php';

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