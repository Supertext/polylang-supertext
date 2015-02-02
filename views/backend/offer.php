<?php
// Load WP admin
require_once('../../../../../wp-admin/admin.php');

use Supertext\Polylang\Backend\OfferBox;

// Create a WP iframe from class
wp_iframe(array(
  new OfferBox,
  'displayOfferBox'
));