<?php

namespace Supertext\Polylang\Helper;

use Comotive\Util\ArrayManipulation;

/**
 * Class CustomFieldsContentAccessor
 * @package Supertext\Polylang\Helper
 */
class CustomFieldsContentAccessor implements IContentAccessor, ISettingsAware
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
   * @param TextProcessor $textProcessor
   * @param Library $library
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
  public function getName()
  {
    return __('Self-defined custom fields', 'polylang-supertext');
  }

  /**
   * @param $postId
   * @return array
   */
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
       'checkedPerDefault' => true
     );
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

    foreach($selectedTranslatableFields as $id => $selected){
      $texts[$id] = get_post_meta($post->ID, $id, true);
    }

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    foreach($texts as $id => $text){
      $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
      update_post_meta($post->ID, $id, $decodedContent);
    }
  }

  /**
   * @return array
   */
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

  /**
   * @param $postData
   */
  public function saveSettings($postData)
  {
    $this->library->saveSetting(Constant::SETTING_CUSTOM_FIELDS, array_filter($postData['custom-fields']));
  }
}