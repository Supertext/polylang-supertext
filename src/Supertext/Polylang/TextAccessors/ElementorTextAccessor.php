<?php

namespace Supertext\Polylang\TextAccessors;

use Supertext\Polylang\Helper\TextProcessor;
use \Elementor\Plugin;

/**
 * Class ElementorTextAccessor
 * @package Supertext\Polylang\TextAccessors
 */
class ElementorTextAccessor implements ITextAccessor, IMetaDataAware
{
  /**
   * @var Array the text keys of the settings array
   */
  private static $textKeys = array('editor', 'caption', 'title', 'text', 'html');

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
    return __('Elementor (Plugin)', 'polylang-supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = array();

    $translatableFields[] = array(
      'title' => __('Elementor content', 'polylang-supertext'),
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
    return Plugin::$instance->documents->get( $post->ID )->get_json_meta( '_elementor_data' );
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

    $elements = Plugin::$instance->documents->get( $post->ID )->get_json_meta( '_elementor_data' );

    $texts = $this->getTextProperties($elements);

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    $elements = Plugin::$instance->documents->get( $post->ID )->get_json_meta( '_elementor_data' );

    $this->setTextProperties($elements, $texts);

    update_post_meta($post->ID, '_elementor_data', json_encode($elements));
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getContentMetaData($post, $selectedTranslatableFields)
  {
    return array(
      '_elementor_data' => get_post_meta($post->ID, '_elementor_data', true),
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
    update_post_meta($post->ID, '_elementor_data', $translationMetaData['_elementor_data']);
    update_post_meta($post->ID, '_elementor_template_type', $translationMetaData['_elementor_template_type']);
    update_post_meta($post->ID, '_elementor_controls_usage', $translationMetaData['_elementor_controls_usage']);
    update_post_meta($post->ID, '_elementor_css', $translationMetaData['_elementor_css']);
  }

  /**
   * @param $elements
   * @return array
   */
  private function getTextProperties($elements)
  {
    $texts = array();

    foreach ($elements as $index => $element) {
      if(is_array($element['elements']) && count($element['elements'])){
        $texts[$index]['elements'] = $this->getTextProperties($element['elements']);
      }

      $settings = $element['settings'];

      foreach(ElementorTextAccessor::$textKeys as $textKey){
        if(isset($settings[$textKey])){
          $texts[$index]['settings'][$textKey] = $this->textProcessor->replaceShortcodes($settings[$textKey]);
        }
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
      if(is_array($value)){
        $this->setTextProperties($entries[$key], $value);
        continue;
      }

      $decodedContent = html_entity_decode($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
      $entries[$key] = $this->textProcessor->replaceShortcodeNodes($decodedContent);
    }
  }
}