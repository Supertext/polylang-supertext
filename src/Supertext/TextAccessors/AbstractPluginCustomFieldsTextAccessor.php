<?php

namespace Supertext\TextAccessors;

use Supertext\Helper\Constant;
use Supertext\Helper\Library;
use Supertext\Helper\TextProcessor;
use Supertext\Helper\View;

/**
 * Class PluginCustomFieldsTextAccessor
 * @package Supertext\TextAccessors
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

    if (!isset($savedFieldDefinitions[$this->pluginId])) {
      return array();
    }

    $translatableFields = array();
    $metaKeys = array_keys($postCustomFields);

    foreach ($savedFieldDefinitions[$this->pluginId] as $savedFieldDefinition) {
      if (count(preg_grep('/^' . $savedFieldDefinition['meta_key_regex'] . '$/', $metaKeys)) > 0) {
        $translatableFields[] = array(
          'title' => $savedFieldDefinition['label'],
          'name' => $savedFieldDefinition['id'],
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
    $serializedContents = array();
    $postCustomFields = get_post_meta($post->ID);
    $selectedFieldDefinitions = $this->getSelectedFieldDefinitions($selectedTranslatableFields);

    foreach ($postCustomFields as $metaKey => $value) {
      foreach ($selectedFieldDefinitions as $selectedFieldDefinition) {
        $metaKeyRegex = $selectedFieldDefinition['meta_key_regex'];

        if (!preg_match('/^' . $metaKeyRegex . '$/', $metaKey)) {
          continue;
        }

        if (isset($selectedFieldDefinition['serialized_key'])) {
          $serializedKey = $selectedFieldDefinition['serialized_key'];
          if (!isset($serializedContents[$metaKey])) {
            $serializedContents[$metaKey] = array('value' => $value[0], 'keys' => array());
          }
          array_push($serializedContents[$metaKey]['keys'], $serializedKey);
        } else {
          $texts[$metaKey] = $this->textProcessor->replaceShortcodes($value[0]);
        }
      }
    }

    foreach ($serializedContents as $metaKey => $serializedContent) {
      $object = unserialize($serializedContent['value']);
      $keys = $serializedContent['keys'];
      $text = '';
      foreach ($object as $key => $value) {

        if (in_array($key, $keys)) {
          $content =  $this->textProcessor->replaceShortcodes($value);
          $text .= '<span name="' . $key . '">' . $content . '</span>';
        } else {
          $text .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }
      }

      $texts[$metaKey] = $text;
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
      $value = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');

      if (preg_match('/<span\s+name=("|\')([a-z]+)("|\')\s*>([^<]+)<\/\s*span\s*>/', $value)) {
        $value = $this->getArrayItemsFromHtmlText($value);
      } else {
        $value = $this->textProcessor->replaceShortcodeNodes($value);
      }

      $filteredValue = apply_filters(Constant::FILTER_POST_META_TRANSLATION, $value, $id);

      update_post_meta($post->ID, $id, $filteredValue);
    }
  }

  /**
   * @return array
   */
  public function getSettingsViewBundle()
  {
    $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);
    $savedFieldDefinitionIds = array();

    if (isset($savedFieldDefinitions[$this->pluginId])) {
      foreach ($savedFieldDefinitions[$this->pluginId] as $savedFieldDefinition) {
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

  private function getSelectedFieldDefinitions($selectedTranslatableFields)
  {
    $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);

    if (!isset($savedFieldDefinitions[$this->pluginId])) {
      return array();
    }

    $selectedFieldDefinitions = array();

    foreach ($selectedTranslatableFields as $fieldDefinitionId => $selected) {
      foreach ($savedFieldDefinitions[$this->pluginId] as $fieldDefinition) {
        if ($fieldDefinition['id'] === $fieldDefinitionId) {
          array_push($selectedFieldDefinitions, $fieldDefinition);
        }
      }
    }

    return $selectedFieldDefinitions;
  }

  private function getArrayItemsFromHtmlText($htmlContent)
  {
    $doc = $this->library->createHtmlDocument($htmlContent);
    $childNodes = $doc->getElementsByTagName('body')->item(0)->childNodes;
    $items = array();

    foreach ($childNodes as $childNode) {
      switch ($childNode->nodeName) {
        case 'span':
          $key = $childNode->attributes->getNamedItem('name')->nodeValue;
          $value = $this->textProcessor->replaceShortcodeNodes($childNode->nodeValue);
          $items[$key] = $value;
          break;
        case 'input':
          $key = $childNode->attributes->getNamedItem('name')->nodeValue;
          $value = $childNode->attributes->getNamedItem('value')->nodeValue;
          $items[$key] = $value;
          break;
      }
    }

    return $items;
  }
}
