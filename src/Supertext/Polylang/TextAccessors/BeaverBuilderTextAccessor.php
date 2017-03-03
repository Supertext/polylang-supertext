<?php

namespace Supertext\Polylang\TextAccessors;

use FLBuilderModel;
use Supertext\Polylang\Helper\TextProcessor;

/**
 * Class BeaverBuilderTextAccessor
 * @package Supertext\Polylang\TextAccessors
 */
class BeaverBuilderTextAccessor implements ITextAccessor, IMetaDataAware
{
  /**
   * @var TextProcessor the text processor
   */
  private $textProcessor;

  /**
   * @param TextProcessor $textProcessor
   */
  public function __construct($textProcessor)
  {
    $this->textProcessor = $textProcessor;
  }

  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName()
  {
    return __('Beaver Builder (Plugin)', 'polylang-supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = array();

    $translatableFields[] = array(
      'title' => __('Beaver Builder content', 'polylang-supertext'),
      'name' => 'beaver_builder_content',
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
    return FLBuilderModel::get_layout_data(null, $post->ID);
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    if (!$selectedTranslatableFields['beaver_builder_content']) {
      return $texts;
    }

    $layoutData = FLBuilderModel::get_layout_data(null, $post->ID);

    foreach ($layoutData as $key => $layoutObject) {
      if ($layoutObject->type !== 'module') {
        continue;
      }

      $settingsTextProperties = $this->getTextProperties($layoutObject->settings);

      if(!count($settingsTextProperties)){
        continue;
      }

      $texts[$key]['settings'] = $settingsTextProperties;
    }

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    $layoutData = FLBuilderModel::get_layout_data(null, $post->ID);

    foreach ($layoutData as $key => $layoutObject) {
      if (!isset($texts[$key])) {
        continue;
      }

      $this->setTextProperties($layoutObject->settings, $texts[$key]['settings']);
    }

    FLBuilderModel::update_layout_data($layoutData, null, $post->ID);
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getContentMetaData($post, $selectedTranslatableFields)
  {
    return array(
      '_fl_builder_enabled' => get_post_meta($post->ID, '_fl_builder_enabled', true),
      'layoutData' => FLBuilderModel::get_layout_data(null, $post->ID),
      'layoutSettings' => FLBuilderModel::get_layout_settings(null, $post->ID)
    );
  }

  /**
   * @param $post
   * @param $translationMetaData
   */
  public function setContentMetaData($post, $translationMetaData)
  {
    update_post_meta($post->ID, '_fl_builder_enabled', $translationMetaData['_fl_builder_enabled']);
    FLBuilderModel::update_layout_data($translationMetaData['layoutData'], null, $post->ID);
    FLBuilderModel::update_layout_settings($translationMetaData['layoutSettings'], null, $post->ID);
  }

  /**
   * @param $settings
   * @return array
   */
  private function getTextProperties($settings)
  {
    $texts = array();

    foreach ($settings as $key => $value) {
      if (stripos($key, 'text') === false && stripos($key, 'title') === false && stripos($key, 'html') === false && stripos($key, 'widget-') === false) {
        continue;
      }

      if(is_object($value) || is_array($value)){
        $texts[$key] = $this->getTextProperties($value);
        continue;
      }

      $texts[$key] = $this->textProcessor->replaceShortcodes($value);
    }

    return $texts;
  }

  /**
   * @param $settings
   * @param $texts
   */
  private function setTextProperties($settings, $texts)
  {
    foreach ($texts as $key => $text) {
      if(is_array($text)){
        if(is_object($settings)){
          $this->setTextProperties($settings->{$key}, $text);
        }else if(is_array($settings)){
          $this->setTextProperties($settings[$key], $text);
        }
        continue;
      }

      $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');

      if(is_object($settings)){
        $settings->{$key} = $this->textProcessor->replaceShortcodeNodes($decodedContent);
      }else if(is_array($settings)){
        $settings[$key] = $this->textProcessor->replaceShortcodeNodes($decodedContent);
      }
    }
  }
}