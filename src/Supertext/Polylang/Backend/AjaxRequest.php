<?php

namespace Supertext\Polylang\Backend;

use Comotive\Util\String;
use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Api\Wrapper;
use Supertext\Polylang\Core;

/**
 * Provided ajax request handlers
 * @package Supertext\Polylang\Backend
 * @author Michael Hadorn <michael.hadorn@blogwerk.com> (inline code)
 * @author Michael Sebel <michael@comotive.ch> (refactoring)
 */
class AjaxRequest
{
  const TRANSLATION_POST_STATUS = 'draft';

  /**
   * Creates the order
   */
  public static function createOrder()
  {
    // Call the API for prices
    $options = self::getTranslationOptions();
    $postId = $options['post_id'];

    $library = Core::getInstance()->getLibrary();
    $data = $library->getTranslationData($postId, $options['pattern']);
    $post = get_post($postId);
    $wrapper = $library->getUserWrapper();
    $log = Core::getInstance()->getLog();

    // Create the order
    $order = $wrapper->createOrder(
      $options['source_lang'],
      $options['target_lang'],
      get_bloginfo('name') . ' - ' . $post->post_title,
      $options['product_id'],
      $data,
      SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/api/callback.php',
      $post->ID . '-' . md5(Wrapper::REFERENCE_HASH . $post->ID),
      $options['additional_information']
    );

    if (!empty($order->Deadline) && !empty($order->Id)) {
      $translationPostId = intval(Multilang::getPostInLanguage($postId, $options['target_lang']));

      if ($translationPostId == 0) {
        $translationPost = self::createTranslationPost($postId, $options);

        if ($translationPost === null) {
          self::setJsonOutput(
            array(
              'reason' => __('Could not create a new post for the translation. You need to create the new post manually using Polylang.', ' polylang-supertext'),
            ),
            'error'
          );
          return;
        }

        $translationPostId = $translationPost->ID;
      }

      $output = '
        <p>
          ' . __('The order has been placed successfully.', 'polylang-supertext') . '<br />
          ' . sprintf(__('Your order number is %s.', 'polylang-supertext'), $order->Id) . '<br />
          ' . sprintf(
          __('The article will be translated by %s.', 'polylang-supertext'),
          date_i18n('D, d. F H:i', strtotime($order->Deadline))
        ) . '
        </p>
      ';

      // Log the success and the order id
      $message = sprintf(
        __('Order for translation of article into %s has been placed successfully. Your order number is %s.', 'polylang-supertext'),
        self::getLanguageName($options['target_lang']),
        $order->Id
      );
      $log->addEntry($post->ID, $message);
      $log->addOrderId($post->ID, $order->Id);
      $log->addOrderId($translationPostId, $order->Id);

      update_post_meta($translationPostId, Translation::IN_TRANSLATION_FLAG, 1);

      self::setJsonOutput(
        array(
          'html' => $output,
        ),
        'success'
      );

    } else {
      // Error, couldn't create a correct order
      $log->addEntry($post->ID, __('Error: Could not create an order with Supertext.', 'polylang-supertext'));

      self::setJsonOutput(
        array(
          'reason' => __('Error: Could not create an order with Supertext.', 'polylang-supertext'),
        ),
        'error'
      );
    }
  }

  /**
   * This was built by MHA by reference. No time to fix just yet, but it works.
   */
  public static function getOffer()
  {
    $optional = array('requestCounter' => $_POST['requestCounter']);

    // Call the API for prices
    $options = self::getTranslationOptions();
    $library = Core::getInstance()->getLibrary();
    $data = $library->getTranslationData($options['post_id'], $options['pattern']);
    $wrapper = $library->getUserWrapper();
    // Call for prices
    $pricing = $wrapper->getQuote(
      $options['source_lang'],
      $options['target_lang'],
      $data
    );

    //Check if there are no offers
    if (empty($pricing['options'])) {
      self::setJsonOutput(
        array(
          'html' => __('There are no offers for this translation.', ' polylang-supertext'),
          'optional' => $optional,
        ),
        'no_data'
      );
      return;
    }

    // generate html output
    $rows = '';
    $checked = 'checked="checked"';
    foreach ($pricing['options'] as $option) {
      $itemsCount = count($option['items']);

      $rows .= '<tr class="firstGroupRow">
                    <td class="qualityGroupCell" rowspan="' . ($itemsCount + 1) . '"><strong>' . $option['name'] . '</strong></td>
                    <td class="selectionCell">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>';

      foreach ($option['items'] as $groupRowNumber => $item) {
        $radioInputId = $option['id'] . "_" . $item['id'];
        $radioInputValue = $option['id'] . ":" . $item['id'];

        $rows .= '
          <tr>
            <td class="selectionCell">
              <input type="radio" data-currency="' . $pricing['currency'] . '" name="rad_translation_type" id="rad_translation_type_' . $radioInputId . '" value="' . $radioInputValue . '" ' . $checked . '>
            </td>
            <td>
              <label for="rad_translation_type_' . $radioInputId . '">' . $item['name'] . '</label>
            </td>
            <td align="right" class="ti_deadline">
              <label for="rad_translation_type_' . $radioInputId . '">' . date_i18n('D, d. F H:i', strtotime($item['date'])) . '</label>
            </td>
            <td align="right" class="ti_price">
              <label for="rad_translation_type_' . $radioInputId . '">' . $pricing['currency'] . ' ' . String::numberFormat($item['price'], 2) . '</label>
            </td>
          </tr>
        ';

        $checked = '';
      }

      $rows .= '<tr class="lastGroupRow"></tr>';
    }

    $output =
      '<table border="0" cellpadding="2" cellspacing="0">
            <tbody>
                ' . $rows . '
            </tbody>
          </table>';

    self::setJsonOutput(
      array(
        'html' => $output,
        'optional' => $optional,
      ),
      'success'
    );
  }

  /**
   * @param string $key slug to search
   * @return string name of the $key language
   */
  private static function getLanguageName($key)
  {
    // Get the supertext key
    $stKey = Core::getInstance()->getLibrary()->mapLanguage($key);
    return __($stKey, 'polylang-supertext-langs');
  }

  /**
   * @return array translation info
   */
  private static function getTranslationOptions()
  {
    $options = array();
    foreach ($_POST as $field_name => $field_value) {
      // Search texts
      if (substr($field_name, 0, 3) == 'to_') {
        $field_name = substr($field_name, 3);
        $options[$field_name] = true;
      }
    }

    // Param zusammenstellen
    $options = array(
      'post_id' => $_POST['post_id'],
      'pattern' => $options,
      'source_lang' => $_POST['source_lang'],
      'target_lang' => $_POST['target_lang'],
      'product_id' => isset($_POST['rad_translation_type']) ? $_POST['rad_translation_type'] : 0,
      'additional_information' => stripslashes($_POST['txtComment']),
    );

    return $options;
  }

  /**
   * @param array $data data to be sent in body
   * @param string $state the state
   * @param string $info additional request information
   */
  private static function setJsonOutput($data, $state = 'success')
  {
    $json = array(
      'head' => array(
        'status' => $state
      ),
      'body' => $data
    );
    header('Content-Type: application/json');
    echo json_encode($json);
  }

  /**
   * @param $postId
   * @param $options
   * @return array|null|\WP_Post
   */
  private static function createTranslationPost($postId, $options)
  {
    $translationPostId = self::createNewPostFrom($postId);

    if ($translationPostId === 0) {
      return null;
    }

    $translationPost = get_post($translationPostId);

    self::AddImageAttachments($postId, $translationPostId, $options['source_lang'], $options['target_lang']);

    self::copyPostMetas($postId, $translationPostId, $options['target_lang']);

    self::AddInTranslationTexts($options, $translationPost);

    wp_update_post($translationPost);

    self::SetLanguage($postId, $translationPostId, $options['source_lang'], $options['target_lang']);

    Core::getInstance()->getLog()->addEntry($translationPostId, __('The article to be translated has been created.', 'polylang-supertext'));

    return $translationPost;
  }

  /**
   * @param $postId
   * @return int|\WP_Error
   */
  private static function createNewPostFrom($postId)
  {
    $post = get_post($postId);

    $translationPostData = array(
      'post_author' => wp_get_current_user()->ID,
      'post_mime_type' => $post->post_mime_type,
      'post_password' => $post->post_password,
      'post_status' => self::TRANSLATION_POST_STATUS,
      'post_title' => $post->post_title,
      'post_type' => $post->post_type,
      'menu_order' => $post->menu_order,
      'comment_status' => $post->comment_status,
      'ping_status' => $post->ping_status,
    );

    return wp_insert_post($translationPostData);
  }

  /**
   * @param $sourcePostId
   * @param $targetPostId
   * @param $sourceLang
   * @param $targetLang
   */
  private static function AddImageAttachments($sourcePostId, $targetPostId, $sourceLang, $targetLang)
  {
    $sourceAttachments = get_children(array(
        'post_parent' => $sourcePostId,
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'orderby' => 'menu_order ASC, ID',
        'order' => 'DESC')
    );

    foreach ($sourceAttachments as $sourceAttachment) {
      $sourceAttachmentId = $sourceAttachment->ID;
      $sourceAttachmentLink = get_post_meta($sourceAttachmentId, '_wp_attached_file', true);
      $sourceAttachmentMetadata = get_post_meta($sourceAttachmentId, '_wp_attached_file', true);

      $targetAttachmentId = intval(Multilang::getPostInLanguage($sourceAttachmentId, $targetLang));

      if ($targetAttachmentId == null) {
        $targeAttachment = $sourceAttachment;
        $targeAttachment->ID = null;
        $targeAttachment->post_parent = $targetPostId;
        $targetAttachmentId = wp_insert_attachment($targeAttachment);
        add_post_meta($targetAttachmentId, '_wp_attachment_metadata', $sourceAttachmentMetadata);
        add_post_meta($targetAttachmentId, '_wp_attached_file', $sourceAttachmentLink);
        self::SetLanguage($sourceAttachmentId, $targetAttachmentId, $sourceLang, $targetLang);
      }else{
        $targetAttachment = get_post($targetAttachmentId);
        $targeAttachment->post_parent = $targetPostId;
        wp_insert_attachment($targetAttachment);
      }
    }
  }

  /**
   * Copy post metas using polylang
   * @param $postId
   * @param $translationPostId
   * @param $target_lang
   */
  private static function copyPostMetas($postId, $translationPostId, $target_lang)
  {
    global $polylang;

    if(empty($polylang)){
      return;
    }

    $polylang->sync->copy_post_metas($postId, $translationPostId, $target_lang);
  }

  /**
   * @param $options
   * @param $translationPostId
   * @param $translationPost
   */
  private static function AddInTranslationTexts($options, $translationPost)
  {
    foreach ($options['pattern'] as $field_name => $selected) {
      $field_name_parts = explode('_', $field_name);

      if (!$selected || $field_name_parts[0] !== 'post') {
        continue;
      }

      switch ($field_name_parts[1]) {
        case 'title':
          $translationPost->post_title = $translationPost->post_title . Translation::IN_TRANSLATION_TEXT;
          break;
        case 'image':
          // Set all images to default
          $attachments = get_children(array(
              'post_parent' => $translationPost->ID,
              'post_type' => 'attachment',
              'post_mime_type' => 'image',
              'orderby' => 'menu_order ASC, ID',
              'order' => 'DESC')
          );

          foreach ($attachments as $attachment_post) {
            $attachment_post->post_title = Translation::IN_TRANSLATION_TEXT;
            $attachment_post->post_content = Translation::IN_TRANSLATION_TEXT;
            $attachment_post->post_excerpt = Translation::IN_TRANSLATION_TEXT;
            // Update meta and update attachmet post
            update_post_meta($attachment_post->ID, '_wp_attachment_image_alt', addslashes(Translation::IN_TRANSLATION_TEXT));
            wp_update_post($attachment_post);
          }
        default:
          $translationPost->{$field_name} = Translation::IN_TRANSLATION_TEXT;
      }
    }
  }

  /**
   * @param $sourcePostId
   * @param $targetPostId
   * @param $sourceLanguage
   * @param $targetLanguage
   */
  private static function SetLanguage($sourcePostId, $targetPostId, $sourceLanguage, $targetLanguage)
  {
    Multilang::setPostLanguage($targetPostId, $targetLanguage);

    $postsLanguageMappings = array(
      $sourceLanguage => $sourcePostId,
      $targetLanguage => $targetPostId
    );

    foreach (Multilang::getLanguages() as $language) {
      $languagePostId = Multilang::getPostInLanguage($sourcePostId, $language->slug);
      if ($languagePostId) {
        $postsLanguageMappings[$language->slug] = $languagePostId;
      }
    }

    Multilang::savePostTranslations($postsLanguageMappings);
  }
}
