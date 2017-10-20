<?php

namespace Supertext\Polylang\Settings;

use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\Library;
use Supertext\Polylang\Helper\TranslationMeta;
use Supertext\Polylang\Helper\View;
use Supertext\Polylang\TextAccessors\IAddDefaultSettings;
use Supertext\Polylang\TextAccessors\ITextAccessor;
use Supertext\Polylang\TextAccessors\ISettingsAware;

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

  private $textAccessors;
  private $tabs = array();
  private $viewTemplates;

  /**
   * @param Library $library
   * @param ITextAccessor[] $textAccessors
   */
  public function __construct($library, $textAccessors)
  {
    parent::__construct($library);

    $this->textAccessors = $textAccessors;
    $this->tabs = array(
      self::USERS_TAB => array(),
      self::TRANSLATABLE_FIELDS_TAB => array(),
      self::SHORTCODES_TAB => array(),
      self::WORKFLOW_TAB => array(),
    );
    $this->viewTemplates = new View("templates/settings-templates");
  }

  public function initTabs(){
    // User and language settings tab
    $this->tabs[self::USERS_TAB] = array(
      'name' => __('User and languages', 'polylang-supertext'),
      'viewBundles' => array(
        array('view' => new View('backend/settings-users')),
        array('view' => new View('backend/settings-languages'))
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
        array('view' => new View('backend/settings-shortcodes'))
      ),
      'saveFunction' => 'saveShortcodesSettings'
    );

    // Workflow settings tab
    $this->tabs[self::WORKFLOW_TAB] = array(
      'name' => __('Workflow and API', 'polylang-supertext'),
      'viewBundles' => array(
        array('view' => new View('backend/settings-workflow')),
        array('view' => new View('backend/settings-api'))
      ),
      'saveFunction' => 'saveWorkflowAndApiSettings'
    );
  }

  /**
   * Displays the main settings page
   */
  public function display()
  {
    $this->initTabs();

    $currentTabId = $this->getCurrentTabId();

    // Display the page with typical entry infos
    echo '
      <div class="wrap">
        <h1>' . __('Settings › Supertext', 'polylang-supertext') . '</h1>
        ' . $this->showSystemMessage() . '
        ' . $this->addTabs($currentTabId);

    if ($currentTabId != null) {
      $this->addViews($currentTabId);
    }

    echo '</div>';

    $this->viewTemplates->render();
  }

  /**
   * Calls the tabs save function
   */
  public function control()
  {
    $this->runHiddenFunctions();

    $currentTabId = $this->getCurrentTabId();

    if ($currentTabId == null || !isset($_POST['saveStPlSettings'])) {
      return;
    }

    $this->initTabs();

    $saveFunction = $this->tabs[$currentTabId]['saveFunction'];
    $this->$saveFunction();

    // Redirect to same page with message
    wp_redirect($this->getPageUrl($currentTabId) . '&message=saved');
  }

  /**
   * Sets default settings
   */
  public function addDefaultSettings()
  {
    foreach($this->textAccessors as $textAccessor){
      if($textAccessor instanceof IAddDefaultSettings){
        $textAccessor->addDefaultSettings();
      }
    }
  }

  /**
   * @return array
   */
  private function getContentProviderSettingsViewBundles()
  {
    $viewBundle = array();

    foreach($this->textAccessors as $textAccessor)
    {
      if($textAccessor instanceof ISettingsAware){
        $viewBundle[] = $textAccessor->getSettingsViewBundle();
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
   * @param string $page page name
   * @param string $currentTabId id of current tab
   * @return string all tabs as links
   */
  private function addTabs($currentTabId)
  {
    $html = '<div class="nav-tab-wrapper">';

    foreach ($this->tabs as $tabId => $tab) {
      $class = ($tabId == $currentTabId) ? 'nav-tab-active' : '';
      $html .= '<a class="nav-tab ' . $class . '" href="' . $this->getPageUrl($tabId) . '">' . $tab['name'] . '</a>';
    }

    $addDefaultSettingsUrl = get_admin_url(null, 'options-general.php?page=supertext-polylang-settings&addDefaultSettings=on');
    $html .= '<a class="button button-highlighted button-tab-nav" href="'.$addDefaultSettingsUrl.'">Add default settings</a><div class="clearfix"></div>';

    $html .= '</div>';

    return $html;
  }

  /**
   * @param int $currentTabId the current tab id
   */
  private function addViews($currentTabId)
  {
    echo '
        <form id="' . $currentTabId . 'SettingsForm" method="post" action="' . $this->getPageUrl($currentTabId) . '">';

    // Include the views
    foreach ($this->tabs[$currentTabId]['viewBundles'] as $viewBundle) {
      $context = isset($viewBundle['context']) ? $viewBundle['context'] : array('library' => $this->library);
      $viewBundle['view']->render($context);
    }

    echo '
        <p><input type="submit" class="button button-primary" name="saveStPlSettings" value="' . __('Save settings', 'polylang-supertext') . '" /></p>
      </form>';
  }

  /**
   * @param int $tabId the tab id
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

    $languageMap = array();
    foreach (Multilang::getLanguages() as $language) {
      if(empty($_POST['sel_st_language_'.$language->slug])){
        continue;
      }

      $languageMap[$language->slug] = $_POST['sel_st_language_'.$language->slug];
    }

    // Put into the options
    $this->library->saveSettingOption(Constant::SETTING_USER_MAP, $userMap);
    $this->library->saveSettingOption(Constant::SETTING_LANGUAGE_MAP, $languageMap);
  }

  private function saveTranslatableFieldsSettings()
  {
    foreach($this->textAccessors as $textAccessor)
    {
      if($textAccessor instanceof ISettingsAware){
        $textAccessor->saveSettings($_POST);
      }
    }
  }

  /**
   * Saves shortcode settings to options
   */
  private function saveShortcodesSettings()
  {
    $shortcodeSettingsToSave = array();

    foreach ($_POST['shortcodes'] as $shortcode) {
      if(empty($shortcode['name'])){
        continue;
      }

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

      $name = stripslashes($shortcode['name']);

      if(!isset($shortcodeSettingsToSave[$name])){
        $shortcodeSettingsToSave[$name] = $settings;
      }else{
        $shortcodeSettingsToSave[$name] =  array_merge_recursive($shortcodeSettingsToSave[$name], $settings);
      }
    }

    $this->library->saveSettingOption(Constant::SETTING_SHORTCODES, $shortcodeSettingsToSave);
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
  private function saveWorkflowAndApiSettings()
  {
    $workflowSettingsToSave = array(
      'publishOnCallback' => !empty($_POST['publishOnCallback']),
      'overridePublishedPosts' => !empty($_POST['overridePublishedPosts']),
      'syncTranslationChanges' => !empty($_POST['syncTranslationChanges'])
    );

    $apiSettingsToSave = array(
      'apiServerUrl' => !empty($_POST['apiServerUrl']) ? $_POST['apiServerUrl'] : Constant::LIVE_API,
      'serviceType' => !empty($_POST['serviceType']) ? $_POST['serviceType'] : Constant::DEFAULT_SERVICE_TYPE,
    );

    $this->library->saveSettingOption(Constant::SETTING_WORKFLOW, $workflowSettingsToSave);
    $this->library->saveSettingOption(Constant::SETTING_API, $apiSettingsToSave);
  }

  /**
   * Hidden helper functions
   */
  private function runHiddenFunctions()
  {
    if(!empty($_GET['setInTranslationFlagFalse'])){
      TranslationMeta::of($_GET['setInTranslationFlagFalse'])->set(TranslationMeta::IN_TRANSLATION, false);
    }

    if(!empty($_GET['addDefaultSettings'])){
      $this->addDefaultSettings();
    }
  }
} 