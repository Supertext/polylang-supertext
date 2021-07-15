<?php

namespace Supertext\TextAccessors;

use Supertext\Helper\TextProcessor;
use Supertext\Helper\Library;
use Supertext\Helper\Constant;
use Supertext\Helper\View;

/**
 * Class ElementorTextAccessor
 * @package Supertext\TextAccessors
 */
class ElementorTextAccessor implements ITextAccessor, IMetaDataAware, ISettingsAware, IAddDefaultSettings
{
  /**
   * @var Array the text keys of the settings array
   */
  private static $defaultTextKeys = array('editor', 'caption', 'title', 'text', 'html');

  /**
   * @var TextProcessor the text processor
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
    return __('Elementor (Plugin)', 'supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = array();

    $translatableFields[] = array(
      'title' => __('Elementor content', 'supertext'),
      'name' => 'elementor_content',
      'checkedPerDefault' => true
    );

    return $translatableFields;
  }

  /**
   * @param $post
   * @return array
   */
  public function getRawTexts($post)
  {
    return $this->getElementorData($post->ID);
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    if (!$selectedTranslatableFields['elementor_content']) {
      return $texts;
    }

    $elements = $this->getElementorData($post->ID);

    $texts = $this->getTextProperties($elements);

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    $elements = $this->getElementorData($post->ID);

    $this->setTextProperties($elements, $texts);

    update_post_meta($post->ID, '_elementor_data', wp_slash(json_encode($elements)));
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getContentMetaData($post, $selectedTranslatableFields)
  {
    return array(
      '_elementor_data' => $this->getElementorData($post->ID),
      '_elementor_template_type' => get_post_meta($post->ID, '_elementor_template_type', true),
      '_elementor_controls_usage' => get_post_meta($post->ID, '_elementor_controls_usage', true),
      '_elementor_css' => get_post_meta($post->ID, '_elementor_css', true)
    );
  }

  /**
   * @param $post
   * @param $translationMetaData
   */
  public function setContentMetaData($post, $translationMetaData)
  {
    update_post_meta($post->ID, '_elementor_data', wp_slash(json_encode($translationMetaData['_elementor_data'])));
    update_post_meta($post->ID, '_elementor_template_type', $translationMetaData['_elementor_template_type']);
    update_post_meta($post->ID, '_elementor_controls_usage', $translationMetaData['_elementor_controls_usage']);
    update_post_meta($post->ID, '_elementor_css', $translationMetaData['_elementor_css']);
  }

  /**
   * @return array
   */
  public function getSettingsViewBundle()
  {
    $savedTextKeys = $this->library->getSettingOption(Constant::SETTING_ELEMENTOR_TEXT_KEYS);

    return array(
      'view' => new View('backend/settings-elementor'),
      'context' => array(
        'savedTextKeys' => $savedTextKeys
      )
    );
  }

  /**
   * @param $postData
   */
  public function saveSettings($postData)
  {
    $this->library->saveSettingOption(Constant::SETTING_ELEMENTOR_TEXT_KEYS, array_filter($postData['elementor-text-keys']));
  }

  /**
   * Adds default settings
   */
  public function addDefaultSettings()
  {
    $savedTextKeys = $this->library->getSettingOption(Constant::SETTING_ELEMENTOR_TEXT_KEYS);

    foreach (ElementorTextAccessor::$defaultTextKeys as $textKey) {
      if (in_array($textKey, $savedTextKeys)) {
        continue;
      }

      array_push($savedTextKeys, $textKey);
    }

    $this->library->saveSettingOption(Constant::SETTING_ELEMENTOR_TEXT_KEYS, $savedTextKeys);
  }

  private function getElementorData($postId)
  {
    return  json_decode(get_post_meta($postId, '_elementor_data', true), true);
  }

  /**
   * @param $elements
   * @return array
   */
  private function getTextProperties($elements)
  {
    $texts = array();

    foreach ($elements as $index => $element) {
      if (is_array($element['elements']) && count($element['elements'])) {
        $texts[$index]['elements'] = $this->getTextProperties($element['elements']);
      }

      if (!isset($element['settings'])) {
        continue;
      }

      $settingsTexts = $this->getSettingsTextProperties($element['settings']);

      if (empty($settingsTexts)) {
        continue;
      }

      $texts[$index]['settings'] = $settingsTexts;
    }

    return $texts;
  }

  private function getSettingsTextProperties($settings)
  {
    $texts = array();
    $textKeys = $this->library->getSettingOption(Constant::SETTING_ELEMENTOR_TEXT_KEYS);

    foreach (array_keys($settings) as $settingsKey) {
      $value = $settings[$settingsKey];

      if (is_array($value)) {
        $subTexts = $this->getSettingsTextProperties($value);

        if (!empty($subTexts)) {
          $texts[$settingsKey] = $subTexts;
        }

        continue;
      }

      if (in_array($settingsKey, $textKeys)) {
        $texts[$settingsKey] = $this->textProcessor->replaceShortcodes($value);
      }
    }

    return $texts;
  }

  /**
   * @param $entries
   * @param $texts
   */
  private function setTextProperties(&$entries, $texts)
  {
    foreach ($texts as $key => $value) {
      if (is_array($value)) {
        $this->setTextProperties($entries[$key], $value);
        continue;
      }

      $decodedContent = html_entity_decode($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
      $entries[$key] = $this->textProcessor->replaceShortcodeNodes($decodedContent);
    }
  }
}
