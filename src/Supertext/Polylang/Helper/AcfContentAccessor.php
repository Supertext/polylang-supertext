<?php

namespace Supertext\Polylang\Helper;

use Comotive\Util\ArrayManipulation;

/**
 * Class AcfContentAccessor
 * @package Supertext\Polylang\Helper
 */
class AcfContentAccessor implements IContentAccessor, ISettingsAware
{
  const KEY_SEPARATOR = '__';

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
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $options = $this->library->getSettingOption();
    $savedAcfFields = isset($options[Constant::SETTING_ACF_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_ACF_FIELDS]) : array();
    $fields = get_field_objects($postId, true, false);

    $translatableFields = array();

    while(($field = array_shift($fields))){
      if(isset($field['sub_fields'])){
        $fields = array_merge($fields, $field['sub_fields']);
        continue;
      }

      if(!in_array($field['name'], $savedAcfFields)){
        continue;
      }

      $translatableFields[] = array(
        'title' => $field['label'],
        'name' => $field['name'],
        'default' => true
      );
    }

    return array(
      'sourceName' => __('Advanced Custom Field (Plugin)', 'polylang-supertext'),
      'fields' => $translatableFields
    );
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();
    $fields = get_fields($post->ID);
    $ids = array_keys($selectedTranslatableFields);

    $texts = $this->getFieldTexts($fields, '', $texts, $ids);

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    $fields = get_fields($post->ID);

    foreach($texts as $id => $text){
      $keys = explode(self::KEY_SEPARATOR, $id);
      $lastKeyIndex = count($keys)-1;
      $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
      $decodedContent = $this->textProcessor->replaceShortcodeNodes($decodedContent);

      $item = &$fields;
      foreach($keys as $index => $key){
        if($index === $lastKeyIndex){
          $item[$key] = $decodedContent;
          continue;
        }

        $item = &$item[$key];
      }
    }

    foreach($fields as $key => $value){
      update_field($key, $value, $post->ID);
    }
  }

  /**
   * @return array
   */
  public function getSettingsViewBundle()
  {
    $options = $this->library->getSettingOption();
    $savedAcfFields = isset($options[Constant::SETTING_ACF_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_ACF_FIELDS]) : array();

    return array(
      'view' => 'backend/settings-acf',
      'context' => array(
        'acfFieldDefinitions' => $this->getAcfFieldDefinitions(),
        'savedAcfFields' => $savedAcfFields
      )
    );
  }

  /**
   * @param $postData
   */
  public function saveSettings($postData)
  {
    $checkedAcfFields = explode(',', $postData['acf']['checkedAcfFields']);

    $acfFieldsToSave = array();

    foreach($checkedAcfFields as $checkedAcfField){
      if(strpos($checkedAcfField, 'group_') === 0){
        continue;
      }

      $acfFieldsToSave[] = $checkedAcfField;
    }

    $this->library->saveSetting(Constant::SETTING_ACF_FIELDS, $acfFieldsToSave);
  }

  /**
   * @return array
   */
  private function getAcfFieldDefinitions()
  {
    $fieldGroups = function_exists( 'acf_get_field_groups' ) ? acf_get_field_groups() : apply_filters( 'acf/get_field_groups', array() );
    $acfFields = array();

    foreach ($fieldGroups as $fieldGroup) {
      $fieldGroupId = isset($fieldGroup['ID']) ? $fieldGroup['ID'] : $fieldGroup['id'];
      $fields = function_exists( 'acf_get_fields' ) ? acf_get_fields($fieldGroup) : apply_filters('acf/field_group/get_fields', array(), $fieldGroupId);

      $acfFields['group_'.$fieldGroupId] = array(
        'label' => $fieldGroup['title'],
        'type' => 'group',
        'sub_field_definitions' => $this->getFieldDefinitions($fields)
      );
    }

    return $acfFields;
  }

  /**
   * @param $fields
   * @return array
   */
  private function getFieldDefinitions($fields)
  {
    $group = array();

    foreach ($fields as $field) {
      $group[$field['name']] = array(
        'label' => $field['label'],
        'type' => 'field',
        'sub_field_definitions' => isset($field['sub_fields']) ? $this->getFieldDefinitions($field['sub_fields']) : array()
      );
    }

    return $group;
  }

  /**
   * @param $fields
   * @param $idPrefix
   * @param $texts
   * @param $keysToAdd
   * @return array
   */
  private function getFieldTexts($fields, $idPrefix, $texts, $keysToAdd)
  {
    foreach($fields as $key => $value){
      if(is_array($value)){
        $newIdPrefix = $idPrefix . $key . self::KEY_SEPARATOR;
        $texts = array_merge($texts, $this->getFieldTexts($value, $newIdPrefix, $texts, $keysToAdd));
        continue;
      }

      if(!in_array($key, $keysToAdd)){
        continue;
      }

      $id = $idPrefix . $key;
      $texts[$id] = $this->textProcessor->replaceShortcodes($value);
    }

    return $texts;
  }
}