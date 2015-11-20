<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Core;
use Supertext\Polylang\Helper\Constant;

/**
 * Called in the backend/offer.php view
 * @package Supertext\Polylang\Backend
 * @author Michael Sebel <michael@comotive.ch>
 */
class OfferBox
{
  /**
   * @var int the post being translated
   */
  protected $postId = 0;
  /**
   * @var \WP_Post the post object
   */
  protected $post = null;
  /**
   * @var bool determines if the post is already translated in target
   */
  protected $hasExistingTranslation = false;
  /**
   * @var string the source language
   */
  protected $sourceLang = '';
  /**
   * @var string the target language
   */
  protected $targetLang = '';

  /**
   * Gather various meta information for the upcoming offer
   */
  public function __construct()
  {
    $this->postId = $_GET['postId'];
    $this->post = get_post($this->postId);
    $this->sourceLang = Multilang::getPostLanguage($this->post->ID);
    $this->targetLang = $_GET['targetLang'];
    $this->hasExistingTranslation = intval(Multilang::getPostInLanguage($this->postId, $this->targetLang)) > 0;

    wp_enqueue_style(Constant::POST_STYLE_HANDLE);
    wp_enqueue_script(Constant::GLOBAL_SCRIPT_HANDLE);
    wp_enqueue_script(Constant::TRANSLATION_SCRIPT_HANDLE);
  }

  /**
   * Display the offerbox html
   */
  public function displayOfferBox()
  {
    // Info of translation service
    $output = $this->getTranslationApiHtml();

    // Inform the user over a draft that might not be finished
    if ($this->post->post_status == 'draft') {
      $output .= '
        <div id="warning_draft_state" class="notice notice-warning">
          <p>
            ' . __('The articles status is <b>draft</b>.<br>Are you sure you want to order a translation for this article?', 'polylang-supertext') . '
          </p>
        </div>
      ';
    }

    // If there is an existing translation
    if ($this->hasExistingTranslation) {
      $output .= '
        <div id="warning_already_translated" class="notice notice-warning">
          <p>
            ' . __('There is already a translation for this post. The quote below may be higher than the final price if only parts of the content need to be translated again.', 'polylang-supertext') . '
          </p>
        </div>
      ';
    }

    // Print the actual form
    echo '
      <script type="text/javascript">
        jQuery(function() {
          Supertext.Polylang.addOfferEvents();
        });
      </script>
      <div id="div_tb_wrap_translation" class="div_tb_wrap_translation">
        <div id="div_translation_order_head">
          ' . $output . '
        </div>
        <div id="div_waiting_while_loading" style="display:none;">
          <p>
            <i>
              ' . __('One moment please. The translation order is being sent to Supertext.', 'polylang-supertext') . '<br>
              ' . __('Please do not close this window.', 'polylang-supertext') . '
            </i>
            <img src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/loader.gif" title="' . __('Loading', 'polylang-supertext') . '">
          </p>
        </div>
        <div id="div_translation_order_content">
          <form
            name="frm_Translation_Options"
            id="frm_Translation_Options"
            method="post"
           >
            <h3>' . sprintf(__('Translation of post %s', 'polylang-supertext'), $this->post->post_title) . '</h3>
            ' . sprintf(
                  __('The article will be translated from <b>%s</b> into <b>%s</b>.', 'polylang-supertext'),
                  $this->getLanguageName($this->sourceLang),
                  $this->getLanguageName($this->targetLang)
                ) . '
            <input type="hidden" name="post_id" value="' . intval($this->postId) . '">
            <input type="hidden" name="source_lang" id="source_lang" value="' . $this->sourceLang . '">
            <input type="hidden" name="target_lang" id="target_lang" value="' . $this->targetLang . '">

            <h3>' . __('Content to be translated', 'polylang-supertext') . '</h3>
            ' . $this->getCheckboxes(self::getTranslatableFields($this->postId)) . '
            <h3>' . __('Custom fields to be translated', 'polylang-supertext') . '</h3>
            <p>'.__('Translatable custom fields can be defined under Settings -> Supertext -> Custom Fields.', 'polylang-supertext').'</p>
            ' . $this->getCheckboxes(self::getTranslatableCustomFields($this->postId)) . '

            <h3>' . __('Service and deadline', 'polylang-supertext') . '</h3>
            <p>' . __('Select the translation service and deadline:', 'polylang-supertext') . '</p>

            <div class="div_translation_price_loading" id="div_translation_price_loading" style="height:200px;">
              ' . __('The price is being calculated, one moment please.', 'polylang-supertext') . '
              <img src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/loader.gif" title="' . __('Loading', 'polylang-supertext') . '">
            </div>
            <div id="div_translation_price" style="display:none;"></div>

            <h3>' . __('Your comment to Supertext', 'polylang-supertext') . '</h3>
            <p><textarea name="txtComment" id="txtComment"></textarea></p>

            <div class="div_translation_order_buttons">
              <input type="submit" name="btn_order" id="btn_order" value="' . __('Order translation', 'polylang-supertext') . '" class="button" />
            </div>
          </form>
        </div>
        <div id="div_close_translation_order_window" class="div_translation_order_buttons" style="display:none">
              <input type="button" id="btn_close_translation_order_window" class="button" value="'. __('Close window', 'polylang-supertext') .'" />
        </div>
      </div>
    ';

    // Add JS string translations
    Core::getInstance()->getTranslation()->addTranslations();
  }

  /**
   * @param string $key slug to search
   * @return string name of the $key language
   */
  protected function getLanguageName($key)
  {
    // Get the supertext key
    $stKey = Core::getInstance()->getLibrary()->mapLanguage($key);
    return __($stKey, 'polylang-supertext-langs');
  }

  /**
   * @param int $postId the post to be translated
   * @return array list of translatable fields
   */
  public static function getTranslatableFields($postId)
  {
    $result = array();

    $result[] = array(
      'title' => __('Title', 'polylang-supertext'),
      'name' => 'to_post_title',
      'default' => true
    );

    $result[] = array(
      'title' => __('Content', 'polylang-supertext'),
      'name' => 'to_post_content',
      'default' => true
    );

    $result[] = array(
      'title' => __('Excerpt', 'polylang-supertext'),
      'name' => 'to_post_excerpt',
      'default' => true
    );

    // Texts for images
    $result[] = array(
      'title' => __('Image captions', 'polylang-supertext'),
      'name' => 'to_post_image',
      'default' => true
    );

    // Let developers add their own translatable items
    $result = apply_filters('translation_fields_for_post', $result, $postId);

    return $result;
  }

  /**
   * @param int $postId the post to be translated
   * @return array list of translatable fields
   */
  public static function getTranslatableCustomFields($postId)
  {
    $result = array();

    $fields = Core::getInstance()->getLibrary()->getCustomFieldDefinitions($postId);

    // Create the field list to generate checkboxes
    foreach ($fields as $field) {
      $result[] = array(
        'title' => $field['label'],
        'name' => 'to_' . $field['id'],
        'default' => true
      );
    }

    // Let developers add their own translatable custom fields
    $result = apply_filters('translation_custom_fields_for_post', $result, $postId);

    return $result;
  }

  /**
   * Generate checkboxes for the user to select translation fields
   * @param array $fields that are translateable
   * @return string html code for checkboxes
   */
  protected function getCheckboxes($fields)
  {
    $return = '';
    // Go trough all possible fields
    $i = 0;
    foreach ($fields as $tick) {
      $i++;
      $checkName = $tick['name'];

      if ($i == 1) {
        $return .= '<tr>';
      }

      $checked = '';
      if ($tick['default'] == true) {
        $checked = ' checked';
      }

      $return .= '
        <td>
          <input type="checkbox" class="chkTranslationOptions" name="' . $checkName . '" id="' . $checkName . '" value="1"' . $checked . '>
        </td>
        <td style="padding-right:10px;">
          <label for="' . $checkName . '">' . $tick['title'] . '</label>
        </td>
      ';

      if ($i == 4) {
        $return .= '</tr>';
        $i = 0;
      }
    }

    // Crazy hadorn code.
    if ($i !== 0) {
      for ($index = $i; $index <= 4; $index++) {
        $return .= '<td></td><td></td>';
      }
    }

    // If nothing, give a message
    if (!is_array($fields) || count($fields) == 0) {
      $return .= '<tr><td>' . __('There is no content to be translated.', 'polylang-supertext') . '</td></tr>';
    }

    // Return the table with checkboxes
    return '
      <table border="0" cellpadding="2" cellspace="0">
        ' . $return . '
      </table>
    ';
  }

  /**
   * @return string error message or informational text
   */
  protected function getTranslationApiHtml()
  {
    // search translation feature and use first result
    if (!Core::getInstance()->getLibrary()->isWorking()) {
      return __('The Supertext plugin hasn\'t been configured correctly.', 'polylang-supertext');
    }

    $title = __('Your Supertext translation order', 'polylang-supertext');

    // Return info about supertext
    return '
    <div id="div_translation_title">
        <span><img src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/logo_supertext.png" width="48" height="48" alt="Supertext" title="Supertext" /></span>
        <span><h2>'.$title.'</h2></span>
        <div class="clear"></div>
    </div>';
  }

}