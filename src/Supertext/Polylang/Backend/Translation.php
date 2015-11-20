<?php

namespace Supertext\Polylang\Backend;

use Comotive\Helper\Metabox;
use Comotive\Util\Date;
use Supertext\Polylang\Core;
use Supertext\Polylang\Helper\Constant;

/**
 * Serves as a helper for the translation inject to the user
 * @package Supertext\Polylang\Backend
 * @author Michael Sebel <michael@comotive.ch>
 */
class Translation
{
  /**
   * @var string the translation column id
   */
  const TRANSLATION_STATUS_COLUMN = 'translation-status';
  /**
   * @var string the text that marks a post as "in translation"
   */
  const IN_TRANSLATION_TEXT = '[in Translation...]';
  /**
   * @var string the flag that sets a post in translation
   */
  const IN_TRANSLATION_FLAG = '_in_st_translation';
  /**
   * Various filters to change and/or display things
   */
  public function __construct()
  {

    add_action('admin_init', array($this, 'addBackendAssets'));
    add_action('admin_notices', array($this, 'showInTranslationMessage'));
    add_action('current_screen', array($this, 'addScreenbasedAssets'));
    add_action('admin_footer', array($this, 'addTranslations'));
    add_action('admin_footer', array($this, 'printWorkingState'));
    add_action('media_upload_gallery', array($this, 'disableGalleryInputs'));
    add_action('add_meta_boxes', array($this, 'addLogInfoMetabox'));

    add_filter('manage_posts_columns', array($this, 'addTranslationStatusColumn'), 100);
    add_action('manage_posts_custom_column', array($this, 'displayTranslationStatusColumn'), 12, 2);
    add_filter('manage_pages_columns', array($this, 'addTranslationStatusColumn'), 100);
    add_action('manage_pages_custom_column', array($this, 'displayTranslationStatusColumn'), 12, 2);

    // Load translations
    load_plugin_textdomain('polylang-supertext', false, 'polylang-supertext/resources/languages');
    load_plugin_textdomain('polylang-supertext-langs', false, 'polylang-supertext/resources/languages');
  }

  /**
   * Show information about the article translation, if given
   */
  public function showInTranslationMessage()
  {
    if (isset($_GET['post']) && isset($_GET['action'])) {
      $translationPost = get_post(intval($_GET['post']));
      $orderId = $this->getOrderId($translationPost, true);

      // Show info if there is an order and the article is not translated yet
      if (intval($orderId) > 0 && get_post_meta($translationPost->ID, Translation::IN_TRANSLATION_FLAG, true) == 1) {
        echo '
          <div class="updated">
            <p>' .  sprintf(__('The article was sent to Supertext and is now being translated. Your order number is %s.', 'polylang-supertext'), intval($orderId)) . '</p>
          </div>
        ';
      }
    }
  }

  /**
   * @param \WP_Post $translationPost the translated post
   * @return int $orderId
   */
  public function getOrderId($translationPost)
  {
    $orderIdList = get_post_meta($translationPost->ID, Log::META_ORDER_ID, true);
    $orderId = is_array($orderIdList) ? end($orderIdList) : 0;

    return $orderId;
  }

  /**
   * Only includes resources for post translation management
   * @param \WP_Screen $screen the screen shown
   */
  public function addScreenbasedAssets($screen)
  {
    if ($screen->base == 'post' && ($_GET['action'] == 'edit')) {
      // SCripts to inject translation
      wp_enqueue_script(Constant::TRANSLATION_SCRIPT_HANDLE);

      // Styles for post backend and offer page
      wp_enqueue_style(Constant::POST_STYLE_HANDLE);
    }
  }

  /**
   * Add the global backend libraries and css
   */
  public function addBackendAssets()
  {
    wp_enqueue_style(Constant::STYLE_HANDLE);
    wp_enqueue_script(Constant::GLOBAL_SCRIPT_HANDLE);
  }

  /**
   * Add JS translations to the i18n Supertext object that has already been loaded now
   */
  public function addTranslations()
  {
    echo '
      <script type="text/javascript">
        Supertext.i18n = {
          resourceUrl : "' . get_bloginfo('wpurl') . '",
          addNewUser : "' . esc_js(__('Add user', 'polylang-supertext')) . '",
          inTranslationText : "' . esc_js(self::IN_TRANSLATION_TEXT) . '",
          deleteUser : "' . esc_js(__('Delete user', 'polylang-supertext')) . '",
          translationCreation : "' . esc_js(__('Translation is being initialized. Please wait a moment.', 'polylang-supertext')) . '",
          generalError : "' . esc_js(__('An error occurred.', 'polylang-supertext')) . '",
          offerTranslation : "' . esc_js(__('Order translation', 'polylang-supertext')) . '",
          translationOrderError : "' . esc_js(__('The order couldn\'t be sent to Supertext. Please try again.', 'polylang-supertext')) . '",
          confirmUnsavedArticle : "' . esc_js(__('The article was not saved. If you proceed with the translation, the unsaved changes will be lost.', 'polylang-supertext')) . '",
          alertUntranslatable : "' . esc_js(__('The article cannot be translated because there is an unfinished translation task. Please use the original article to order a translation.', 'polylang-supertext')) . '",
          offerConfirm_Price : "' . esc_js(__('You are ordering a translation with the deadline {deadline} and price {price}.', 'polylang-supertext')) . '",
          offerConfirm_Binding : "' . esc_js(__('This order for a translation is binding.', 'polylang-supertext')) . '",
          offerConfirm_EmailInfo : "' . esc_js(__('You will receive an email as soon as the translation of your article is complete.', 'polylang-supertext')) . '",
          offerConfirm_Confirm : "' . esc_js(__('Please confirm your order.', 'polylang-supertext')) . '"
        };
      </script>
    ';
  }

  /**
   * Print a working state hidden field
   */
  public function printWorkingState()
  {
    $working = 1;
    $library = Core::getInstance()->getLibrary();

    // See if the plugin is generally working
    if (!$library->isWorking()) {
      $working = 0;
    }

    // See if the user has credentials
    $userId = get_current_user_id();
    $cred = $library->getUserCredentials($userId);

    // Check credentials and api key
    if (strlen($cred['stUser']) == 0 || strlen($cred['stApi']) == 0 || $cred['stUser'] == Constant::DEFAULT_API_USER) {
      $working = 0;
    }

    // Print the field
    echo '<input type="hidden" id="supertextPolylangWorking" value="' . $working . '" />';
  }

  /**
   * Show supertext log information, if there are entries for the current post
   */
  public function addLogInfoMetabox()
  {
    $postId = intval($_GET['post']);
    $logEntries = Core::getInstance()->getLog()->getLogEntries($postId);

    // Show info if valid post and there are entries
    if ($postId > 0 && count($logEntries) > 0) {
      // Reverse entries, so that the newest is on top
      $logEntries = array_reverse($logEntries);
      // Create an html element to display the entries
      $html = '';
      foreach ($logEntries as $entry) {
        $datetime = '
          ' . Date::getTime(Date::EU_DATE, $entry['datetime']) . ',
          ' . Date::getTime(Date::EU_TIME, $entry['datetime']) . '
        ';
        $html .= '<p><strong>' . $datetime . '</strong>: ' . $entry['message'] . '</p>';
      }

      $helper = Metabox::get('post');
      // Add a new metabox to show log entries
      $helper->addMetabox(Log::META_LOG, __('Supertext Plugin Log', 'polylang-supertext'), 'side', 'low');
      $helper->addHtml('info', Log::META_LOG, $html);
    }
  }

  /**
   * Sets the translation status column cell
   * @param $column
   * @param $postId
   */
  public function displayTranslationStatusColumn($column, $postId) {
    if ($column != self::TRANSLATION_STATUS_COLUMN){
      return;
    }

    if(get_post_meta($postId, Translation::IN_TRANSLATION_FLAG, true) == 1){
      echo '<span class="dashicons dashicons-clock"></span>';
    }
  }

  /**
   * Adds a translation status column.
   * @param $columns
   * @return array
   */
  public function addTranslationStatusColumn($columns)
  {
    $newColumns = array();

    foreach ($columns as $key => $column) {
      if($key == 'comments'){
        $newColumns[self::TRANSLATION_STATUS_COLUMN] =  '<span class="dashicons dashicons-translation"></span>';
      }

      $newColumns[$key] = $column;
    }

    if(!isset($newColumns[self::TRANSLATION_STATUS_COLUMN])){
      $newColumns[self::TRANSLATION_STATUS_COLUMN] =  '<span class="dashicons dashicons-translation"></span>';
    }

    return $newColumns;
  }

  /**
   * Disable gallery inputs (only called if the media viewer is opened
   */
  public function disableGalleryInputs()
  {
    echo '
      <script type="text/javascript">
        Supertext.Polylang.disableGalleryInputs();
      </script>
    ';
  }
} 