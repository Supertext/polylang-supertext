<?php

require_once '../../../../../../wp-load.php';

$response = array(
  'code' => 400,
  'body' => array('message' => '')
);

$requestBody = file_get_contents('php://input');
$json = json_decode($requestBody);

if($requestBody === true || !empty($json)){
  try{
    $core = Supertext\Polylang\Core::getInstance();
    $callbackHandler = $core->getCallbackHandler();
    $response = $callbackHandler->handleRequest($json);
  }catch (Exception $e){
    $response = array(
      'code' => 500,
      'body' => array('message' => $e->getMessage())
    );
  }

}else{
  $response = array(
    'code' => 400,
    'body' => array('message' => 'Invalid request body')
  );
}

// Print the response
header('Content-Type: application/json');
http_response_code($response['code']);
echo json_encode($response['body']);
