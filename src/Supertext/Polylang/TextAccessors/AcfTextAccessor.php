<?php

namespace Supertext\Polylang\TextAccessors;

use Supertext\Polylang\Helper\Constant;

/**
 * Class AcfTextAccessor
 * @package Supertext\Polylang\TextAccessors
 */
class AcfTextAccessor extends AbstractPluginCustomFieldsTextAccessor implements IMetaDataAware
{
  const META_KEY_DELIMITER = '_(\\d+_)?';

  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName()
  {
    return __('Advanced Custom Fields (Plugin)', 'polylang-supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = parent::getTranslatableFields($postId);

    return array_merge(array(array(
      'title' => 'Copy none-translatable',
      'name' => 'none-translatable',
      'checkedPerDefault' => false
    )), $translatableFields);
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
    $metaKeys = array_keys($postCustomFields);

    $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);

    if (isset($selectedTranslatableFields['none-translatable']) && isset($savedFieldDefinitions[$this->pluginId])) {
      foreach ($metaKeys as $metaKey) {

        $isTranslatable = false;
        foreach ($savedFieldDefinitions[$this->pluginId] as $savedFieldDefinition) {
          if (preg_match('/^' . $savedFieldDefinition['meta_key_regex'] . '$/', $metaKey)) {
            $isTranslatable = true;
            break;
          }
        }

        if (!$isTranslatable) {
          $metaData[$metaKey] = get_post_meta($post->ID, $metaKey, true);
        }
      }
    }

    $subParentMetaKeys = $this->getSubParentMetaKeys(array_keys($selectedTranslatableFields));

    foreach ($metaKeys as $metaKey) {
      if (isset($metaData[$metaKey])) {
        continue;
      }

      foreach ($subParentMetaKeys as $subParentMetaKey) {
        if (!preg_match('/^' . $subParentMetaKey . '$/', $metaKey)) {
          continue;
        }

        $metaData[$metaKey] = get_post_meta($post->ID, $metaKey, true);
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
    foreach ($translationMetaData as $key => $value) {
      update_post_meta($post->ID, $key, $value);
    }
  }

  /**
   * @return array
   */
  protected function getFieldDefinitions()
  {
    $fieldGroups = function_exists('acf_get_field_groups') ? acf_get_field_groups() : apply_filters('acf/get_field_groups', array());
    $acfFieldDefinition = array();

    foreach ($fieldGroups as $fieldGroup) {
      $fieldGroupId = $fieldGroup['key'];
      $fields = function_exists('acf_get_fields') ? acf_get_fields($fieldGroup) : apply_filters('acf/field_group/get_fields', array(), $fieldGroupId);

      $acfFieldDefinition[] = array(
        'id' => $fieldGroupId,
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

    foreach ($selectedMetaKeyRegexs as $selectedMetaKeyRegex) {
      $subMetaKeyParts = explode(self::META_KEY_DELIMITER, $selectedMetaKeyRegex);

      for ($i = 0; $i < count($subMetaKeyParts) - 1; ++$i) {
        if ($i > 0) {
          $subParentMetaKeys[] = $subMetaKeyParts[$i - 1] . self::META_KEY_DELIMITER . $subMetaKeyParts[$i];
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
      $fieldId = $field['key'];

      $newElement = array(
        'id' => $fieldId,
        'label' => $field['label']
      );

      if ($field['type'] === "flexible_content") {
        $newElement['type'] = 'group';
        $newElement['sub_field_definitions'] = $this->getFlexibleContentFieldDefinitions($field['layouts'], $metaKey, $fieldId);
      } elseif (isset($field['sub_fields'])) {
        $newElement['type'] = 'group';
        $newElement['sub_field_definitions'] = $this->getSubFieldDefinitions($field['sub_fields'], $metaKey . self::META_KEY_DELIMITER);
      } else {
        $newElement['type'] = 'field';
        $newElement['meta_key_regex'] = $metaKey;
      }

      $group[] = $newElement;
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
        'id' => $flexibleContentFieldId . '_layout_' . $layoutId,
        'label' => $layout['label'],
        'type' => 'group',
        'sub_field_definitions' => $layoutSubFieldDefinitions
      );

      ++$layoutId;
    }

    return $subFieldDefinitions;
  }
}
