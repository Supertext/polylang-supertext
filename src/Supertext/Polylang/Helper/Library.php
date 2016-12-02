<?php

namespace Supertext\Polylang\Helper;

use Comotive\Util\WordPress;
use Supertext\Polylang\Api\ApiConnection;
use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Api\Wrapper;

/**
 * Class Library
 * @package Supertext\Polylang\Helper
 */
class Library
{
  private $pluginStatus = null;

  /**
   * @param string $language polylang language code
   * @return string equivalent supertext language code
   */
  public function mapLanguage($language)
  {
    $languageMappings = $this->getSettingOption(Constant::SETTING_LANGUAGE_MAP);
    foreach ($languageMappings as $polyKey => $stKey) {
      if ($language == $polyKey) {
        return $stKey;
      }
    }

    return null;
  }

  /**
   * @param int $userId wordpress user
   * @return array user configuration for supertext api calls
   */
  public function getUserCredentials($userId)
  {
    $userMappings = $this->getSettingOption(Constant::SETTING_USER_MAP);

    foreach ($userMappings as $config) {
      if ($config['wpUser'] == $userId) {
        return $config;
      }
    }

    return array(
      'wpUser' => $userId,
      'stUser' => Constant::DEFAULT_API_USER,
      'stApi' => ''
    );
  }

  /**
   * @param $subSetting
   * @return array full settings array
   */
  public function getSettingOption($subSetting = null)
  {
    $options = get_option(Constant::SETTINGS_OPTION, array());

    if(!isset($subSetting)){
      return $options;
    }

    return isset($options[$subSetting]) ? $options[$subSetting] : array();
  }

  /**
   * @param string $subSetting key
   * @param array|mixed $value saved value
   */
  public function saveSetting($subSetting, $value)
  {
    $options = $this->getSettingOption();
    $options[$subSetting] = $value;
    update_option(Constant::SETTINGS_OPTION, $options);
  }

  /**
   * Deletes the setting option
   */
  public function deleteSettingOption(){
    delete_option(Constant::SETTINGS_OPTION);
  }

  /**
   * @return bool
   */
  public function isPolylangActivated()
  {
    return WordPress::isPluginActive('polylang/polylang.php') || WordPress::isPluginActive('polylang-pro/polylang.php');
  }

  /**
   * @return bool
   */
  public function isCurlActivated(){
    return function_exists('curl_exec');
  }

  /**
   * @return bool
   */
  public function isPluginConfiguredProperly(){
    $options = $this->getSettingOption();

    return
      isset($options[Constant::SETTING_USER_MAP]) &&
      count($options[Constant::SETTING_USER_MAP]) > 0 &&
      isset($options[Constant::SETTING_LANGUAGE_MAP]) &&
      count($options[Constant::SETTING_LANGUAGE_MAP]) == count(Multilang::getLanguages());
  }

  /**
   * @return bool
   */
  public function isCurrentUserConfigured(){
    $userId = get_current_user_id();
    $cred = $this->getUserCredentials($userId);

    return
      strlen($cred['stUser']) > 0 &&
      strlen($cred['stApi']) > 0 &&
      $cred['stUser'] != Constant::DEFAULT_API_USER;
  }

  /**
   * Gets the plugin status
   * @return null|\stdClass
   */
  public function getPluginStatus(){
    if($this->pluginStatus == null){
      $this->pluginStatus =  new \stdClass();
      $this->pluginStatus->isPolylangActivated = $this->isPolylangActivated();
      $this->pluginStatus->isCurlActivated = $this->isCurlActivated();
      $this->pluginStatus->isPluginConfiguredProperly = $this->isPluginConfiguredProperly();
      $this->pluginStatus->isCurrentUserConfigured = $this->isCurrentUserConfigured();
    };

    return $this->pluginStatus;
  }

  /**
   * Get an API connection as an authenticated user
   * @param int $userId
   * @return ApiConnection the api connection of the user
   */
  public function getApiConnection($userId = 0)
  {
    // Get currently logged in user, if no user given
    if ($userId == 0) {
      $userId = get_current_user_id();
    }

    // Try to find credentials
    $userId = intval($userId);
    $credentials = $this->getUserCredentials($userId);
    $workflowSetting = $this->getSettingOption(Constant::SETTING_WORKFLOW);
    $apiServerUrl = !empty($workflowSetting['apiServerUrl']) ? $workflowSetting['apiServerUrl'] : Constant::LIVE_API;
    $local = explode('_', get_locale());
    $communicationLanguage = $local[0].'-'.$local[1];

    // Get the ready to call instance
    return ApiConnection::getInstance(
      $apiServerUrl,
      $credentials['stUser'],
      $credentials['stApi'],
      $communicationLanguage
    );
  }

  /**
   * @return array
   */
  public function getShortcodeTags()
  {
    global $shortcode_tags;
    return $shortcode_tags;
  }
}