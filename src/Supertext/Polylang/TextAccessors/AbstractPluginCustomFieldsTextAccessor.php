<?php

namespace Supertext\Polylang\TextAccessors;

use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\Library;
use Supertext\Polylang\Helper\TextProcessor;
use Supertext\Polylang\Helper\View;

/**
 * Class PluginCustomFieldsTextAccessor
 * @package Supertext\Polylang\TextAccessors
 */
abstract class AbstractPluginCustomFieldsTextAccessor implements ITextAccessor, ISettingsAware
{
  /**
   * @var TextProcessor text processor
   */
  protected $textProcessor;
  /**
   * @var Library library
   */
  protected $library;
  /**
   * @var string plugin id
   */
  protected $pluginId;

  /**
   * @param $textProcessor
   * @param $library
   */
  public function __construct($textProcessor, $library)
  {
    $this->textProcessor = $textProcessor;
    $this->library = $library;
    $this->pluginId = $this->getPluginId();
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $postCustomFields = get_post_meta($postId);
    $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);

    if(!isset($savedFieldDefinitions[$this->pluginId])){
      return array();
    }

    $translatableFields = array();
    $metaKeys = array_keys($postCustomFields);

    foreach ($savedFieldDefinitions[$this->pluginId] as $savedFieldDefinition) {
      if (count(preg_grep('/^' . $savedFieldDefinition['meta_key_regex'] . '$/', $metaKeys)) > 0) {
        $translatableFields[] = array(
          'title' => $savedFieldDefinition['label'],
          'name' => $savedFieldDefinition['meta_key_regex'],
          'checkedPerDefault' => true
        );
      }
    }

    return $translatableFields;
  }

  /**
   * @param $post
   * @return array
   */
  public function getRawTexts($post)
  {
    return get_post_meta($post->ID);
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

    foreach($postCustomFields as $metaKey => $value){
      foreach($selectedTranslatableFields as $metaKeyRegex => $selected){
        if (!preg_match('/^' . $metaKeyRegex . '$/', $metaKey)) {
          continue;
        }

        $texts[$metaKey] = $this->textProcessor->replaceShortcodes($value[0]);
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
    $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);
    $savedFieldDefinitionIds = array();

    if(isset($savedFieldDefinitions[$this->pluginId])){
      foreach($savedFieldDefinitions[$this->pluginId] as $savedFieldDefinition){
        $savedFieldDefinitionIds[] = $savedFieldDefinition['id'];
      }
    }

    return array(
      'view' => new View('backend/settings-plugin-custom-fields'),
      'context' => array(
        'pluginId' => $this->pluginId,
        'pluginName' => $this->getName(),
        'fieldDefinitions' => $this->getFieldDefinitions(),
        'savedFieldDefinitionIds' => $savedFieldDefinitionIds
      )
    );
  }

  /**
   * @param $postData
   */
  public function saveSettings($postData)
  {
    $checkedFieldIds = explode(',', $postData['pluginCustomFields'][$this->pluginId]['checkedFields']);
    $fieldDefinitionsToSave = array();

    $fieldDefinitions = $this->getFieldDefinitions();

    while (($field = array_shift($fieldDefinitions))) {
      if (!empty($field['sub_field_definitions'])) {
        $fieldDefinitions = array_merge($fieldDefinitions, $field['sub_field_definitions']);
        continue;
      }

      if (in_array($field['id'], $checkedFieldIds) && isset($field['meta_key_regex'])) {
        $fieldToSave = $field;
        unset($fieldToSave['sub_field_definitions']);
        $fieldDefinitionsToSave[] = $fieldToSave;
      }
    }

    $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);
    $savedFieldDefinitions[$this->pluginId] = $fieldDefinitionsToSave;

    $this->library->saveSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS, $savedFieldDefinitions);
  }

  /**
   * Abstract function
   * Gets the field definitions
   * @return mixed
   */
  protected abstract function getFieldDefinitions();

  /**
   * @return mixed
   */
  private function getPluginId()
  {
    return lcfirst(str_replace('TextAccessor', '', (new \ReflectionClass($this))->getShortName()));
  }
}