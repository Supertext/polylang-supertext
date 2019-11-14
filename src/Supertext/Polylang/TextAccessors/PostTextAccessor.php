<?php

namespace Supertext\Polylang\TextAccessors;

use Supertext\Polylang\Helper\TextProcessor;

/**
 * Class PostTextAccessor
 * @package Supertext\Polylang\TextAccessors
 */
class PostTextAccessor implements ITextAccessor
{
  /**
   * @var TextProcessor the text processor
   */
  private $textProcessor;

  /**
   * @var bool
   */
  private $isPostContentCheckedPerDefault = true;

  /**
   * @param TextProcessor $textProcessor
   */
  public function __construct($textProcessor)
  {
    $this->textProcessor = $textProcessor;
  }

  public function uncheckPostContentPerDefault()
  {
    $this->isPostContentCheckedPerDefault = false;
  }

  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName()
  {
    return __('Post', 'polylang-supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = array();

    $translatableFields[] = array(
      'title' => __('Title', 'polylang-supertext'),
      'name' => 'post_title',
      'checkedPerDefault' => true
    );

    $translatableFields[] = array(
      'title' => __('Content', 'polylang-supertext'),
      'name' => 'post_content',
      'checkedPerDefault' => $this->isPostContentCheckedPerDefault
    );

    $translatableFields[] = array(
      'title' => __('Excerpt', 'polylang-supertext'),
      'name' => 'post_excerpt',
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
    return $post;
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    if ($selectedTranslatableFields['post_title']) {
      $texts['post_title'] = $post->post_title;
    }

    if ($selectedTranslatableFields['post_content']) {
      $texts['post_content'] = $this->textProcessor->replaceShortcodes($post->post_content);
    }

    if ($selectedTranslatableFields['post_content'] && use_block_editor_for_post($post)) {
      $texts['post_content_block_attributes'] = $this->getTranslatableBlockAttributes($post);
    }

    if ($selectedTranslatableFields['post_excerpt']) {
      $texts['post_excerpt'] = $post->post_excerpt;
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
      if ($id === 'post_content_block_attributes') {
        $post->post_content = $this->setTranslatableBlockAttributes($text, $post->post_content);
        continue;
      }

      $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');

      if ($id === 'post_content') {
        $decodedContent = $this->textProcessor->replaceShortcodeNodes($decodedContent);
      }

      $post->{$id} = $decodedContent;
    }
  }

  private function getTranslatableBlockAttributes($post)
  {
    $translatableBlockAttributes = array();
    $blocks = parse_blocks($post->post_content);

    foreach ($blocks as $index => $block) {
      if (empty($block['attrs'])) {
        continue;
      }

      $translatableBlockAttributesTexts = $this->getTranslatableBlockAttributesTexts($block['attrs']);

      if (empty($translatableBlockAttributesTexts)) {
        continue;
      }

      $translatableBlockAttributes[$index] = $translatableBlockAttributesTexts;
    }

    return $translatableBlockAttributes;
  }

  private function getTranslatableBlockAttributesTexts($attributes)
  {
    $translatableBlockAttributesTexts = array();

    foreach ($attributes as $key => $value) {
      if (!is_string($value)) {
        continue;
      }

      $translatableBlockAttributesTexts[$key] = $value;
    }

    return $translatableBlockAttributesTexts;
  }

  private function setTranslatableBlockAttributes($translatableBlockAttributes, $content)
  {
    $newContent = '';
    $blocks = parse_blocks($content);

    foreach ($blocks as $index => $block) {
      if (isset($translatableBlockAttributes[$index])) {
        foreach ($translatableBlockAttributes[$index] as $key => $value) {
          $block['attrs'][$key] = $value;
        }
      }

      $newContent .= $this->serializeBlock($block);
    }

    return $newContent;
  }

  private function serializeBlock($block)
  {
    if (!isset($block['blockName'])) {
      return false;
    }
    $name = $block['blockName'];
    if (0 === strpos($name, 'core/')) {
      $name = substr($name, strlen('core/'));
    }
    if (empty($block['attrs'])) {
      $opening_tag_suffix = '';
    } else {
      $opening_tag_suffix = ' ' . json_encode($block['attrs']);
    }
    if (empty($block['innerHTML'])) {
      return sprintf(
        '<!-- wp:%s%s /-->',
        $name,
        $opening_tag_suffix
      );
    } else {
      return sprintf(
        '<!-- wp:%1$s%2$s -->%3$s<!-- /wp:%1$s -->',
        $name,
        $opening_tag_suffix,
        $block['innerHTML']
      );
    }
  }
}
