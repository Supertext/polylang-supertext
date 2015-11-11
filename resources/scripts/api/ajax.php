<?php

require_once '../../../../../../wp-admin/admin.php';

use Supertext\Polylang\Backend\AjaxRequest;

switch ($_GET['action']) {
  case 'getOffer':
    AjaxRequest::getOffer($output, $state, $optional);
    break;
  case 'createOrder':
    AjaxRequest::createOrder($output, $state, $optional, $info);
    break;
  default:
}