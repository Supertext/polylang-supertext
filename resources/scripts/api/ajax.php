<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/wp-admin/admin.php';

use Supertext\Polylang\Backend\AjaxRequest;

// Prepare parameters that are referenced in ajax handlers
$output = '';
$state = 'error';
$info = '';
$optional = array();

switch ($_GET['action']) {
  case 'getOffer':
    AjaxRequest::getOffer($output, $state, $optional);
    break;
  case 'make_order':
    AjaxRequest::createOrder($output, $state, $optional, $info);
    break;
  default:
}

// Push back info
AjaxRequest::setJsonOutput(
  array(
    'html' => $output,
    'optional' => $optional,
  ),
  $state,
  $info
);