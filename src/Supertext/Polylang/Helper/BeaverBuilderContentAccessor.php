<?php

namespace Supertext\Polylang\Helper;

use FLBuilderModel;

class BeaverBuilderContentAccessor implements IContentAccessor
{
  const KEY_SEPARATOR = '__';

  /**
   * @var TextProcessor the text processor
   */
  private $textProcessor;

  public function __construct($textProcessor)
  {
    $this->textProcessor = $textProcessor;
  }

  public function getTranslatableFields($postId)
  {
    $translatableFields = array();

    $translatableFields[] = array(
      'title' => __('Beaver Builder content', 'polylang-supertext'),
      'name' => 'beaver_builder_content',
      'default' => true
    );

    return array(
      'source_name' => __('Beaver Builder (Plugin)', 'polylang-supertext'),
      'fields' => $translatableFields
    );
  }

  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    if(!$selectedTranslatableFields['beaver_builder_content']){
      return $texts;
    }

    $layoutData = FLBuilderModel::get_layout_data(null, $post->ID);

    foreach ($layoutData as $layoutObject) {
      if ($layoutObject->type !== 'module') {
        continue;
      }

      $settingsTextProperties = $this->getTextProperties($layoutObject->settings);

      $flattenTextProperties = $this->flattenArray($settingsTextProperties, $layoutObject->node . self::KEY_SEPARATOR . 'settings');

      $texts = array_merge($texts, $flattenTextProperties);
    }

    return $texts;
  }

  public function setTexts($post, $texts)
  {
    $layoutData = FLBuilderModel::get_layout_data(null, $post->ID);

    foreach ($texts as $id => $text) {
      $object = $layoutData;
      $keys = explode(self::KEY_SEPARATOR, $id);
      $lastKeyIndex = count($keys) - 1;

      foreach ($keys as $index => $key) {

        if ($index !== $lastKeyIndex) {
          if (is_array($object)) {
            $object = $object[$key];
          } else if (is_object($object)) {
            $object = $object->{$key};
          }

          continue;
        }

        $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
        $object->$key = $this->textProcessor->replaceShortcodeNodes($decodedContent);
      }
    }

    FLBuilderModel::update_layout_data($layoutData, null, $post->ID);
  }

  public function prepareTranslationPost($post, $translationPost)
  {
    update_post_meta($translationPost->ID, '_fl_builder_enabled', get_post_meta($post->ID, '_fl_builder_enabled', true));

    $layoutData = FLBuilderModel::get_layout_data(null, $post->ID);
    FLBuilderModel::update_layout_data($layoutData, null, $translationPost->ID);

    $layoutSettings = FLBuilderModel::get_layout_settings(null, $post->ID);
    FLBuilderModel::update_layout_settings($layoutSettings, null, $translationPost->ID);
  }

  private function getTextProperties($settings)
  {
    $texts = array();

    foreach ($settings as $key => $value) {
      if (stripos($key, 'text') === false && stripos($key, 'title') === false && stripos($key, 'html') === false) {
        continue;
      }

      $texts[$key] = $this->textProcessor->replaceShortcodes($value);
    }

    return $texts;
  }

  private function flattenArray($settingsTextProperties, $keyPrefix)
  {
    $flatten = array();

    foreach ($settingsTextProperties as $key => $value) {
      $flattenKey = $keyPrefix . self::KEY_SEPARATOR . $key;

      if (is_array($value) || is_object($value)) {
        $flatten = array_merge($flatten, $this->flattenArray($value, $flattenKey));
        continue;
      }

      $flatten[$flattenKey] = $value;
    }

    return $flatten;
  }
}