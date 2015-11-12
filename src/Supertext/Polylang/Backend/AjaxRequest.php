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

  public static function createTranslationPost($options){
    $hasImages = false;
    $excerpts = array();
    $postId = $options['post_id'];
    $post = get_post($postId);

    // Create post object
    $translationPostData = array(
      'post_status'   => 'draft',
      'post_title'    => $post->post_title . ' ' . Translation::IN_TRANSLATION_TEXT,
      'post_type'     => $post->post_type
    );

    foreach ($options['pattern'] as $field_name => $selected) {
      $field_name_parts = explode('_', $field_name);

      if (!$selected) {
        continue;
      }

      if($field_name_parts[0] === 'post'){
        switch($field_name_parts[1]){
          case 'title':
          case 'status':
          case 'type':
            break;
          case 'image':
            $hasImages = true;
          default:
            $translationPostData[$field_name] = Translation::IN_TRANSLATION_TEXT;
        }
      }else if($field_name_parts[0] !== 'excerpt'){
        $excerpts[] = $field_name_parts[1];
      }
    }

    $translationPostId = wp_insert_post($translationPostData);

    if($translationPostId === 0){
      return null;
    }

    if($hasImages){
      // Set all images to default
      $attachments = get_children(array('post_parent' => $translationPostId, 'post_type' => 'attachment', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC'));
      foreach ($attachments as $attachement_post) {
        $attachement_post->post_title = Translation::IN_TRANSLATION_TEXT;
        $attachement_post->post_content = Translation::IN_TRANSLATION_TEXT;
        $attachement_post->post_excerpt = Translation::IN_TRANSLATION_TEXT;
        // Update meta and update attachmet post
        update_post_meta($attachement_post->ID, '_wp_attachment_image_alt', addslashes(Translation::IN_TRANSLATION_TEXT));
        wp_update_post($attachement_post);
      }
    }

    foreach ($excerpts as $excerpt) {
      update_post_meta($translationPostId, '_excerpt_' .$excerpt, Translation::IN_TRANSLATION_TEXT);
      update_post_meta($translationPostId, '_modified_excerpt_' . $excerpt, 1);
    }

    Multilang::setPostLanguage($translationPostId, $options['target_lang']);

    $postsLanguageMappings = array(
      $options['source_lang'] => $postId,
      $options['target_lang'] => $translationPostId
    );

    foreach (Multilang::getLanguages() as $language) {
      $languagePostId = Multilang::getPostInLanguage($postId, $language->slug);
      if($languagePostId){
        $postsLanguageMappings[$language->slug] = $languagePostId;
      }
    }

    Multilang::savePostTranslations($postsLanguageMappings);

    Core::getInstance()->getLog()->addEntry($translationPostId, __('The translatable article has been created.', 'polylang-supertext'));

    return get_post($translationPostId);
  }

  public static function createOrder()
  {
    // Call the API for prices
    $output = '';
    $options = self::getTranslationOptions();
    $postId = $options['post_id'];
    $translationPostId = intval(Multilang::getPostInLanguage($postId, $options['target_lang']));

    if($translationPostId === 0){
      $translationPost = self::createTranslationPost($options);
    }else{
      $translationPost = get_post($translationPostId);
    }

    if($translationPost === null){
      self::setJsonOutput(
        array(
          'html' => __('Could not create new post for the translation.',' polylang-supertext'),
        ),
        'error'
      );
      return;
    }

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
      $output = '
        <p>
          ' . __('The order has been placed successfully.', 'polylang-supertext') . '<br />
          ' . sprintf(__('Your order number is %s.', 'polylang-supertext'), $order->Id) . '<br />
          ' . sprintf(
                __('The article will be translated until %s.', 'polylang-supertext'),
                date_i18n('D, d. F H:i', strtotime($order->Deadline))
          ) . '
        </p>
        <p>' . __('One moment, the window closes itself in a few seconds and finishes the order. Don\'t close the window yet.', 'polylang-supertext') . '</p>
      ';

      // Log the success and the order id
      $message = sprintf(
        __('Order for article translation to %s successfully placed. Your order number is %s.', 'polylang-supertext'),
        self::getLanguageName($options['target_lang']),
        $order->Id
      );
      $log->addEntry($post->ID, $message);
      $log->addOrderId($post->ID, $order->Id);
      $log->addOrderId($translationPost->ID, $order->Id);

      update_post_meta($translationPost->ID, Translation::IN_TRANSLATION_FLAG, 1);

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
          'html' => $output,
        ),
        'error'
      );
    }
  }

  /**
   * @param string $key slug to search
   * @return string name of the $key language
   */
  public static function getLanguageName($key)
  {
    // Get the supertext key
    $stKey = Core::getInstance()->getLibrary()->mapLanguage($key);
    return __($stKey, 'polylang-supertext-langs');
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
          'html' => __('There are no offers for this translation.',' polylang-supertext'),
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
                    <td class="qualityGroupCell" rowspan="'.($itemsCount+1).'"><strong>'.$option['name'].'</strong></td>
                    <td class="selectionCell">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>';

      foreach($option['items'] as $groupRowNumber =>  $item){
        $radioInputId = $option['id'] . "_" . $item['id'];
        $radioInputValue = $option['id'] . ":" . $item['id'];

        $rows .= '
          <tr>
            <td class="selectionCell">
              <input type="radio" data-currency="' . $pricing['currency'] . '" name="rad_translation_type" id="rad_translation_type_' . $radioInputId . '" value="' . $radioInputValue . '" '.$checked.'>
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
                '.$rows.'
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
   * @return array translation info
   */
  protected static function getTranslationOptions()
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
      'product_id' => $_POST['rad_translation_type'],
      'additional_information' => stripslashes($_POST['txtComment']),
    );

    return $options;
  }



  /**
   * @param array $data data to be sent in body
   * @param string $state the state
   * @param string $info additional request information
   */
  public static function setJsonOutput($data, $state = 'success', $info = '')
  {
    $json = array(
      'head' => array(
        'status' => $state,
        'info' => $info
      ),
      'body' => $data
    );
    header('Content-Type: application/json');
    echo json_encode($json);
  }
}
