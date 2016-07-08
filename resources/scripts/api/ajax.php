<?php

require_once '../../../../../../wp-admin/admin.php';

$core = Supertext\Polylang\Core::getInstance();
$ajaxRequestHandler = $core->getAjaxRequestHandler();

if(!isset($_GET['action'])){
  http_response_code(400);
}else{
  $ajaxRequestHandler->handleRequest($_GET['action'], $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET);
}