<?php

namespace Supertext\Polylang\Helper;

/**
 * The ACF Custom Field provider
 * @package Supertext\Polylang\Helper
 */
class AcfCustomFieldProvider implements ICustomFieldProvider
{
  const PLUGIN_NAME = 'Advanced Custom Fields';

  public function getPluginName()
  {
    return self::PLUGIN_NAME;
  }

  /**
   * @return array multidimensional list of custom fields definitions
   */
  public function getCustomFieldDefinitions()
  {
    $fieldGroups = function_exists( 'acf_get_field_groups' ) ? acf_get_field_groups() : apply_filters( 'acf/get_field_groups', array() );
    $customFields = array();

    foreach ($fieldGroups as $fieldGroup) {
      $fieldGroupId = isset($fieldGroup['ID']) ? $fieldGroup['ID'] : $fieldGroup['id'];
      $fields = function_exists( 'acf_get_fields' ) ? acf_get_fields($fieldGroup) : apply_filters('acf/field_group/get_fields', array(), $fieldGroupId);

      $customFields[] = array(
        'id' => 'group_'.$fieldGroupId,
        'label' => $fieldGroup['title'],
        'type' => 'group',
        'sub_field_definitions' => $this->getFieldDefinitions($fields)
      );
    }

    return $customFields;
  }

  /**
   * @param array $fields the acf fields to process
   * @param string $metaKeyPrefix a prefix for creating meta keys
   * @return array
   */
  private function getFieldDefinitions($fields, $metaKeyPrefix = '')
  {
    $group = array();

    foreach ($fields as $field) {
      $metaKey = $metaKeyPrefix . $field['name'];
      $fieldId = isset($field['ID']) ? $field['ID'] : $field['id'];

      $group[] = array(
        'id' => 'field_'.$fieldId,
        'label' => $field['label'],
        'type' => 'field',
        'meta_key_regex' => $metaKey,
        'sub_field_definitions' => isset($field['sub_fields']) ? $this->getFieldDefinitions($field['sub_fields'], $metaKey . '_\\d+_') : array()
      );
    }

    return $group;
  }
}