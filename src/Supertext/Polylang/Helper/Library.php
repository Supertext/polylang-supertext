<?php

namespace Supertext\Polylang\Helper;

use Supertext\Polylang\Api\ApiClient;
use Supertext\Polylang\Api\PolylangApiWrapper;
use Supertext\Polylang\Api\WPMLApiWrapper;

/**
 * Class Library
 * @package Supertext\Polylang\Helper
 */
class Library
{
  private $pluginStatus = null;
  private $multilang = null;

  /**
   * Make multilang API available through the library.
   * @return \Supertext\Polylang\Api\IMultilang
   */
  public function getMultilang()
  {
    if ($this->multilang == null) {
      $this->multilang = $this->isWPMLActivated() ? new WPMLApiWrapper() : new PolylangApiWrapper();
    }

    return $this->multilang;
  }

  /**
   * @param string $languageCode polylang language code
   * @return string equivalent supertext language code
   */
  public function toSuperCode($languageCode)
  {
    $languageMappings = $this->getSettingOption(Constant::SETTING_LANGUAGE_MAP);
    foreach ($languageMappings as $polyKey => $superKey) {
      if ($languageCode == $polyKey) {
        return $superKey;
      }
    }

    return null;
  }

  /**
   * @param string $languageCode Supertext language code
   * @return string equivalent polylang language code
   */
  public function toPolyCode($languageCode)
  {
    $languageMappings = $this->getSettingOption(Constant::SETTING_LANGUAGE_MAP);
    foreach ($languageMappings as $polyKey => $superKey) {
      if ($languageCode == $superKey) {
        return $polyKey;
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

    if (!isset($subSetting)) {
      return $options;
    }

    return isset($options[$subSetting]) ? $options[$subSetting] : array();
  }

  /**
   * @param string $subSetting key
   * @param array|mixed $value saved value
   */
  public function saveSettingOption($subSetting, $value)
  {
    $options = $this->getSettingOption();
    $options[$subSetting] = $value;
    update_option(Constant::SETTINGS_OPTION, $options);
  }

  /**
   * Deletes the setting option
   */
  public function deleteSettingOption()
  {
    delete_option(Constant::SETTINGS_OPTION);
  }

  /**
   * @param string $plugin plugin file path
   * @return bool true if plugin is active
   */
  public function isPluginActive($plugin)
  {
    return in_array($plugin, (array) get_option('active_plugins', array())) || (is_multisite() && isset(get_site_option('active_sitewide_plugins')[$plugin]));
  }

  /**
   * @return bool
   */
  public function isPolylangActivated()
  {
    return $this->isPluginActive('polylang/polylang.php') || $this->isPluginActive('polylang-pro/polylang.php');
  }

  /**
   * @return bool
   */
  public function isWPMLActivated()
  {
    return $this->isPluginActive('sitepress-multilingual-cms/sitepress.php');
  }

  /**
   * @return bool
   */
  public function isMultilangActivated()
  {
    return $this->isPolylangActivated() || $this->isWPMLActivated();
  }

  /**
   * @return bool
   */
  public function isCurlActivated()
  {
    return function_exists('curl_exec');
  }

  /**
   * @return bool
   */
  public function isPluginConfiguredProperly()
  {
    $options = $this->getSettingOption();

    return
      isset($options[Constant::SETTING_USER_MAP]) &&
      count($options[Constant::SETTING_USER_MAP]) > 0 &&
      isset($options[Constant::SETTING_LANGUAGE_MAP]) &&
      count($options[Constant::SETTING_LANGUAGE_MAP]) > 0;
  }

  /**
   * @return bool
   */
  public function isCurrentUserConfigured()
  {
    $userId = get_current_user_id();
    $cred = $this->getUserCredentials($userId);

    return
      strlen($cred['stUser']) > 0 &&
      strlen($cred['stApi']) > 0 &&
      $cred['stUser'] != Constant::DEFAULT_API_USER;
  }

  /**
   * @return array configured languages
   */
  public function getConfiguredLanguages()
  {
    $languages = $this->getMultilang()->getLanguages();
    $languageMappings = $this->getSettingOption(Constant::SETTING_LANGUAGE_MAP);
    $languageMappingCodes = array_keys($languageMappings);
    $configuredLanguages = array();

    foreach ($languages as $language) {
      if (!in_array($language->slug, $languageMappingCodes)) {
        continue;
      }

      array_push($configuredLanguages, $language);
    }

    return $configuredLanguages;
  }

  /**
   * Gets the plugin status
   * @return null|\stdClass
   */
  public function getPluginStatus()
  {
    if ($this->pluginStatus == null) {
      $this->pluginStatus =  new \stdClass();
      $this->pluginStatus->isMultilangActivated = $this->isMultilangActivated();
      $this->pluginStatus->isWPMLActivated = $this->isWPMLActivated();
      $this->pluginStatus->isCurlActivated = $this->isCurlActivated();
      $this->pluginStatus->isPluginConfiguredProperly = $this->isPluginConfiguredProperly();
      $this->pluginStatus->isCurrentUserConfigured = $this->isCurrentUserConfigured();
    };

    return $this->pluginStatus;
  }

  /**
   * Get an API connection as an authenticated user
   * @param int $userId
   * @return ApiClient the api connection of the user
   */
  public function getApiClient($userId = 0)
  {
    // Get currently logged in user, if no user given
    if ($userId == 0) {
      $userId = get_current_user_id();
    }

    // Try to find credentials
    $userId = intval($userId);
    $credentials = $this->getUserCredentials($userId);
    $apiSettings = $this->getSettingOption(Constant::SETTING_API);
    $apiServerUrl = !empty($apiSettings['apiServerUrl']) ? $apiSettings['apiServerUrl'] : Constant::LIVE_API;
    $local = explode('_', get_locale());
    $communicationLanguage = $local[0] . '-' . $local[1];

    // Get the ready to call instance
    return ApiClient::getInstance(
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

    /**
   * @param $content HTML content
   * @return \DOMDocument
   */
  public function createHtmlDocument($content)
  {
    $html = '<?xml version="1.0" encoding="utf-8"?>
    <html>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        </head>
        <body>' . $content . '</body>
    </html>
    ';

    $doc = new \DOMDocument();
    $doc->loadHTML($html);
    return $doc;
  }

}
