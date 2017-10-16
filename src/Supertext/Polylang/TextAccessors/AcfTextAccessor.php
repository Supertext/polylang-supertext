<?php

namespace Supertext\Polylang\TextAccessors;

/**
 * Class AcfTextAccessor
 * @package Supertext\Polylang\TextAccessors
 */
class AcfTextAccessor extends AbstractPluginCustomFieldsTextAccessor implements IMetaDataAware
{
  const META_KEY_DELIMITER = '_\\d+_';

  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName(){
    return __('Advanced Custom Fields (Plugin)', 'polylang-supertext');
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getContentMetaData($post, $selectedTranslatableFields)
  {
    $metaData = array();

    $postCustomFields = get_post_meta($post->ID);

    $subParentMetaKeys = $this->getSubParentMetaKeys(array_keys($selectedTranslatableFields));

    foreach($postCustomFields as $metaKey => $value){
      if(isset($metaData[$metaKey])){
        continue;
      }

      foreach($subParentMetaKeys as $subParentMetaKey){
        if (!preg_match('/^' . $subParentMetaKey . '$/', $metaKey)) {
          continue;
        }

        $metaData[$metaKey] = $value[0];
      }
    }

    return $metaData;
  }

  /**
   * @param $post
   * @param $translationMetaData
   */
  public function setContentMetaData($post, $translationMetaData)
  {
    foreach($translationMetaData as $key => $value){
      update_post_meta($post->ID, $key, $value);
    }
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
   * @param $metaKeyRegex
   * @return array
   */
  private function getSubParentMetaKeys($selectedMetaKeyRegexs)
  {
    $subParentMetaKeys = array();

    foreach($selectedMetaKeyRegexs as $selectedMetaKeyRegex){
      $subMetaKeyParts = explode(self::META_KEY_DELIMITER, $selectedMetaKeyRegex);

      for($i = 0; $i < count($subMetaKeyParts)-1; ++$i){
        if($i > 0){
          $subParentMetaKeys[] = $subMetaKeyParts[$i-1] . self::META_KEY_DELIMITER . $subMetaKeyParts[$i];
          continue;
        }

        $subParentMetaKeys[] = $subMetaKeyParts[$i];
      }
    }

    return $subParentMetaKeys;
  }

  /**
   * @param $fields
   * @param string $metaKeyPrefix
   * @return array
   */
  private function getSubFieldDefinitions($fields, $metaKeyPrefix = '')
  {
    $group = array();

    foreach ($fields as $field) {
      $metaKey = $metaKeyPrefix . $field['name'];
      $fieldId = isset($field['ID']) ? $field['ID'] : $field['id'];
      $subFieldDefinitions = array();

      if($field['type'] === "flexible_content"){
        $subFieldDefinitions = $this->getFlexibleContentFieldDefinitions($field['layouts'], $metaKey, $fieldId);
      } elseif(isset($field['sub_fields'])) {
        $subFieldDefinitions = $this->getSubFieldDefinitions($field['sub_fields'], $metaKey . self::META_KEY_DELIMITER);
      }

      $group[] = array(
        'id' => 'field_'.$fieldId,
        'label' => $field['label'],
        'type' => 'field',
        'meta_key_regex' => $metaKey,
        'sub_field_definitions' => $subFieldDefinitions
      );
    }

    return $group;
  }

  private function getFlexibleContentFieldDefinitions($layouts, $metaKey, $flexibleContentFieldId)
  {
    $subFieldDefinitions = array();

    $layoutId = 0;
    foreach ($layouts as $layout) {
      $layoutSubFieldDefinitions = $this->getSubFieldDefinitions($layout['sub_fields'], $metaKey . self::META_KEY_DELIMITER);

      $subFieldDefinitions[] = array(
        'id' => 'layout_' . $flexibleContentFieldId . '_' . $layoutId,
        'label' => $layout['label'],
        'type' => 'field',
        'sub_field_definitions' => $layoutSubFieldDefinitions
      );

      ++$layoutId;
    }

    return $subFieldDefinitions;
  }
}