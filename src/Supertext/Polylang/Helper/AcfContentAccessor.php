<?php

namespace Supertext\Polylang\Helper;

/**
 * Class AcfContentAccessor
 * @package Supertext\Polylang\Helper
 */
class AcfContentAccessor extends AbstractPluginCustomFieldsContentAccessor
{
  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName(){
    return __('Advanced Custom Fields (Plugin)', 'polylang-supertext');
  }

  /**
   * @return array
   */
  protected function getFieldDefinitions()
  {
    $fieldGroups = function_exists( 'acf_get_field_groups' ) ? acf_get_field_groups() : apply_filters( 'acf/get_field_groups', array() );
    $acfFieldDefinition = array();

    foreach ($fieldGroups as $fieldGroup) {
      $fieldGroupId = isset($fieldGroup['ID']) ? $fieldGroup['ID'] : $fieldGroup['id'];
      $fields = function_exists( 'acf_get_fields' ) ? acf_get_fields($fieldGroup) : apply_filters('acf/field_group/get_fields', array(), $fieldGroupId);

      $acfFieldDefinition[] = array(
        'id' => 'group_'.$fieldGroupId,
        'label' => $fieldGroup['title'],
        'type' => 'group',
        'sub_field_definitions' => $this->getSubFieldDefinitions($fields)
      );
    }

    return $acfFieldDefinition;
  }

  /**
   * @param $fields
   * @return array
   */
  private function getSubFieldDefinitions($fields, $metaKeyPrefix = '')
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
        'sub_field_definitions' => isset($field['sub_fields']) ? $this->getSubFieldDefinitions($field['sub_fields'], $metaKey . '_\\d+_') : array()
      );
    }

    return $group;
  }
}