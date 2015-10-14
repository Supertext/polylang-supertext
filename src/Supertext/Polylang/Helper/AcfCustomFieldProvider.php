<?php

namespace Supertext\Polylang\Helper;

/**
 * The ACF Custom Field provider
 * @package Supertext\Polylang\Helper
 */
class AcfCustomFieldProvider implements ICustomFieldProvider
{
  const PLUGIN_NAME = 'Advanced Custom Fields';

  public function getPluginName(){
    return self::PLUGIN_NAME;
  }

  /**
   * @return array multidimensional list of custom fields definitions
   */
  public function getCustomFieldDefinitions()
  {
    $fieldGroups = acf_get_field_groups();
    $customFields = array();

    foreach ($fieldGroups as $fieldGroup) {
      $fields = acf_get_fields($fieldGroup);

      $customFields[] = array(
        'id' => $fieldGroup['key'],
        'label' => $fieldGroup['title'],
        'type' => 'group',
        'field_definitions' => $this->getFieldDefinitions($fields)
      );
    }

    return $customFields;
  }

  /**
   * @param array $fields the acf fields to process
   * @param string $metaKeyPrefix a prefix for creating meta keys
   * @return array
   */
  private function getFieldDefinitions($fields, $metaKeyPrefix = ''){
    $group = array();

    foreach ($fields as $field) {
      $metaKey = $metaKeyPrefix.$field['name'];

      $group[] = array(
        'id' => $field['key'],
        'label' => $field['label'],
        'type' => 'field',
        'meta_key' => $metaKey,
        'field_definitions' => isset($field['sub_fields']) ? $this->getFieldDefinitions($field['sub_fields'], $metaKey.'_\\d+_') : array()
      );
    }

    return $group;
  }
}