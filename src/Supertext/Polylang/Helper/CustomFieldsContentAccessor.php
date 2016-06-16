<?php

namespace Supertext\Polylang\Helper;

use Comotive\Util\ArrayManipulation;

class CustomFieldsContentAccessor implements IContentAccessor, ISettingsAware
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
    $options = $this->library->getSettingOption();
    $savedCustomFields = isset($options[Constant::SETTING_CUSTOM_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_CUSTOM_FIELDS]) : array();

    $translatableFields = array();

    foreach($savedCustomFields as $savedCustomField){

     if(!get_post_meta($postId, $savedCustomField, true)) {
      continue;
     }

     $translatableFields[] = array(
       'title' => $savedCustomField,
       'name' => $savedCustomField,
       'default' => true
     );
    }

    return $translatableFields;
  }

  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    foreach($selectedTranslatableFields as $id => $selected){
      $texts[$id] = get_post_meta($post->ID, $id, true);
    }

    return $texts;
  }

  public function setTexts($post, $texts)
  {
    foreach($texts as $id => $text){
      $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
      update_post_meta($post->ID, $id, $decodedContent);
    }
  }

  public function prepareTranslationPost($post, $translationPost)
  {
  }

  public function getSettingsViewBundle()
  {
    $options = $this->library->getSettingOption();
    $savedCustomFields = isset($options[Constant::SETTING_CUSTOM_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_CUSTOM_FIELDS]) : array();

    return array(
      'view' => 'backend/settings-custom-fields',
      'context' => array(
        'savedCustomFields' => $savedCustomFields
      )
    );
  }

  public function SaveSettings($postData)
  {
    $this->library->saveSetting(Constant::SETTING_CUSTOM_FIELDS, array_filter($postData['custom-fields']));
  }
}