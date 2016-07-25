<?php

namespace Supertext\Polylang\Helper;

/**
 * This class wrapps all constant configurations
 * @package Supertext\Polylang\Helper
 * @author Michael Sebel <michael@comotive.ch>
 */
class Constant
{
  /**
   * @var string the used API base url
   */
  const API_URL = self::DEV_API;
  /**
   * @var string development api endpoints
   */
  const DEV_API = 'https://192.168.164.1/Supertext/api/v1/';
  /**
   * @var string live api endpoints
   */
  const LIVE_API = 'https://www.supertext.ch/api/v1/';
  /**
   * @var string the settings option
   */
  const SETTINGS_OPTION = 'polylang_supertext_settings';
  /**
   * @var string the version option
   */
  const VERSION_OPTION = 'polylang_supertext_version';
  /**
   * @var string the version option
   */
  const REFERENCE_OPTION = 'polylang_supertext_reference';
  /**
   * @var string name of the subsetting for user mapping
   */
  const SETTING_USER_MAP = 'userMap';
  /**
   * @var string name of the subsetting for language mapping
   */
  const SETTING_LANGUAGE_MAP = 'languageMap';
  /**
   * $var string name of the subsetting for custom field definitions
   */
  const SETTING_CUSTOM_FIELDS = 'customFields';
  /**
   * $var string name of the subsetting for acf field definitions
   */
  const SETTING_PCF_FIELDS = 'pcfFields';
  /**
   * $var string name of the subsetting for acf field definitions
   */
  const SETTING_ACF_FIELDS = 'acfFields';
  /**
   * $var string name of the subsetting for shortcodes
   */
  const SETTING_SHORTCODES = 'shortcodes';
  /**
   * $var string name of the subsetting for workflow settings
   */
  const SETTING_WORKFLOW = 'workflow';
  /**
   * @var string the default supertext api user
   */
  const DEFAULT_API_USER = 'public_user';
  /**
   * @var string the style handle
   */
  const SETTINGS_STYLE_HANDLE = 'polylang-supertext-styles';
  /**
   * @var string the post style handle
   */
  const ADMIN_EXTENSION_STYLE_HANDLE = 'polylang-supertext-admin-extension-styles';
  /**
   * @var string the jstree style handle
   */
  const JSTREE_STYLE_HANDLE = 'polylang-supertext-jstree-styles';
  /**
   * @var string the translation script handle
   */
  const ADMIN_EXTENSION_SCRIPT_HANDLE = 'polylang-supertext-admin-extension-library';
  /**
   * @var string the settings script handle
   */
  const SETTINGS_SCRIPT_HANDLE = 'polylang-supertext-settings-scripts';
  /**
   * @var string the jstree script handle
   */
  const JSTREE_SCRIPT_HANDLE = 'polylang-supertext-jstree-scripts';
  /**
   * @var string the jquery ui complete handle
   */
  const JQUERY_UI_AUTOCOMPLETE = 'jquery-ui-autocomplete';
  /**
   * @var string the text that marks a post as "in translation"
   */
  const IN_TRANSLATION_TEXT = '[in Translation...]';
  /**
   * @var string the flag that sets a post in translation
   */
  const IN_TRANSLATION_FLAG = '_in_st_translation';
  /**
   * $var string the translation reference hash
   */
  const IN_TRANSLATION_REFERENCE_HASH = '_in_translation_ref_hash';
  /**
   * @var reference bitmask. If changed all translations jobs are invalidated.
   */
  const REFERENCE_BITMASK = '9682059641ba9a50a1c15abf4e23e26327139f570783c04900af023ac9569ecb';
}
