<?php

namespace Supertext\Polylang\Helper;

use Comotive\Util\ArrayManipulation;

class AcfTextAccessor implements ITextAccessor, ISettingsAware
{
  /**
   * @var text processor
   */
  private $textProcessor;

  /**
   * @var library
   */
  private $library;

  public function __construct($textProcessor, $library)
  {
    $this->textProcessor = $textProcessor;
    $this->library = $library;
  }

  public function getTranslatableFields($postId)
  {
    $translatableFields = array();
    $options = $this->library->getSettingOption();
    $savedAcfFields = isset($options[Constant::SETTING_ACF_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_ACF_FIELDS]) : array();
    $fields = get_field_objects($postId);

    foreach($fields as $field){
      if(!in_array($field['name'], $savedAcfFields)){
        continue;
      }

      $translatableFields[] = array(
        'title' => $field['label'],
        'name' => $field['name'],
        'default' => true
      );
    }

    return $translatableFields;
  }

  public function getTexts($post, $selectedTranslatableFields)
  {
    $fields = get_fields($post->ID);

    $texts = array();

    foreach($selectedTranslatableFields as $id => $selected){
      $texts[$id] = $fields[$id];
    }

    return $texts;
  }

  public function setTexts($post, $texts)
  {
    foreach($texts as $id => $text){
      update_field($id, $text, $post->ID);
    }
  }

  public function prepareTranslationPost($post, $translationPost)
  {

  }

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


  public function getPostDataKey()
  {
    return 'acf';
  }

  public function SaveSettings($postData)
  {
    $checkedAcfFields = explode(',', $postData['checkedAcfFields']);

    $acfFieldsToSave = array();

    foreach($checkedAcfFields as $checkedAcfField){
      if(strpos($checkedAcfField, 'group_') === 0){
        continue;
      }

      $acfFieldsToSave[] = $checkedAcfField;
    }

    $this->library->saveSetting(Constant::SETTING_ACF_FIELDS, $acfFieldsToSave);
  }

  private function getAcfFieldDefinitions()
  {
    $fieldGroups = function_exists( 'acf_get_field_groups' ) ? acf_get_field_groups() : apply_filters( 'acf/get_field_groups', array() );
    $acfFields = array();

    foreach ($fieldGroups as $fieldGroup) {
      $fieldGroupId = isset($fieldGroup['ID']) ? $fieldGroup['ID'] : $fieldGroup['id'];
      $fields = function_exists( 'acf_get_fields' ) ? acf_get_fields($fieldGroup) : apply_filters('acf/field_group/get_fields', array(), $fieldGroupId);

      $acfFields[] = array(
        'id' => 'group_'.$fieldGroupId,
        'label' => $fieldGroup['title'],
        'type' => 'group',
        'sub_field_definitions' => $this->getFieldDefinitions($fields)
      );
    }

    return $acfFields;
  }

  private function getFieldDefinitions($fields)
  {
    $group = array();

    foreach ($fields as $field) {
      $group[] = array(
        'id' => $field['name'],
        'label' => $field['label'],
        'type' => 'field',
        'sub_field_definitions' => isset($field['sub_fields']) ? $this->getFieldDefinitions($field['sub_fields']) : array()
      );
    }

    return $group;
  }
}