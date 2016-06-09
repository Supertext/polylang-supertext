<?php

namespace Supertext\Polylang\Api;

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
    $post = get_post($translationPostId);
    $library = Core::getInstance()->getLibrary();
    $options = $library->getSettingOption();
    $workflowSettings = isset($options[Constant::SETTING_WORKFLOW]) ? ArrayManipulation::forceArray($options[Constant::SETTING_WORKFLOW]) : array();

    $isPostWritable =
      $post->post_status == 'draft' ||
      ($post->post_status == 'publish' && $workflowSettings['overridePublishedPosts']) ||
      intval(get_post_meta($post->ID, Translation::IN_TRANSLATION_FLAG, true)) === 1;

    if (!$isPostWritable) {
      $message = __('Error: translation import only possible for drafted articles', 'polylang-supertext');
      Core::getInstance()->getLog()->addEntry($translationPostId, $message);
      return $this->createResult(403, $message);
    }

    $this->saveTranslations($post, $json, $targetLang);

    Core::getInstance()->getContentProvider()->SaveTranslatedData($postId, $translationPostId, $json);

    if ($workflowSettings['publishOnCallback']) {
      $post->post_status = 'publish';
    }

    // Now finally save that post and flush cache
    wp_update_post($post);

    // All good, remove translation flag
    delete_post_meta($post->ID, Translation::IN_TRANSLATION_FLAG);

    $message = __('translation saved successfully', 'polylang-supertext');
    Core::getInstance()->getLog()->addEntry($translationPostId, $message);
    return $this->createResult(200, $message);
  }

  /**
   * @param $post
   * @param $json
   * @param $targetLang
   */
  private function saveTranslations($post, $json, $targetLang)
  {
    foreach ($json->Groups as $translationGroup) {
      switch ($translationGroup->GroupId) {
        case 'post':
          foreach ($translationGroup->Items as $translationItem) {
            $decodedContent = html_entity_decode($translationItem->Content, ENT_COMPAT | ENT_HTML401, 'UTF-8');

            if ($translationItem->Id === 'post_content') {
              $decodedContent = Core::getInstance()->getContentProvider()->replaceShortcodeNodes($decodedContent);
            }

            $post->{$translationItem->Id} = $decodedContent;
          }
          break;
        case 'meta':
          foreach ($translationGroup->Items as $translationItem) {
            $decodedContent = html_entity_decode($translationItem->Content, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            $decodedContent = Core::getInstance()->getContentProvider()->replaceShortcodeNodes($decodedContent);
            update_post_meta($post->ID, $translationItem->Id, $decodedContent);
          }
          break;
        case 'beaver_builder_texts':

          break;
        default:
          // Gallery images
          $groupData = explode('_', $translationGroup->GroupId);

          if ($groupData[0] != 'gallery' || $groupData[1] != 'image') {
            break;
          }

          $sourceAttachmentId = $groupData[2];
          $targetAttachmentId = intval(Multilang::getPostInLanguage($sourceAttachmentId, $targetLang));

          if ($targetAttachmentId > 0) {
            $targetAttachment = get_post($targetAttachmentId);

            foreach ($translationGroup->Items as $translationItem) {
              switch ($translationItem->Id) {
                case 'image_alt':
                  update_post_meta(
                    $targetAttachment->ID,
                    '_wp_attachment_image_alt',
                    addslashes(html_entity_decode($translationItem->Content, ENT_COMPAT | ENT_HTML401, 'UTF-8'))
                  );
                  break;

                default:
                  $targetAttachment->{$translationItem->Id} = html_entity_decode($translationItem->Content, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                  break;
              }

              // Save the attachment
              wp_update_post($targetAttachment);
            }
          }
          break;
      }
    }
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