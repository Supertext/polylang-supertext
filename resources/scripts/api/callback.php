<?php

require_once '../../../../../../wp-load.php';

$result = array(
  'code' => 400,
  'response' => array('message' => '')
);

$requestBody = file_get_contents('php://input');
$json = json_decode($requestBody);

if($requestBody === true || !empty($json)){
  $core = Supertext\Polylang\Core::getInstance();
  $callbackHandler = $core->getCallbackHandler();
  $result = $callbackHandler->handleRequest($json);
}else{
  $result = array(
    'code' => 400,
    'response' => array('message' => 'Invalid request body')
  );
}

// Print the response
header('Content-Type: application/json');
http_response_code($result['code']);
echo json_encode($result['response']);
