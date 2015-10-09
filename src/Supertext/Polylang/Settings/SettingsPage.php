<?php

namespace Supertext\Polylang\Settings;

use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Helper\Constant;

/**
 * The supertext / polylang main settings page
 * @package Supertext\Polylang\Settings
 * @author Michael Sebel <michael@comotive.ch>
 */
class SettingsPage extends AbstractPage
{
  private $tabs = array();

  public function __construct()
  {
    parent::__construct();

    //Tabs definitions
    $this->tabs = array(
      'first' => array(
        'name' => __("User and languages", 'polylang-supertext'),
        'views' => array(
          'backend/settings-users',
          'backend/settings-languages'
        ),
        'saveFunction' => 'saveUserAndLanguageSettings'
      ),
      'second' => array(
        'name' => __("Custom Fields", 'polylang-supertext'),
        'views' => array(
          'backend/settings-custom-fields'
        ),
        'saveFunction' => 'saveCustomFieldsSettings'
      )
    );
  }

  /**
   * Displays the main settings page
   */
  public function display()
  {
    $currentTabId = $this->GetCurrentTabId();

    if ($currentTabId === null) {
      return;
    }

    // Display the page with typical entry infos
    echo '
      <div class="wrap">
        <h2>' . __('Settings › Supertext API', 'polylang-supertext') . '</h2>
        ' . $this->addResources() . '
        ' . $this->showSystemMessage() . '
        ' . $this->addTabs($currentTabId) . '
        <form method="post" action="' . $this->getPageUrl($currentTabId) . '">
    ';

    // Include the views
    foreach ($this->tabs[$currentTabId]['views'] as $view) {
      $this->includeView($view, $this);
    }

    // Close the form
    echo '
        <p><input type="submit" class="button button-primary" name="saveStPlSettings" value="' . __('Save settings', 'polylang-supertext') . '" /></p>
      </form>
    </div>
    ';
  }

  /**
   * Calls the tabs save function
   */
  public function control()
  {
    $currentTabId = $this->GetCurrentTabId();

    if ($currentTabId === null || !isset($_POST['saveStPlSettings'])) {
      return;
    }

    $saveFunction = $this->tabs[$currentTabId]['saveFunction'];
    $this->$saveFunction();

    // Redirect to same page with message
    wp_redirect($this->getPageUrl($currentTabId) . '&message=saved');
  }

  /**
   * Add js/css resources needed on this page
   * I know this is crap, will be fixed in near future
   */
  protected function addResources()
  {
    return '
      <link rel="stylesheet" href="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/style.css?v=' . SUPERTEXT_PLUGIN_REVISION . '" />
      <script type="text/javascript" src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/settings-library.js?v=' . SUPERTEXT_PLUGIN_REVISION . '"></script>
    ';
  }

  /**
   * @return string system message, if given, otherwise void
   */
  protected function showSystemMessage()
  {
    if (!isset($_REQUEST['message']) || $_REQUEST['message'] !== 'saved') {
      return '';
    }

    return '
        <div id="message" class="updated fade">
          <p>' . __('Settings saved', 'polylang-supertext') . '</p>
        </div>
      ';
  }

  /**
   * @param $page page name
   * @param string $currentTabId id of current tab
   * @return string all tabs as links
   */
  protected function addTabs($currentTabId)
  {
    $html = '<h2 class="nav-tab-wrapper">';

    foreach ($this->tabs as $tabId => $tab) {
      $class = ($tabId == $currentTabId) ? 'nav-tab-active' : '';
      $html .= '<a class="nav-tab ' . $class . '" href="' . $this->getPageUrl($tabId) . '">' . $tab['name'] . '</a>';
    }

    $html .= '</h2>';

    return $html;
  }

  /**
   * @param $tabId the tab id
   * @return string the page url with tab
   */
  private function getPageUrl($tabId)
  {
    return get_admin_url() . 'options-general.php?page=' . $_GET['page'] . '&tab=' . $tabId;
  }

  /**
   * @return string|void
   */
  public function GetCurrentTabId()
  {
    //Return default tab if none set
    if (empty($_GET['tab'])) {
      return 'first';
    }

    $tabId = esc_attr($_GET['tab']);

    //Not existing tab
    if (!isset($this->tabs[$tabId])) {
      return null;
    }

    return $tabId;
  }

  /**
   * Saves user and language settings to options
   */
  protected function saveUserAndLanguageSettings()
  {
    // Saving the user mappings
    $userMap = array();
    foreach ($_POST['selStWpUsers'] as $key => $id) {
      if (intval($id) > 0) {
        $userMap[] = array(
          'wpUser' => intval($_POST['selStWpUsers'][$key]),
          'stUser' => $_POST['fieldStUser'][$key],
          'stApi' => $_POST['fieldStApi'][$key]
        );
      }
    }

    // Crappily create the language array
    $languageMap = array();
    foreach ($_POST as $postField => $stLanguage) {
      if (substr($postField, 0, strlen('sel_st_language_')) == 'sel_st_language_') {
        $language = substr($postField, strlen('sel_st_language_'));
        $languageMap[$language] = $stLanguage;
      }
    }

    // Put into the options
    $this->library->saveSetting(Constant::SETTING_USER_MAP, $userMap);
    $this->library->saveSetting(Constant::SETTING_LANGUAGE_MAP, $languageMap);

    // Set the plugin to working mode, if both arrays are saved
    if (count($userMap) > 0 && count($languageMap) == count(Multilang::getLanguages())) {
      $this->library->saveSetting(Constant::SETTING_WORKING, 1);
    } else {
      $this->library->saveSetting(Constant::SETTING_WORKING, 0);
    }
  }

  /**
   * Saves the custom field settings to options
   */
  protected function saveCustomFieldsSettings()
  {

  }
} 