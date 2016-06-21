<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Api\Multilang;
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

  private $library;
  private $contentProvider;

  /**
   * Gather various meta information for the upcoming offer
   */
  public function __construct($library, $contentProvider)
  {
    $this->library = $library;
    $this->contentProvider = $contentProvider;
    $this->postId = $_GET['postId'];
    $this->post = get_post($this->postId);
    $this->sourceLang = Multilang::getPostLanguage($this->post->ID);
    $this->targetLang = $_GET['targetLang'];
    $this->hasExistingTranslation = intval(Multilang::getPostInLanguage($this->postId, $this->targetLang)) > 0;

    wp_enqueue_style(Constant::POST_STYLE_HANDLE);
    wp_enqueue_script(Constant::TRANSLATION_SCRIPT_HANDLE);
  }

  /**
   * Display the offerbox html
   */
  public function displayOfferBox()
  {
    $offerBoxTitle = $this->getTitle();

    $errors = $this->getErrors();

    $warnings = $this->getWarnings();

    $orderForm = $this->getOrderForm();

    // Print the actual form
    echo '
      <script type="text/javascript">
        jQuery(function() {
          Supertext.Polylang.addOfferEvents();
        });
      </script>
      <div id="div_tb_wrap_translation" class="div_tb_wrap_translation">
        <div id="div_translation_order_head">
          ' . $offerBoxTitle . '
          ' . $errors . '
          ' . $warnings . '
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
          ' . $orderForm . '
        </div>
        <div id="div_close_translation_order_window" class="div_translation_order_buttons" style="display:none">
              <input type="button" id="btn_close_translation_order_window" class="button" value="' . __('Close window', 'polylang-supertext') . '" />
        </div>
      </div>
    ';
  }

  /**
   * @param string $key slug to search
   * @return string name of the $key language
   */
  protected function getLanguageName($key)
  {
    // Get the supertext key
    $stKey = $this->library->mapLanguage($key);
    return __($stKey, 'polylang-supertext-langs');
  }

  /**
   * Generate checkboxes for the user to select translation fields
   * @param array $translatableFields that are translateable
   * @return string html code for checkboxes
   */
  protected function getCheckboxes($allTranslatableFields)
  {
    $return = '';

    foreach ($allTranslatableFields as $sourceId => $translatableFields) {
      $fields = $translatableFields['fields'];
      if(count($fields) === 0){
        continue;
      }

      $return .= '<table class="translatableContentTable" border="0" cellpadding="2" cellspace="0">';
      $return .= '<thead><tr><th colspan="8">'.$translatableFields['source_name'].'</th></tr></thead>';
      $return .= '<tbody>';
      foreach ($fields as $index => $field) {
        $checkName = $sourceId.$field['name'];
        $checked = $field['default'] ? ' checked': '';
        $reminder = $index % 4;
        $rowStart = $reminder === 0 ? '<tr>' : '';
        $rowEnd = $reminder === 3 ? '</tr>' : '';

        $return .= $rowStart;
        $return .= '
          <td>
            <input type="checkbox" class="chkTranslationOptions" name="translatable_fields['.$sourceId.'][' . $field['name'] . ']" id="' . $checkName . '" ' . $checked . '>
          </td>
          <td style="padding-right:10px;">
            <label for="' . $checkName . '">' . $field['title'] . '</label>
          </td>
        ';
        $return .= $rowEnd;
      }
      $return .= '</tbody></table>';
    }

    // If nothing, give a message
    if (!is_array($allTranslatableFields) || count($allTranslatableFields) == 0) {
      $return .= '<tr><td>' . __('There is no content to be translated.', 'polylang-supertext') . '</td></tr>';
    }

    // Return the table with checkboxes
    return $return;
  }

  private function getTitle()
  {
    $title = __('Your Supertext translation order', 'polylang-supertext');

    // Return info about supertext
    return '
    <div id="div_translation_title">
        <span><img src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/logo_supertext.png" width="48" height="48" alt="Supertext" title="Supertext" /></span>
        <span><h2>' . $title . '</h2></span>
        <div class="clear"></div>
    </div>';
  }

  /**
   * @return string html error message
   */
  private function getErrors()
  {
    $messages = '';

    // search translation feature and use first result
    if (!$this->library->isWorking()) {
      $messages .= '
        <div id="error_missing_function" class="notice notice-error">
          <p>
            ' . __('The Supertext plugin hasn\'t been configured correctly.', 'polylang-supertext') . '
          </p>
        </div>
      ';
    }

    if (!function_exists('curl_exec')) {
      $messages .= '
        <div id="error_missing_function" class="notice notice-error">
          <p>
            ' . __('The PHP function <em>curl_exec</em> is disabled. Please enable it in order to be able to send requests to Supertext.', 'polylang-supertext') . '
          </p>
        </div>
      ';
    }

    return $messages;
  }

  /**
   * @return string html warning message
   */
  private function getWarnings()
  {
    $messages = '';

    // Inform the user over a draft that might not be finished
    if ($this->post->post_status == 'draft') {
      $messages .= '
        <div id="warning_draft_state" class="notice notice-warning">
          <p>
            ' . __('The articles status is <b>draft</b>.<br>Are you sure you want to order a translation for this article?', 'polylang-supertext') . '
          </p>
        </div>
      ';
    }

    // If there is an existing translation
    if ($this->hasExistingTranslation) {
      $messages .= '
        <div id="warning_already_translated" class="notice notice-warning">
          <p>
            ' . __('There is already a translation for this post. The quote below may be higher than the final price if only parts of the content need to be translated again.', 'polylang-supertext') . '
          </p>
        </div>
      ';
    }

    return $messages;
  }

  private function getOrderForm()
  {
    $subTitle = sprintf(__('Translation of post %s', 'polylang-supertext'), $this->post->post_title);
    $translationDetails = sprintf(
      __('The article will be translated from <b>%s</b> into <b>%s</b>.', 'polylang-supertext'),
      $this->getLanguageName($this->sourceLang),
      $this->getLanguageName($this->targetLang)
    );

    $customFieldSettingsUrl = get_admin_url(null, 'options-general.php?page=supertext-polylang-settings&tab=translatablefields');
    $allTranslatableFields = $this->contentProvider->getAllTranslatableFields($this->postId);

    return '<form
            name="frm_Translation_Options"
            id="frm_Translation_Options"
            method="post">
            <h3>' . $subTitle . '</h3>
            ' . $translationDetails . '
            <input type="hidden" name="post_id" value="' . intval($this->postId) . '">
            <input type="hidden" name="source_lang" id="source_lang" value="' . $this->sourceLang . '">
            <input type="hidden" name="target_lang" id="target_lang" value="' . $this->targetLang . '">

            <h3>' . __('Content to be translated', 'polylang-supertext') . '</h3>
            <p>' . sprintf(wp_kses(__('Translatable custom fields can be defined in the <a target="_parent" href="%s">settings</a>.', 'polylang-supertext'), array('a' => array('href' => array(), 'target' => array()))), esc_url($customFieldSettingsUrl)) . '</p>
            ' . $this->getCheckboxes($allTranslatableFields) . '

            <h3>' . __('Service and deadline', 'polylang-supertext') . '</h3>
            <p>' . __('Select the translation service and deadline:', 'polylang-supertext') . '</p>

            <div class="div_translation_price_loading" id="div_translation_price_loading" style="height:200px;">
              ' . __('The price is being calculated, one moment please.', 'polylang-supertext') . '
              <img src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/loader.gif" title="' . __('Loading', 'polylang-supertext') . '">
            </div>
            <div id="div_translation_price" style="display:none;"></div>

            <h3>' . __('Your comment to Supertext', 'polylang-supertext') . '</h3>
            <p><textarea name="txt_comment" id="txt_comment"></textarea></p>

            <div class="div_translation_order_buttons">
              <input type="submit" name="btn_order" id="btn_order" value="' . __('Order translation', 'polylang-supertext') . '" class="button" />
            </div>
          </form>';
  }
}