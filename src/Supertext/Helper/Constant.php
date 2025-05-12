<?php

namespace Supertext\Helper;

/**
 * This class wrapps all constant configurations
 * @package Supertext\Helper
 * @author Michael Sebel <michael@comotive.ch>
 */
class Constant
{
  /**
   * @var string development api endpoints
   */
  const DEV_API = 'https://staging.supertext.com/api/';
  /**
   * @var string live api endpoints
   */
  const LIVE_API = 'https://www.supertext.com/api/'; 
  /**
   * @var int max system name length for the order referrer data
   */
  const MAX_SYSTEM_NAME_LENGTH = 50;
  /**
   * @var string live api endpoints
   */
  const DEFAULT_SERVICE_TYPE = 46;
  /**
   * @var string live api endpoints for proofread
   */
  const DEFAULT_SERVICE_TYPE_PR = 3;
  /**
   * @var string the settings option
   */
  const SETTINGS_OPTION = 'polylang_supertext_settings';
  /**
   * @var string the version option
   */
  const ENVIRONMENT_ADJUSTED_OPTION = 'polylang_supertext_environment_adjusted';
  /**
   * @var string the version option
   */
  const VERSION_OPTION = 'polylang_supertext_version';
  /**
   * @var string the version option
   */
  const REFERENCE_OPTION = 'supertext_reference';
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
   * $var string name of the subsetting for elementor text properties
   */
  const SETTING_ELEMENTOR_TEXT_PROPERTIES = 'elementorTextProperties';
  /**
   * $var string name of the subsetting for acf field definitions
   */
  const SETTING_PLUGIN_CUSTOM_FIELDS = 'pluginCustomFields';
  /**
   * $var string name of the subsetting for shortcodes
   */
  const SETTING_SHORTCODES = 'shortcodes';
  /**
   * $var string name of the subsetting for workflow settings
   */
  const SETTING_WORKFLOW = 'workflow';
  /**
   * $var string name of the subsetting for workflow settings
   */
  const SETTING_API = 'api';
  /**
   * @var string the default supertext api user
   */
  const DEFAULT_API_USER = 'public_user';
  /**
   * @var string the style handle
   */
  const SETTINGS_STYLE_HANDLE = 'supertext-styles';
  /**
   * @var string the post style handle
   */
  const ADMIN_EXTENSION_STYLE_HANDLE = 'supertext-admin-extension-styles';
  /**
   * @var string the jstree style handle
   */
  const JSTREE_STYLE_HANDLE = 'supertext-jstree-styles';
  /**
   * @var string the translation script handle
   */
  const ADMIN_EXTENSION_SCRIPT_HANDLE = 'supertext-admin-extension-library';
  /**
   * @var string the settings script handle
   */
  const SETTINGS_SCRIPT_HANDLE = 'supertext-settings-scripts';
  /**
   * @var string the jstree script handle
   */
  const JSTREE_SCRIPT_HANDLE = 'supertext-jstree-scripts';
  /**
   * @var string the block editor extension script handle
   */
  const BLOCK_EDITOR_SCRIPT_HANDLE = 'supertext-block-editor-scripts';
  /**
   * @var string the jquery ui complete handle
   */
  const JQUERY_UI_AUTOCOMPLETE = 'jquery-ui-autocomplete';
  /**
   * @var reference bitmask. If changed all translations jobs are invalidated.
   */
  const REFERENCE_BITMASK = '9682059641ba9a50a1c15abf4e23e26327139f570783c04900af023ac9569ecb';
  /**
   * @var string flag new post to be saved automatically.
   */
  const NEW_POST_AUTO_SAVE_FLAG = 'sttr_auto_save';
  /**
   * @var string default post status for translation posts.
   */
  const TRANSLATION_POST_STATUS = 'draft';
  /**
   * @var string tag filter for translatable block attributes.
   */
  const FILTER_TRANSLATABLE_BLOCK_ATTRIBUTES = 'sttr_translatable_block_attributes';
  /**
   * @var string tag filter for custom fields / post meta translation.
   */
  const FILTER_POST_META_TRANSLATION = 'sttr_post_meta_translation';
  /**
   * @var string tag filter for target texts.
   */
  const FILTER_WRITEBACK_TARGET_CONTENT = 'sttr_writeback_target_content';
  /**
   * @var string tag action when finishing writing back translation into target post.
   */
  const ACTION_FINISH_TARGET_POST_WRITEBACK = 'sttr_finish_target_post_writeback';
}
