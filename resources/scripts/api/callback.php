<?php

require_once '../../../../../../wp-load.php';

use Supertext\Polylang\Core;
use Supertext\Polylang\Api\Wrapper;
use Supertext\Polylang\Api\Multilang;

// Get json request body
$response = array('message' => 'unknown error');
$requestBody = file_get_contents('php://input');
$json = json_decode($requestBody);

$refData = explode('-', $json->ReferenceData, 2);
$postId = $refData[0];
$secureToken = $refData[1];

// check md5 Secure String
if (md5(Wrapper::REFERENCE_HASH . $postId) == $secureToken) {

  // Yes a valid confirmation -> load post object
  $targetLang = substr($json->TargetLang, 0, 2);
  $translationPostId = intval(Multilang::getPostInLanguage($postId, $targetLang));

  // Only if valid, countinue
  if ($translationPostId > 0 ) {
    // Get the translation post object
    $post = get_post($translationPostId);
    // check if correct language
    if ($post->post_status == 'draft') {

      // Load attachments to merge later
      $attachments = get_children(array(
        'post_parent' => $post->ID,
        'post_type' => 'attachment',
        'orderby' => 'menu_order ASC, ID',
        'order' => 'DESC'
      ));

      // Create file links
      $attachmentFiles = array();
      foreach ($attachments as $attachment) {
        $fileLink = get_post_meta($attachment->ID, '_wp_attached_file', true);
        $attachmentFiles[$fileLink] = $attachment;
      }

      // Save all translations
      foreach ($json->Groups as $translationGroup) {
        switch ($translationGroup->GroupId) {
          case 'post':
            foreach ($translationGroup->Items as $translationItem) {
              $post->{$translationItem->Id} = html_entity_decode($translationItem->Content, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            }
            break;

          default:
            // Gallery images
            $groupData = explode('_', $translationGroup->GroupId);

            if ($groupData[0] == 'gallery' && $groupData[1] == 'image') {
              // load (via _wp_attached_file; show are any same named files available)
              $img_id = $groupData[2];
              $attachementJson = get_post($img_id);
              $fileLink = get_post_meta($attachementJson->ID, '_wp_attached_file', true);
              $translatedAttachment = $attachmentFiles[$fileLink];

              // Only fill in, if possible
              if (!empty($translatedAttachment)) {
                foreach ($translationGroup->Items as $translationItem) {
                  switch ($translationItem->Id) {
                    case 'image_alt':
                      update_post_meta(
                        $translatedAttachment->ID,
                        '_wp_attachment_image_alt',
                        addslashes(html_entity_decode($translationItem->Content, ENT_COMPAT | ENT_HTML401, 'UTF-8'))
                      );
                      break;

                    default:
                      $translatedAttachment->{$translationItem->Id} = html_entity_decode($translationItem->Content, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                      break;
                  }
                }

                // Save the attachment
                wp_update_post($translatedAttachment);
              }
            }
            break;
        }
      }

      // Now finally save that post and flush cache
      wp_update_post($post);

      $response['message'] = __('translation saved successfully', 'polylang-supertext');
      Core::getInstance()->getLog()->addEntry($translationPostId, $response['message']);
    } else {
      $response['message'] = __('error: can only import into draft article', 'polylang-supertext');
      Core::getInstance()->getLog()->addEntry($translationPostId, $response['message']);
    }
  } else {
    $response['message'] = __('error: wrong language or translation post has been deleted', 'polylang-supertext');
    Core::getInstance()->getLog()->addEntry($postId, $response['message']);
  }
} else {
  $response['message'] = __('error: method not allowed', 'polylang-supertext');
  Core::getInstance()->getLog()->addEntry($postId, $response['message']);
}

// Print the response
header('Content-Type: application/json');
echo json_encode($response);