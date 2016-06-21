<?php

require_once '../../../../../../wp-admin/admin.php';

$core = Supertext\Polylang\Core::getInstance();
$ajaxRequestHandler = $core->getAjaxRequestHandler();
$ajaxRequestHandler->handleRequest($_GET['action'], $_POST);