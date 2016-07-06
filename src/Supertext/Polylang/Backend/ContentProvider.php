<?php

namespace Supertext\Polylang\Backend;
use Supertext\Polylang\Helper\IContentAccessor;
use Supertext\Polylang\Helper\ITranslationAware;

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

  /**
   * @var \Supertext\Polylang\Helper\Library
   */
  private $library;

  /**
   * @param IContentAccessor[] $contentAccessors
   * @param \Supertext\Polylang\Helper\Library $library
   */
  public function __construct($contentAccessors, $library)
  {
    $this->contentAccessors = $contentAccessors;
    $this->library = $library;
  }

  /**
   * @param $postId
   * @return array|mixed|void
   */
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

  /**
   * @param $post
   * @param $selectedTranslatableFieldGroups
   * @return array|mixed|void
   */
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

  /**
   * @param $post
   * @param $translationPost
   * @param $json
   */
  public function saveTranslatedData($post, $translationPost, $json)
  {
    foreach ($json->Groups as $translationGroup) {
      if (isset($this->contentAccessors[$translationGroup->GroupId])) {
        $contentAccessors = $this->contentAccessors[$translationGroup->GroupId];

        $texts = array();

        foreach ($translationGroup->Items as $translationItem) {
          $texts[$translationItem->Id] = $translationItem->Content;
        }

        $contentAccessors->setTexts($translationPost, $texts);
      }
    }
  }

  /**
   * @param $post
   * @param $translationPost
   */
  public function prepareTranslationPost($post, $translationPost)
  {
    foreach ($this->contentAccessors as $id => $contentAccessor) {
      if($contentAccessor instanceof ITranslationAware){
        $contentAccessor->prepareTranslationPost($post, $translationPost);
      }
    }
  }
}