<?php

namespace Supertext\Polylang\Helper;

use Comotive\Util\ArrayManipulation;

/**
 * Class AcfContentAccessor
 * @package Supertext\Polylang\Helper
 */
class AcfContentAccessor implements IContentAccessor, ISettingsAware
{
  /**
   * @var TextProcessor text processor
   */
  private $textProcessor;

  /**
   * @var Library library
   */
  private $library;

  /**
   * @param $textProcessor
   * @param $library
   */
  public function __construct($textProcessor, $library)
  {
    $this->textProcessor = $textProcessor;
    $this->library = $library;
  }

  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName(){
    return __('Advanced Custom Field (Plugin)', 'polylang-supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $postCustomFields = get_post_meta($postId);
    $options = $this->library->getSettingOption();
    $savedAcfFieldDefinitions = isset($options[Constant::SETTING_ACF_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_ACF_FIELDS]) : array();

    $translatableFields = array();

    $meta_keys = array_keys($postCustomFields);

    foreach ($savedAcfFieldDefinitions as $savedAcfFieldDefinition) {
      if (count(preg_grep('/^' . $savedAcfFieldDefinition['meta_key_regex'] . '$/', $meta_keys)) > 0) {
        $translatableFields[] = array(
          'title' => $savedAcfFieldDefinition['label'],
          'name' => $savedAcfFieldDefinition['meta_key_regex'],
          'checkedPerDefault' => true
        );
      }
    }

    return $translatableFields;
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    $postCustomFields = get_post_meta($post->ID);

    foreach($postCustomFields as $meta_key => $value){
      foreach($selectedTranslatableFields as $meta_key_regex => $selected){
        if (!preg_match('/^' . $meta_key_regex . '$/', $meta_key)) {
          continue;
        }

        $texts[$meta_key] = $this->textProcessor->replaceShortcodes($value[0]);
      }
    }

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    foreach ($texts as $id => $text) {
      $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
      $decodedContent = $this->textProcessor->replaceShortcodeNodes($decodedContent);
      update_post_meta($post->ID, $id, $decodedContent);
    }
  }

  /**
   * @return array
   */
  public function getSettingsViewBundle()
  {
    $options = $this->library->getSettingOption();
    $savedAcfFieldDefinitions = isset($options[Constant::SETTING_ACF_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_ACF_FIELDS]) : array();

    $savedAcfFieldIds = array();
    foreach($savedAcfFieldDefinitions as $savedAcfFieldDefinition){
      $savedAcfFieldIds[] = $savedAcfFieldDefinition['id'];
    }

    return array(
      'view' => 'backend/settings-acf',
      'context' => array(
        'acfFieldDefinitions' => $this->getAcfFieldDefinitions(),
        'savedAcfFieldIds' => $savedAcfFieldIds
      )
    );
  }

  /**
   * @param $postData
   */
  public function saveSettings($postData)
  {
    $checkedAcfFieldIds = explode(',', $postData['acf']['checkedAcfFields']);
    $acfFieldDefinitionsToSave = array();

    $fieldDefinitions = $this->getAcfFieldDefinitions();

    while (($field = array_shift($fieldDefinitions))) {
      if (count($field['sub_field_definitions']) > 0) {
        $fieldDefinitions = array_merge($fieldDefinitions, $field['sub_field_definitions']);
        continue;
      }

      if (in_array($field['id'], $checkedAcfFieldIds) && isset($field['meta_key_regex'])) {
        $fieldToSave = $field;
        unset($fieldToSave['sub_field_definitions']);
        $acfFieldDefinitionsToSave[] = $fieldToSave;
      }
    }

    $this->library->saveSetting(Constant::SETTING_ACF_FIELDS, $acfFieldDefinitionsToSave);
  }

  /**
   * @return array
   */
  private function getAcfFieldDefinitions()
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
        'sub_field_definitions' => $this->getFieldDefinitions($fields)
      );
    }

    return $acfFieldDefinition;
  }

  /**
   * @param $fields
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