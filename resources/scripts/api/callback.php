<?php

require_once '../../../../../../wp-load.php';

use Supertext\Polylang\Core;
use Supertext\Polylang\Api\Wrapper;
use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Backend\Translation;
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

// Get json request body
$library = Core::getInstance()->getLibrary();
$options = $library->getSettingOption();
$workflowSettings = isset($options[Constant::SETTING_WORKFLOW]) ? ArrayManipulation::forceArray($options[Constant::SETTING_WORKFLOW]) : array();

$response = array('message' => 'unknown error');
$requestBody = file_get_contents('php://input');
$json = json_decode($requestBody);

if($requestBody === false || empty($requestBody) || empty($json)){
  http_response_code(400);
  return;
}

$refData = explode('-', $json->ReferenceData, 2);
$postId = $refData[0];
$secureToken = $refData[1];

// check md5 Secure String
if (md5(Wrapper::REFERENCE_HASH . $postId) == $secureToken) {

  // Yes a valid confirmation -> load post object
  $targetLang = substr($json->TargetLang, 0, 2);
  $translationPostId = intval(Multilang::getPostInLanguage($postId, $targetLang));

  // Only if valid, countinue
  if ($translationPostId > 0) {
    // Get the translation post object
    $post = get_post($translationPostId);

    $isPostWritable =
      $post->post_status == 'draft' ||
      ($post->post_status == 'publish' && $workflowSettings['overridePublishedPosts']) ||
      intval(get_post_meta($post->ID, Translation::IN_TRANSLATION_FLAG, true)) === 1;

    if ($isPostWritable) {

      // Save all translations
      foreach ($json->Groups as $translationGroup) {
        switch ($translationGroup->GroupId) {
          case 'post':
            foreach ($translationGroup->Items as $translationItem) {
              $decodedContent = html_entity_decode($translationItem->Content, ENT_COMPAT | ENT_HTML401, 'UTF-8');

              if($translationItem->Id === 'post_content'){
                $decodedContent = $library->replaceShortcodeNodes($decodedContent);
              }

              $post->{$translationItem->Id} = $decodedContent;
            }
            break;
          case 'meta':
            foreach ($translationGroup->Items as $translationItem) {
              $decodedContent = html_entity_decode($translationItem->Content, ENT_COMPAT | ENT_HTML401, 'UTF-8');
              $decodedContent = Core::getInstance()->getLibrary()->replaceShortcodeNodes($decodedContent);
              update_post_meta($post->ID, $translationItem->Id, $decodedContent);
            }
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

      // Now finally save that post and flush cache
      wp_update_post($post);
      // All good, remove translation flag
      delete_post_meta($post->ID, Translation::IN_TRANSLATION_FLAG);

      $response['message'] = __('translation saved successfully', 'polylang-supertext');
      Core::getInstance()->getLog()->addEntry($translationPostId, $response['message']);
    } else {
      $response['message'] = __('Error: translation import only possible for drafted articles', 'polylang-supertext');
      Core::getInstance()->getLog()->addEntry($translationPostId, $response['message']);
      http_response_code(403);
    }
  } else {
    $response['message'] = __('Error: the language of the translation from Supertext does not match or the translated post has been deleted', 'polylang-supertext');
    Core::getInstance()->getLog()->addEntry($postId, $response['message']);
    http_response_code(404);
  }
} else {
  $response['message'] = __('Error: method not allowed', 'polylang-supertext');
  Core::getInstance()->getLog()->addEntry($postId, $response['message']);
  http_response_code(403);
}

// Print the response
header('Content-Type: application/json');
echo json_encode($response);