<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\TextAccessors\ITextAccessor;
use Supertext\Polylang\TextAccessors\ITranslationAware;

/**
 * Processes post content
 * @package Supertext\Polylang\Backend
 */
class ContentProvider
{
  /**
   * @var ITextAccessor[] the text accessors
   */
  private $textAccessors = null;

  /**
   * @var \Supertext\Polylang\Helper\Library
   */
  private $library;

  /**
   * @param ITextAccessor[] $textAccessors
   * @param \Supertext\Polylang\Helper\Library $library
   */
  public function __construct($textAccessors, $library)
  {
    $this->textAccessors = $textAccessors;
    $this->library = $library;
  }

  /**
   * @param $postId
   * @return array|mixed|void
   */
  public function getTranslatableFieldGroups($postId)
  {
    $result = array();

    foreach ($this->textAccessors as $id => $textAccessor) {
      $result[$id] = array(
        'name' => $textAccessor->getName(),
        'fields' => $textAccessor->getTranslatableFields($postId)
      );
    }

    // Let developers add their own translatable items
    $result = apply_filters('translation_fields_for_post', $result, $postId);

    return $result;
  }

  /**
   * @param $post
   * @return array|mixed|void
   */
  public function getRawData($post)
  {
    $result = array();

    foreach ($this->textAccessors as $id => $textAccessor) {
      $texts = $textAccessor->getRawTexts($post);

      if (count($texts) === 0) {
        continue;
      }

      $result[$id] = $texts;
    }

    // Let developers add their own fields
    $result = apply_filters('raw_data_for_post', $result, $post->ID);

    return $result;
  }

  /**
   * @param $post
   * @param $translatableFieldGroups
   * @return array|mixed|void
   */
  public function getTranslationData($post, $translatableFieldGroups)
  {
    $result = array();

    foreach ($this->textAccessors as $id => $textAccessor) {
      if (!isset($translatableFieldGroups[$id])) {
        continue;
      }

      $texts = $textAccessor->getTexts($post, $translatableFieldGroups[$id]['fields']);

      if (count($texts) === 0) {
        continue;
      }

      $result[$id] = $texts;
    }

    // Let developers add their own fields
    $result = apply_filters('translation_data_for_post', $result, $post->ID);

    return $result;
  }

  /**
   * @param $targetPost
   * @param $translationData
   */
  public function saveTranslatedData($targetPost, $translationData)
  {
    foreach ($translationData as $id => $texts) {
      if (!isset($this->textAccessors[$id])) {
        continue;
      }

      $textAccessors = $this->textAccessors[$id];
      $textAccessors->setTexts($targetPost, $texts);
    }
  }

  /**
   * @param $sourcePost
   * @param $targetPost
   */
  public function prepareTargetPost($sourcePost, $targetPost)
  {
    foreach ($this->textAccessors as $id => $textAccessor) {
      if ($textAccessor instanceof ITranslationAware) {
        $textAccessor->prepareTargetPost($sourcePost, $targetPost);
      }
    }
  }
}