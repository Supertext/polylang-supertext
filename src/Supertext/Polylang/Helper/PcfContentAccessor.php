<?php

namespace Supertext\Polylang\Helper;

use Comotive\Util\ArrayManipulation;

/**
 * Class PcfContentAccessor
 * @package Supertext\Polylang\Helper
 */
class PcfContentAccessor implements IContentAccessor, ISettingsAware
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
   * @var array
   */
  private $pcfFieldDefinitions = array();

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
   * @param $plugin
   * @param $fieldDefinitions
   */
  public function registerPluginFieldDefinitions($plugin, $fieldDefinitions)
  {
    $this->pcfFieldDefinitions['group_' . $plugin] = $fieldDefinitions;
  }

  /**
   * @return bool true if has registered plugin field definitions
   */
  public function hasRegisteredPluginFieldDefinitions()
  {
    return count($this->pcfFieldDefinitions) > 0;
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $options = $this->library->getSettingOption();
    $savedPcfFields = isset($options[Constant::SETTING_PCF_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_PCF_FIELDS]) : array();
    $translatableFields = array();

    foreach ($savedPcfFields as $savedPcfField) {
      if (!get_post_meta($postId, $savedPcfField, true)) {
        continue;
      }

      foreach ($this->pcfFieldDefinitions as $pcfFieldDefinition) {
        $subFieldDefinition = $pcfFieldDefinition['sub_field_definitions'];

        $translatableFields[] = array(
          'title' => $subFieldDefinition[$savedPcfField]['label'],
          'name' => $savedPcfField,
          'default' => true
        );

      }
    }

    return array(
      'sourceName' => __('Plugin defined custom fields', 'polylang-supertext'),
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

    foreach ($selectedTranslatableFields as $id => $selected) {
      $texts[$id] = $this->textProcessor->replaceShortcodes(get_post_meta($post->ID, $id, true));
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
    $savedPcfFields = isset($options[Constant::SETTING_PCF_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_PCF_FIELDS]) : array();

    return array(
      'view' => 'backend/settings-pcf',
      'context' => array(
        'pcfFieldDefinitions' => $this->pcfFieldDefinitions,
        'savedPcfFields' => $savedPcfFields
      )
    );
  }

  /**
   * @param $postData
   */
  public function saveSettings($postData)
  {
    $checkedPcfFields = explode(',', $postData['pcf']['checkedPcfFields']);

    $pcfFieldsToSave = array();

    foreach ($checkedPcfFields as $checkedPcfField) {
      if (strpos($checkedPcfField, 'group_') === 0) {
        continue;
      }

      $pcfFieldsToSave[] = $checkedPcfField;
    }

    $this->library->saveSetting(Constant::SETTING_PCF_FIELDS, $pcfFieldsToSave);
  }
}