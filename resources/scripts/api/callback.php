<?php

//@deprecated
//TODO remove once all plugins updated and no orders with old callback url exists

require_once '../../../../../../wp-load.php';

$core = Supertext\Polylang\Core::getInstance();
$callbackHandler = $core->getCallbackHandler();
$callbackHandler->handleRequest();