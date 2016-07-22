<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Api\WriteBack;
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Api\Multilang;
use Comotive\Util\ArrayManipulation;

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
   * @param $json
   * @return array
   */
  public function handleRequest($json)
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

    $writeBack->removeReferenceData();

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
      $options = $this->library->getSettingOption();
      $workflowSettings = isset($options[Constant::SETTING_WORKFLOW]) ? ArrayManipulation::forceArray($options[Constant::SETTING_WORKFLOW]) : array();

      $isPostWritable =
        $translationPost->post_status == 'draft' ||
        ($translationPost->post_status == 'publish' && $workflowSettings['overridePublishedPosts']) ||
        intval(get_post_meta($translationPost->ID, Constant::IN_TRANSLATION_FLAG, true)) === 1;

      if (!$isPostWritable) {
        $errors[$postId] = 'The post for saving the translation is not writable.';
        continue;
      }

      $this->contentProvider->prepareTranslationPost(get_post($postId), $translationPost);
      $this->contentProvider->saveTranslatedData($translationPost, $translationData[$postId]);

      if ($workflowSettings['publishOnCallback']) {
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