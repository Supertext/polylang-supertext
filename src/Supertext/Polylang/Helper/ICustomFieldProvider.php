<?php

namespace Supertext\Polylang\Helper;

/**
 * The ACF Custom Field provider
 * @package Supertext\Polylang\Helper
 */
interface ICustomFieldProvider
{
  /**
   * @return string the plugin name
   */
  public function getPluginName();

  /**
   * Gets the Custom Field Definitions as multidimensional array.
   *
   * @return array multidimensional list of custom fields defintions, keys:
   * <pre>
   *     id: (string) an id to identify custom field definition
   *     label: (string) a label
   *     type: (string) 'group' or 'field'
   *     meta_key_regex: (string) regex to match custom field key (usually db meta_key is sufficient)
   *     sub_field_definitions: (array) child field definitions
   * </pre>
   */
  public function getCustomFieldDefinitions();
}