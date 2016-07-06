<?php

namespace Supertext\Polylang\Helper;

use Comotive\Util\ArrayManipulation;

class PcfContentAccessor implements IContentAccessor, ISettingsAware
{
  /**
   * @var text processor
   */
  private $textProcessor;

  /**
   * @var library
   */
  private $library;

  /**
   * @var array
   */
  private $pcfFieldDefinitions = array();

  public function __construct($textProcessor, $library)
  {
    $this->textProcessor = $textProcessor;
    $this->library = $library;
  }

  public function registerPluginFieldDefinitions($plugin, $fieldDefinitions)
  {
    $this->pcfFieldDefinitions['group_' . $plugin] = $fieldDefinitions;
  }

  public function hasRegisteredPluginFieldDefinitions()
  {
    return count($this->pcfFieldDefinitions) > 0;
  }

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
      'source_name' => __('Plugin defined custom fields', 'polylang-supertext'),
      'fields' => $translatableFields
    );
  }

  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    foreach ($selectedTranslatableFields as $id => $selected) {
      $texts[$id] = get_post_meta($post->ID, $id, true);
    }

    return $texts;
  }

  public function setTexts($post, $texts)
  {
    foreach ($texts as $id => $text) {
      $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
      update_post_meta($post->ID, $id, $decodedContent);
    }
  }

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