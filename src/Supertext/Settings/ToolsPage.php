<?php

namespace Supertext\Settings;

use Supertext\Helper\Library;
use Supertext\Helper\View;

/**
 * The supertext tools page
 * @package Supertext\Settings
 */
class ToolsPage extends AbstractPage
{

  private $writeBackCallback;
  private $view;

  /**
   * @param Library $library
   * @param array $writeBackCallback
   */
  public function __construct($library, $writeBackCallback)
  {
    parent::__construct($library);

    $this->writeBackCallback = $writeBackCallback;
    $this->view = new View('backend/tools-manual-writeback');
  }

  /**
   * Displays the main tools page
   */
  public function display()
  {
    echo '
      <div class="wrap">
        <h1>' . __('Tools › Supertext', 'supertext') . '</h1>
        <form method="post" action="' . $this->getPageUrl() . '">';

    echo $this->showSystemMessage();

    $this->view->render();

    echo '
        <p><input type="submit" class="button button-primary" name="writeback" value="' . __('Write back', 'supertext') . '" /></p>
        </form>
      </div>';
  }

  /**
   * Calls the tabs save function
   */
  public function control()
  {
    if (!isset($_POST['writeback']) || empty($_POST['writebackData'])) {
      return;
    }

    $json = json_decode(stripslashes($_POST['writebackData']));

    if ($json === null) {
      return;
    }

    try {
      call_user_func($this->writeBackCallback, $json);

      // Redirect to same page with message
      wp_redirect($this->getPageUrl() . '&message=saved');
    } catch (\Exception $e) {
      wp_redirect($this->getPageUrl() . '&message=failed');
    }
  }

  /**
   * @return string system message, if given, otherwise void
   */
  private function showSystemMessage()
  {
    if (!isset($_REQUEST['message'])) {
      return '';
    }

    if ($_REQUEST['message'] === 'saved') {
      return '
        <div id="message" class="updated fade">
          <p>' . __('Writeback successful', 'supertext') . '</p>
        </div>
      ';
    }

    if ($_REQUEST['message'] === 'failed') {
      return '
        <div id="message" class="error fade">
          <p>' . __('Writeback failed', 'supertext') . '</p>
        </div>
      ';
    }
  }

  /**
   * @param int $tabId the tab id
   * @return string the page url with tab
   */
  private function getPageUrl()
  {
    return get_admin_url() . 'tools.php?page=supertext-tools';
  }
}
