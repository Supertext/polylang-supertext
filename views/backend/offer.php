<?php
// Load WP admin
require_once('../../../../../wp-admin/admin.php');

$core = Supertext\Polylang\Core::getInstance();

// Create a WP iframe from class
wp_iframe(array(
  $core->getOfferBox(),
  'displayOfferBox'
));