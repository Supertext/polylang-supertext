<?php

require_once '../../../../../../wp-admin/admin.php';

use Supertext\Polylang\Backend\AjaxRequest;

switch ($_GET['action']) {
  case 'getOffer':
    AjaxRequest::getOffer();
    break;
  case 'createOrder':
    AjaxRequest::createOrder();
    break;
  default:
}