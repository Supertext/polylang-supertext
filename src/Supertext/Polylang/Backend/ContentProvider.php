<?php

namespace Supertext\Polylang\Backend;
use Supertext\Polylang\Helper\IContentAccessor;

/**
 * Processes post content
 * @package Supertext\Polylang\Backend
 */
class ContentProvider
{
  /**
   * @var IContentAccessor[] the text accessors
   */
  private $contentAccessors = null;
  private $library;

  public function __construct($contentAccessors, $library)
  {
    $this->contentAccessors = $contentAccessors;
    $this->library = $library;
  }

  public function getAllTranslatableFields($postId)
  {
    $result = array();

    foreach ($this->contentAccessors as $id => $contentAccessor) {
      $result[$id] = $contentAccessor->getTranslatableFields($postId);
    }

    // Let developers add their own translatable items
    $result = apply_filters('translation_fields_for_post', $result, $postId);

    return $result;
  }

  public function getTranslationData($post, $selectedTranslatableFieldGroups)
  {
    $result = array();

    foreach ($this->contentAccessors as $id => $contentAccessor) {
      if(!isset($selectedTranslatableFieldGroups[$id])){
        continue;
      }

      $texts = $contentAccessor->getTexts($post, $selectedTranslatableFieldGroups[$id]);

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
      if (isset($this->contentAccessors[$translationGroup->GroupId])) {
        $contentAccessors = $this->contentAccessors[$translationGroup->GroupId];

        $contentAccessors->prepareTranslationPost($post, $translationPost);

        $texts = array();

        foreach ($translationGroup->Items as $translationItem) {
          $texts[$translationItem->Id] = $translationItem->Content;
        }

        $contentAccessors->setTexts($translationPost, $texts);
      }
    }
  }
}