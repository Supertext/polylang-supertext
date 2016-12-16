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
        $this->handleWriteBackRequest($json);
      }catch (\Exception $e){
        self::returnResponse(500, array('message' => $e->getMessage()));
      }
    }else{
      self::returnResponse(400, array('message' => 'Invalid request body'));
    }
  }

  /**
   * @param $code
   * @param $body
   */
  private static function returnResponse($code, $body)
  {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($body);
    die();
  }

  /**
   * @param $json
   */
  private function handleWriteBackRequest($json)
  {
    $writeBack = new WriteBack($json, $this->library);

    if(!$writeBack->isReferenceValid()){
      self::returnResponse(403, array('message' => 'Error: reference is invalid.'));
    }

    $this->writeBackTranslation($writeBack);

    self::returnResponse(200, array('message' => 'The translation was saved successfully'));
  }

  /**
   * @param WriteBack $writeBack
   */
  private function writeBackTranslation($writeBack)
  {
    $errors = array();
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

      $this->log->addEntry($translationPostId, __('translation saved successfully', 'Polylang-Supertext'));
    }

    if(count($errors)){
      $message = 'Errors: ';
      foreach($errors as $postId => $error){
        $message .= "Concerning post with id $postId" .' -> ' . $error;
      }
      self::returnResponse(500, array('message' => $message));
    }
  }
}