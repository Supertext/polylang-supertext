<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Core;
use Supertext\Polylang\Backend\Translation;
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

class CallbackHandler
{
  public function handleRequest($json)
  {
    $refData = explode('-', $json->ReferenceData, 2);
    $postId = $refData[0];
    $secureToken = $refData[1];
    $targetLang = substr($json->TargetLang, 0, 2);
    $translationPostId = intval(Multilang::getPostInLanguage($postId, $targetLang));

    // Only if valid, continue
    if ($translationPostId == null) {
      $message = __('Error: the language of the translation from Supertext does not match or the translated post has been deleted', 'polylang-supertext');
      Core::getInstance()->getLog()->addEntry($postId, $message);
      return $this->createResult(404, $message);
    }

    $referenceHash = get_post_meta($translationPostId, Translation::IN_TRANSLATION_REFERENCE_HASH, true);

    // check md5 Secure String
    if (empty($referenceHash) || md5($referenceHash . $postId) !== $secureToken) {
      $message = __('Error: method not allowed', 'polylang-supertext');
      Core::getInstance()->getLog()->addEntry($postId, $message);
      return $this->createResult(403, $message);
    }

    // Get the translation post object
    $translationPost = get_post($translationPostId);
    $library = Core::getInstance()->getLibrary();
    $options = $library->getSettingOption();
    $workflowSettings = isset($options[Constant::SETTING_WORKFLOW]) ? ArrayManipulation::forceArray($options[Constant::SETTING_WORKFLOW]) : array();

    $isPostWritable =
      $translationPost->post_status == 'draft' ||
      ($translationPost->post_status == 'publish' && $workflowSettings['overridePublishedPosts']) ||
      intval(get_post_meta($translationPost->ID, Translation::IN_TRANSLATION_FLAG, true)) === 1;

    if (!$isPostWritable) {
      $message = __('Error: translation import only possible for drafted articles', 'polylang-supertext');
      Core::getInstance()->getLog()->addEntry($translationPostId, $message);
      return $this->createResult(403, $message);
    }

    Core::getInstance()->getContentProvider()->SaveTranslatedData(get_post($postId), $translationPost, $json);

    if ($workflowSettings['publishOnCallback']) {
      $translationPost->post_status = 'publish';
    }

    // Now finally save that post and flush cache
    wp_update_post($translationPost);

    // All good, remove translation flag
    delete_post_meta($translationPost->ID, Translation::IN_TRANSLATION_FLAG);

    $message = __('translation saved successfully', 'polylang-supertext');
    Core::getInstance()->getLog()->addEntry($translationPostId, $message);
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