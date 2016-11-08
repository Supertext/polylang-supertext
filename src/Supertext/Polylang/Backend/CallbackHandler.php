<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Api\WriteBack;
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Api\Multilang;

class CallbackHandler
{
  /**
   * @var \Supertext\Polylang\Helper\Library
   */
  private $library;

  /**
   * @var Log
   */
  private $log;

  /**
   * @var ContentProvider
   */
  private $contentProvider;

  /**
   * @param \Supertext\Polylang\Helper\Library $library
   * @param Log $log
   * @param ContentProvider $contentProvider
   */
  public function __construct($library, $log, $contentProvider)
  {
    $this->library = $library;
    $this->log = $log;
    $this->contentProvider = $contentProvider;
  }

  /**
   * Handles a callback request
   */
  public function handleRequest(){
    $requestBody = file_get_contents('php://input');
    $json = json_decode($requestBody);

    if($requestBody === true || !empty($json)){
      try{
        $response = $this->handleWriteBackRequest($json);
      }catch (\Exception $e){
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

    header('Content-Type: application/json');
    http_response_code($response['code']);
    echo json_encode($response['body']);
    die();
  }

  /**
   * @param $json
   * @return array
   */
  private function handleWriteBackRequest($json)
  {
    $writeBack = new WriteBack($json, $this->library);

    $error = $writeBack->validate();

    if($error != null){
      return $this->createResponse($error['code'], $error['message']);
    }

    $errors = $this->writeBackTranslation($writeBack);

    if(count($errors)){
      $message = 'Errors: ';
      foreach($errors as $postId => $error){
        $message .= "Concerning post with id $postId" .' -> ' . $error;
      }
      return $this->createResponse(500, $message);
    }

    return $this->createResponse(200, 'The translation was saved successfully');
  }

  /**
   * @param WriteBack $writeBack
   * @return array errors
   */
  private function writeBackTranslation($writeBack)
  {
    $errors = array();
    $successMessage = __('translation saved successfully', 'Polylang-Supertext');
    $translationData = $writeBack->getTranslationData();

    foreach ($writeBack->getPostIds() as $postId) {
      $translationPostId = Multilang::getPostInLanguage($postId, $writeBack->getTargetLanguage());

      if ($translationPostId == null) {
        $errors[$postId] = 'There is no linked post for saving the translation.';
        continue;
      }

      // Get the translation post object
      $translationPost = get_post($translationPostId);
      $workflowSettings = $this->library->getSettingOption(Constant::SETTING_WORKFLOW);

      $isPostWritable =
        $translationPost->post_status == 'draft' ||
        ($translationPost->post_status == 'publish' && isset($workflowSettings['overridePublishedPosts']) && $workflowSettings['overridePublishedPosts']) ||
        intval(get_post_meta($translationPost->ID, Constant::IN_TRANSLATION_FLAG, true)) === 1;

      if (!$isPostWritable) {
        $errors[$postId] = 'The post for saving the translation is not writable.';
        continue;
      }

      $this->contentProvider->prepareTranslationPost(get_post($postId), $translationPost);
      $this->contentProvider->saveTranslatedData($translationPost, $translationData[$postId]);

      if (isset($workflowSettings['publishOnCallback'])  && $workflowSettings['publishOnCallback']) {
        $translationPost->post_status = 'publish';
      }

      // Now finally save that post and flush cache
      wp_update_post($translationPost);

      // All good, remove translation flag
      delete_post_meta($translationPost->ID, Constant::IN_TRANSLATION_FLAG);

      $this->log->addEntry($translationPostId, $successMessage);
    }

    return $errors;
  }

  /**
   * @param $code
   * @param $message
   * @return array
   */
  private function createResponse($code, $message)
  {
    return array(
      'code' => $code,
      'body' => array('message' => $message)
    );
  }
}