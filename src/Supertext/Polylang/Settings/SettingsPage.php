<?php

namespace Supertext\Polylang\Settings;

use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Helper\ISettingsAware;
use Supertext\Polylang\Helper\Constant;

/**
 * The supertext / polylang main settings page
 * @package Supertext\Polylang\Settings
 * @author Michael Sebel <michael@comotive.ch>
 */
class SettingsPage extends AbstractPage
{
  const USERS_TAB = 'users';
  const TRANSLATABLE_FIELDS_TAB = 'translatablefields';
  const SHORTCODES_TAB = 'shortcodes';
  const WORKFLOW_TAB = 'workflow';

  private $contentAccessors;
  private $tabs = array();

  public function __construct($library, $contentAccessors)
  {
    parent::__construct($library);

    $this->contentAccessors = $contentAccessors;

    // Tabs definitions
    $this->tabs = array();

    // User and language settings tab
    $this->tabs[self::USERS_TAB] = array(
      'name' => __('User and languages', 'polylang-supertext'),
      'viewBundles' => array(
        array('view' => 'backend/settings-users', 'context' => $library),
        array('view' => 'backend/settings-languages', 'context' => $library)
      ),
      'saveFunction' => 'saveUserAndLanguageSettings'
    );

    // Translatable fields settings tab
    $this->tabs[self::TRANSLATABLE_FIELDS_TAB] = array(
      'name' => __('Translatable fields', 'polylang-supertext'),
      'viewBundles' => $this->getContentProviderSettingsViewBundles(),
      'saveFunction' => 'saveTranslatableFieldsSettings'
    );

    // Shortcode settings tab
    $this->tabs[self::SHORTCODES_TAB] = array(
      'name' => __('Shortcodes', 'polylang-supertext'),
      'viewBundles' => array(
        array('view' => 'backend/settings-shortcodes', 'context' => $library)
      ),
      'saveFunction' => 'saveShortcodesSettings'
    );

    // Workflow settings tab
    $this->tabs[self::WORKFLOW_TAB] = array(
      'name' => __('Workflow', 'polylang-supertext'),
      'viewBundles' => array(
        array('view' => 'backend/settings-workflow', 'context' => $library)
      ),
      'saveFunction' => 'saveWorkflowSettings'
    );
  }

  /**
   * Displays the main settings page
   */
  public function display()
  {
    $currentTabId = $this->getCurrentTabId();

    // Display the page with typical entry infos
    echo '
      <div class="wrap">
        <h2>' . __('Settings › Supertext', 'polylang-supertext') . '</h2>
        ' . $this->showSystemMessage() . '
        ' . $this->addTabs($currentTabId);

    if ($currentTabId != null) {
      $this->addViews($currentTabId);
    }

    echo '</div>';
  }

  /**
   * Calls the tabs save function
   */
  public function control()
  {
    $currentTabId = $this->getCurrentTabId();

    if ($currentTabId == null || !isset($_POST['saveStPlSettings'])) {
      return;
    }

    $saveFunction = $this->tabs[$currentTabId]['saveFunction'];
    $this->$saveFunction();

    // Redirect to same page with message
    wp_redirect($this->getPageUrl($currentTabId) . '&message=saved');
  }

  private function getContentProviderSettingsViewBundles()
  {
    $viewBundle = array();

    foreach($this->contentAccessors as $contentAccessor)
    {
      if($contentAccessor instanceof ISettingsAware){
        $viewBundle[] = $contentAccessor->getSettingsViewBundle();
      }
    }

    return $viewBundle;
  }

  /**
   * @return string system message, if given, otherwise void
   */
  private function showSystemMessage()
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
  private function addTabs($currentTabId)
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
   * @param $currentTabId the current tab id
   */
  private function addViews($currentTabId)
  {
    echo '
        <form id="' . $currentTabId . 'SettingsForm" method="post" action="' . $this->getPageUrl($currentTabId) . '">';

    // Include the views
    foreach ($this->tabs[$currentTabId]['viewBundles'] as $viewBundle) {
      $this->includeView($viewBundle['view'], $viewBundle['context']);
    }

    echo '
        <p><input type="submit" class="button button-primary" name="saveStPlSettings" value="' . __('Save settings', 'polylang-supertext') . '" /></p>
      </form>';
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
  private function getCurrentTabId()
  {
    //Return default tab if none set
    if (empty($_GET['tab'])) {
      return self::USERS_TAB;
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
  private function saveUserAndLanguageSettings()
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

  private function saveTranslatableFieldsSettings()
  {
    foreach($this->contentAccessors as $contentAccessor)
    {
      if($contentAccessor instanceof ISettingsAware){
        $contentAccessor->saveSettings($_POST);
      }
    }
  }

  /**
   * Saves shortcode settings to options
   */
  private function saveShortcodesSettings()
  {
    $shortcodeSettingsToSave = array();

    foreach ($_POST['shortcodes'] as $name => $shortcode) {
      $settings = array(
        'content_encoding' => null,
        'attributes' => array()
      );

      if(isset($shortcode['attributes'])){
        $settings['attributes'] = $this->removeEmptyFields($shortcode['attributes']);
      }

      if(isset($shortcode['content_encoding'])){
        $settings['content_encoding'] = $shortcode['content_encoding'];
      }

      if(count($settings['attributes']) == 0 && empty($settings['content_encoding'])){
        continue;
      }

      $shortcodeSettingsToSave[$name] = $settings;
    }

    $this->library->saveSetting(Constant::SETTING_SHORTCODES, $shortcodeSettingsToSave);
  }

  /**
   * Removes empty attribute settings
   */
  private function removeEmptyFields($attributes)
  {
    $cleanedAttributes = array();

    foreach ($attributes as $attribute) {
      if(!empty($attribute['name'])){
        $cleanedAttributes[] = $attribute;
      }
    }

    return $cleanedAttributes;
  }

  /**
   * Saves workflow settings to options
   */
  private function saveWorkflowSettings()
  {
    $settingsToSave = array(
      'publishOnCallback' => isset($_POST['publishOnCallback']) && !empty($_POST['publishOnCallback']),
      'overridePublishedPosts' => isset($_POST['overridePublishedPosts']) && !empty($_POST['overridePublishedPosts'])
    );

    $this->library->saveSetting(Constant::SETTING_WORKFLOW, $settingsToSave);
  }
} 