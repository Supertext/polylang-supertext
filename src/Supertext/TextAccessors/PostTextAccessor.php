<?php

namespace Supertext\TextAccessors;

use Supertext\Helper\Constant;
use Supertext\Helper\TextProcessor;

/**
 * Class PostTextAccessor
 * @package Supertext\TextAccessors
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
    return __('Post', 'supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = array();

    $translatableFields[] = array(
      'title' => __('Title', 'supertext'),
      'name' => 'post_title',
      'checkedPerDefault' => true
    );

    $translatableFields[] = array(
      'title' => __('Content', 'supertext'),
      'name' => 'post_content',
      'checkedPerDefault' => $this->isPostContentCheckedPerDefault
    );

    $translatableFields[] = array(
      'title' => __('Excerpt', 'supertext'),
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
      $blocks = parse_blocks($post->post_content);
      $translatableBlockAttributes = apply_filters(Constant::FILTER_TRANSLATABLE_BLOCK_ATTRIBUTES, array());
      $texts['post_content_block_attributes'] = $this->getBlockAttributes($blocks, $translatableBlockAttributes);
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
    if (isset($texts['post_content'])) {
      $decodedContent = html_entity_decode($texts['post_content'], ENT_COMPAT | ENT_HTML401, 'UTF-8');

      if (isset($texts['post_content_block_attributes'])) {
        $newBlocks = $this->setTranslatableBlockAttributes($texts['post_content_block_attributes'], parse_blocks($decodedContent));
        $decodedContent = serialize_blocks($newBlocks);
      }
      $post->post_content = $this->textProcessor->replaceShortcodeNodes($decodedContent);
    }

    if (isset($texts['post_title'])) {
      $post->post_title = html_entity_decode($texts['post_title'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
    }

    if (isset($texts['post_excerpt'])) {
      $post->post_excerpt = html_entity_decode($texts['post_excerpt'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
    }
  }

  private function getBlockAttributes($blocks, $translatableBlockAttributes)
  {
    $blockAttributes = array();
    foreach ($blocks as $index => $block) {
      if (!empty($block['innerBlocks'])) {
        $innerBlockAttributes = $this->getBlockAttributes($block['innerBlocks'], $translatableBlockAttributes);

        if (!empty($innerBlockAttributes)) {
          $blockAttributes[$index] = array('inner-blocks' => $innerBlockAttributes);
        }
      }

      $blockName = $block['blockName'];

      if (empty($block['attrs']) || !isset($translatableBlockAttributes[$blockName])) {
        continue;
      }

      $blockAttributesTexts = $this->getBlockAttributesTexts($block['attrs'], $translatableBlockAttributes[$blockName]);

      if (empty($blockAttributesTexts)) {
        continue;
      }

      if (isset($blockAttributes[$index])) {
        $blockAttributes[$index]['attrs'] = $blockAttributesTexts;
      } else {
        $blockAttributes[$index] = array('attrs' => $blockAttributesTexts);
      }
    }

    return $blockAttributes;
  }

  private function getBlockAttributesTexts($attributes, $translatableBlockAttributeKeys)
  {
    $blockAttributesTexts = array();

    foreach ($attributes as $key => $value) {
      if (!in_array($key, $translatableBlockAttributeKeys)) {
        continue;
      }

      $blockAttributesTexts[$key] = $this->textProcessor->replaceShortcodes($value);
    }

    return $blockAttributesTexts;
  }

  private function setTranslatableBlockAttributes($blockAttributes, $blocks)
  {
    $newBlocks = array();

    foreach ($blocks as $index => $block) {
      if (!isset($blockAttributes[$index])) {
        array_push($newBlocks, $block);
        continue;
      }

      if (isset($blockAttributes[$index]['inner-blocks'])) {
        $block['innerBlocks'] = $this->setTranslatableBlockAttributes($blockAttributes[$index]['inner-blocks'], $block['innerBlocks']);
      }

      foreach ($blockAttributes[$index]['attrs'] as $key => $value) {
        $block['attrs'][$key] = $this->textProcessor->replaceShortcodeNodes($value);
      }

      array_push($newBlocks, $block);
    }

    return $newBlocks;
  }
}
