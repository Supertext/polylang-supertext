<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Core;

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
        <div class="ui-widget" id="warning_not_review_state">
          <div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;">
            <p>
              ' . __('The articles state is not <b>review</b>.<br>Are you sure you want to order a translation for this article?', 'polylang-supertext') . '
            </p>
          </div>
        </div>
      ';
    }

    // Create the success url that will create a new post to be translated
    // This will trigger polylang to setup to post, "translation-service=1" triggers creation of article automatically
    $successUrl = 'post-new.php' .
      '?post_type=' . $this->post->post_type .
      '&source=' . $this->sourceLang .
      '&new_lang=' . $this->targetLang .
      '&from_post=' . $this->postId .
      '&translation-service=1';

    // Print the actual form
    echo '
      <link rel="stylesheet" href="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/post.css?v=' . SUPERTEXT_PLUGIN_REVISION . '" />
      <script type="text/javascript" src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/global-library.js?v=' . SUPERTEXT_PLUGIN_REVISION . '"></script>
      <script type="text/javascript" src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/translation-library.js?v=' . SUPERTEXT_PLUGIN_REVISION . '" /></script>
      <script type="text/javascript">
        jQuery(function() {
          Supertext.Polylang.translatedPostId = ' . intval($this->postId) . ';
          Supertext.Polylang.addOfferEvents();
        });
      </script>

      <div id="div_tb_wrap_translation" class="div_tb_wrap_translation">
        ' . $output . '
        <br>
        <div style="clear:both;">
          <div id="div_waiting_while_loading" style="display:none;">
            <i>
              ' . __('The order is being sent. One moment, please.', 'polylang-supertext') . '<br>
              ' . __('As soon as the order is placed, you will be automatically redirected.', 'polylang-supertext') . '
            </i>
            <img src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/loader.gif" title="' . __('Loading', 'polylang-supertext') . '">
          </div>
          <form name="frm_Translation_Options" id="frm_Translation_Options" method="post" data-post-id="' . $this->postId . '">
            <input type="hidden" id="successUrlMakeOrder" value="' . $successUrl . '" />
            <h3>' . __('Translation', 'polylang-supertext') . '</h3>
            ' . sprintf(
                  __('The article will be translated from <b>%s</b> to <b>%s</b>.', 'polylang-supertext'),
                  $this->getLanguageName($this->sourceLang),
                  $this->getLanguageName($this->targetLang)
                ) . '
            <input type="hidden" name="source_lang" id="source_lang" value="' . $this->sourceLang . '">
            <input type="hidden" name="target_lang" id="target_lang" value="' . $this->targetLang . '">
            <br><br>
            <h3>' . __('Contents to be translated', 'polylang-supertext') . '</h3>
            ' . $this->getCheckboxes() . '
            <br>
            <h3>' . __('Quality and deadline', 'polylang-supertext') . '</h3>
            ' . __('The following settings are available for the selected components of your article:', 'polylang-supertext') . '
            <br><br>
            <div class="div_translation_price_loading" id="div_translation_price_loading" style="height:200px;">
              ' . __('Prices are calculated, one moment please.', 'polylang-supertext') . '
              <img src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/loader.gif" title="' . __('Loading', 'polylang-supertext') . '">
            </div>
            <div id="div_translation_price" style="display:none;"></div>
            <br>
            <h3>' . __('Your comment to Supertext', 'polylang-supertext') . '</h3>
            <textarea name="txtComment" id="txtComment" style="width:100%;height:100px;"></textarea>

            <br><br>
            <span style="float:right;clear:both;">
              <input type="submit" name="btn_order" id="btn_order" value="' . __('Order translation', 'polylang-supertext') . '" class="button">
            </span>
          </form>
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
   * Generate checkboxes for the user to select translation fields
   * @return string html code for checkboxes
   */
  protected function getCheckboxes()
  {
    $return = '';
    // Go trough all possible fields
    $i = 0;
    foreach (self::getTranslatableFields($this->postId) as $tick) {
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

    // Return info about supertext
    return '
      <div>
        <span style="float:left;"><img src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/icon-st.png" alt="Supertext" title="Supertext">&nbsp;</span>
        <span style="float:left;"><h2>Supertext: ' . __('Order article translation', 'polylang-supertext') . '</h2></span>
      </div>
      <br><br><br>
    ';
  }

}