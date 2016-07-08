<?php

namespace Supertext\Polylang\Backend;

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
    $refData = explode('-', $json->ReferenceData, 2);
    $postId = $refData[0];
    $secureToken = $refData[1];
    $targetLang = substr($json->TargetLang, 0, 2);
    $translationPostId = Multilang::getPostInLanguage($postId, $targetLang);

    // Only if valid, continue
    if ($translationPostId == null) {
      $message = __('Error: the language of the translation from Supertext does not match or the translated post has been deleted', 'polylang-supertext');
      $this->log->addEntry($postId, $message);
      return $this->createResult(404, $message);
    }

    $referenceHash = get_post_meta($translationPostId, Constant::IN_TRANSLATION_REFERENCE_HASH, true);

    // check md5 Secure String
    if (empty($referenceHash) || md5($referenceHash . $postId) !== $secureToken) {
      $message = __('Error: method not allowed', 'polylang-supertext');
      $this->log->addEntry($postId, $message);
      return $this->createResult(403, $message);
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
      $message = __('Error: translation import only possible for drafted articles', 'polylang-supertext');
      $this->log->addEntry($translationPostId, $message);
      return $this->createResult(403, $message);
    }

    $this->contentProvider->prepareTranslationPost(get_post($postId), $translationPost);
    $this->contentProvider->saveTranslatedData($translationPost, $json);

    if ($workflowSettings['publishOnCallback']) {
      $translationPost->post_status = 'publish';
    }

    // Now finally save that post and flush cache
    wp_update_post($translationPost);

    // All good, remove translation flag
    delete_post_meta($translationPost->ID, Constant::IN_TRANSLATION_FLAG);

    $message = __('translation saved successfully', 'polylang-supertext');
    $this->log->addEntry($translationPostId, $message);
    return $this->createResult(200, $message);
  }

  /**
   * @param $code
   * @param $message
   * @return array
   */
  private function createResult($code, $message)
  {
    return array(
      'code' => $code,
      'response' => array('message' => $message)
    );
  }
}