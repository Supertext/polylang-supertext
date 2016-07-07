<?php

namespace Supertext\Polylang\Backend;

use Comotive\Helper\Metabox;
use Comotive\Util\Date;
use Supertext\Polylang\Helper\Constant;

/**
 * Serves as a helper for the translation inject to the user
 * @package Supertext\Polylang\Backend
 * @author Michael Sebel <michael@comotive.ch>
 */
class AdminExtension
{
  /**
   * @var string the translation column id
   */
  const TRANSLATION_STATUS_COLUMN = 'translation-status';

  /**
   * @var \Supertext\Polylang\Helper\Library
   */
  private $library;

  /**
   * @var Log
   */
  private $log;

  /**
   * @var null|string
   */
  private $screenBase = null;

  /**
   * Various filters to change and/or display things
   */
  public function __construct($library, $log)
  {
    $this->library = $library;
    $this->log = $log;

    add_action('current_screen', array($this, 'setScreenBase'));
    add_action('admin_enqueue_scripts', array($this, 'addBackendAssets'));
    add_action('admin_notices', array($this, 'showInTranslationMessage'));
    add_action('admin_footer', array($this, 'printWorkingState'));
    add_action('media_upload_gallery', array($this, 'disableGalleryInputs'));
    add_action('add_meta_boxes', array($this, 'addLogInfoMetabox'));

    add_filter('manage_posts_columns', array($this, 'addTranslationStatusColumn'), 100);
    add_action('manage_posts_custom_column', array($this, 'displayTranslationStatusColumn'), 12, 2);
    add_filter('manage_pages_columns', array($this, 'addTranslationStatusColumn'), 100);
    add_action('manage_pages_custom_column', array($this, 'displayTranslationStatusColumn'), 12, 2);
  }

  /**
   * @param \WP_Screen $screen the screen shown
   */
  public function setScreenBase($screen)
  {
    $this->screenBase = $screen->base;
  }

  /**
   * Add the global backend libraries and css
   */
  public function addBackendAssets()
  {
    //Settings assets
    if ($this->screenBase == 'settings_page_supertext-polylang-settings') {
      wp_enqueue_style(Constant::SETTINGS_STYLE_HANDLE);
      wp_enqueue_style(Constant::JSTREE_STYLE_HANDLE);

      wp_enqueue_script(Constant::SETTINGS_SCRIPT_HANDLE);
      wp_enqueue_script(Constant::JSTREE_SCRIPT_HANDLE);
      wp_enqueue_script(Constant::JQUERY_UI_AUTOCOMPLETE);
    }

    if ($this->screenBase == 'post' || $this->screenBase == 'edit') {
      wp_enqueue_style(Constant::ADMIN_EXTENSION_STYLE_HANDLE);

      wp_enqueue_script(Constant::ADMIN_EXTENSION_SCRIPT_HANDLE);
    }
  }

  /**
   * Show information about the article translation, if given
   */
  public function showInTranslationMessage()
  {
    if ($this->screenBase != 'post' && isset($_GET['post'])) {
      return;
    }

    $translationPost = get_post(intval($_GET['post']));
    $orderId = $this->getOrderId($translationPost, true);

    // Show info if there is an order and the article is not translated yet
    if (intval($orderId) > 0 && get_post_meta($translationPost->ID, Constant::IN_TRANSLATION_FLAG, true) == 1) {
      echo '
        <div class="updated">
          <p>' . sprintf(__('The article was sent to Supertext and is now being translated. Your order number is %s.', 'polylang-supertext'), intval($orderId)) . '</p>
        </div>
      ';
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
   * Print a working state hidden field
   */
  public function printWorkingState()
  {
    $working = 1;

    // See if the plugin is generally working
    if (!$this->library->isWorking()) {
      $working = 0;
    }

    // See if the user has credentials
    $userId = get_current_user_id();
    $cred = $this->library->getUserCredentials($userId);

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
    if (isset($_GET['post'])) {
      $postId = intval($_GET['post']);
      $logEntries = $this->log->getLogEntries($postId);

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
  }

  /**
   * Sets the translation status column cell
   * @param $column
   * @param $postId
   */
  public function displayTranslationStatusColumn($column, $postId)
  {
    if ($column != self::TRANSLATION_STATUS_COLUMN) {
      return;
    }

    if (get_post_meta($postId, Constant::IN_TRANSLATION_FLAG, true) == 1) {
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
      if ($key == 'comments') {
        $newColumns[self::TRANSLATION_STATUS_COLUMN] = '<span class="dashicons dashicons-translation" width="20px"></span>';
      }

      $newColumns[$key] = $column;
    }

    if (!isset($newColumns[self::TRANSLATION_STATUS_COLUMN])) {
      $newColumns[self::TRANSLATION_STATUS_COLUMN] = '<span class="dashicons dashicons-translation" width="20px"></span>';
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