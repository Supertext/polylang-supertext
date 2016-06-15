<?php

namespace Supertext\Polylang\Backend;

/**
 * Processes post content
 * @package Supertext\Polylang\Backend
 */
class ContentProvider
{
  /**
   * @var array|null the text processors
   */
  private $textAccessors = null;
  private $library;

  public function __construct($textAccessors, $library)
  {
    $this->textAccessors = $textAccessors;
    $this->library = $library;
  }

  public function getTranslatableFieldGroups($postId)
  {
    $result = array();

    foreach ($this->textAccessors as $id => $textAccessor) {
      $result[$id] = $textAccessor->getTranslatableFields($postId);
    }

    // Let developers add their own translatable items
    $result = apply_filters('translation_fields_for_post', $result, $postId);

    return $result;
  }

  public function getTranslationData($post, $selectedTranslatableFieldGroups)
  {
    $result = array();

    foreach ($this->textAccessors as $id => $textAccessor) {
      $texts = $textAccessor->getTexts($post, $selectedTranslatableFieldGroups[$id]);

      if(count($texts) === 0){
        continue;
      }

      $result[$id] = $texts;
    }

    // Let developers add their own fields
    $result = apply_filters('translation_data_for_post', $result, $post->ID);

    return $result;
  }

  public function SaveTranslatedData($post, $translationPost, $json)
  {
    foreach ($json->Groups as $translationGroup) {
      if (isset($this->textAccessors[$translationGroup->GroupId])) {
        $textAccessor = $this->textAccessors[$translationGroup->GroupId];

        $textAccessor->prepareTranslationPost($post, $translationPost);

        $texts = array();

        foreach ($translationGroup->Items as $translationItem) {
          $texts[$translationItem->Id] = $translationItem->Content;
        }

        $textAccessor->setTexts($translationPost, $texts);
      }
    }
  }
}