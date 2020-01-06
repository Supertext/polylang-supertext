<?php

namespace Supertext\Polylang\TextAccessors;

use Supertext\Polylang\Helper\Constant;
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
        $decodedContent = $this->setTranslatableBlockAttributes($texts['post_content_block_attributes'], parse_blocks($decodedContent), $decodedContent);
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

      $blockAttributesTexts[$key] = $value;
    }

    return $blockAttributesTexts;
  }

  private function setTranslatableBlockAttributes($blockAttributes, $blocks, $content)
  {
    $newContent = $content;

    foreach ($blocks as $index => $block) {
      if (!isset($blockAttributes[$index])) {
        continue;
      }

      $blockName = str_replace('/', '\/', str_replace('core/', '', $block['blockName']));

      if (isset($blockAttributes[$index]['inner-blocks'])) {
        $newContent = $this->setTranslatableBlockAttributes($blockAttributes[$index]['inner-blocks'], $block['innerBlocks'], $newContent);
      }

      foreach ($blockAttributes[$index]['attrs'] as $key => $value) {
        $oldValue = $block['attrs'][$key];
        $regex = "/(<!--\s*wp:$blockName\s*{.*\"$key\"\s*:\s*)\"$oldValue\"/";
        $newContent = preg_replace($regex, "$1\"$value\"", $newContent);
      }
    }

    return $newContent;
  }
}
