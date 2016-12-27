<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Api\WriteBack;
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Helper\PostMeta;

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

    foreach ($writeBack->getSourcePostIds() as $sourcePostId) {
      $targetPostId = Multilang::getPostInLanguage($sourcePostId, $writeBack->getTargetLanguage());

      if ($targetPostId == null) {
        $errors[$sourcePostId] = 'There is no linked post for saving the translation.';
        continue;
      }

      // Get the translation post object
      $targetPost = get_post($targetPostId);
      $workflowSettings = $this->library->getSettingOption(Constant::SETTING_WORKFLOW);

      $isPostWritable =
        $targetPost->post_status == 'draft' ||
        ($targetPost->post_status == 'publish' && isset($workflowSettings['overridePublishedPosts']) && $workflowSettings['overridePublishedPosts']) ||
        PostMeta::from($targetPost->ID)->is(PostMeta::IN_TRANSLATION);

      if (!$isPostWritable) {
        $errors[$sourcePostId] = 'The post for saving the translation is not writable.';
        continue;
      }

      $this->contentProvider->prepareTargetPost(get_post($sourcePostId), $targetPost);
      $this->contentProvider->saveTranslatedData($targetPost, $translationData[$sourcePostId]);

      if (isset($workflowSettings['publishOnCallback'])  && $workflowSettings['publishOnCallback']) {
        $targetPost->post_status = 'publish';
      }

      // Now finally save that post and flush cache
      wp_update_post($targetPost);

      // All good, set translation flag false
      PostMeta::from($targetPost->ID)->set(PostMeta::IN_TRANSLATION, false);

      $this->log->addEntry($targetPostId, __('translation saved successfully', 'Polylang-Supertext'));
    }

    if(count($errors)){
      $message = 'Errors: ';
      foreach($errors as $sourcePostId => $error){
        $message .= "Concerning post with id $sourcePostId" .' -> ' . $error;
      }
      self::returnResponse(500, array('message' => $message));
    }
  }
}