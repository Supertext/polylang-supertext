<?php

namespace Supertext\Polylang\TextAccessors;

use Supertext\Polylang\Helper\TextProcessor;

/**
 * Class SiteOriginTextAccessor
 * @package Supertext\Polylang\TextAccessors
 */
class SiteOriginTextAccessor implements ITextAccessor
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
    return __('Page Builder via SiteOrigin (Plugin)', 'polylang-supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = array();

    $translatableFields[] = array(
      'title' => __('Builder content', 'polylang-supertext'),
      'name' => 'builder_content',
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
    return get_post_meta($post->ID, 'panels_data', true);
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    if (!$selectedTranslatableFields['builder_content']) {
      return $texts;
    }

    $panelsData = get_post_meta($post->ID, 'panels_data', true);

    foreach ($panelsData['widgets'] as $key => $widget) {
      $texts[$key]['title'] = $widget['title'];
      $texts[$key]['text'] = isset($widget['text']) ? $widget['text'] : '';
    }

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    $panelsData = get_post_meta($post->ID, 'panels_data', true);

    foreach ($panelsData['widgets'] as $key => &$widget) {
      if (!isset($texts[$key])) {
        continue;
      }

      $widget['title'] = $texts[$key]['title'];
     $widget['text'] = $texts[$key]['text'];
    }

    update_post_meta($post->ID, 'panels_data', $panelsData);
  }
}