<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\TextProcessor;
use Supertext\Polylang\Helper\PostTextAccessor;
use Supertext\Polylang\Helper\PostMediaTextAccessor;
use Supertext\Polylang\Helper\AcfTextAccessor;
use Supertext\Polylang\Helper\BeaverBuilderTextAccessor;

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

  /**
   * @param $post
   * @param array $userSelection user selection of items to be translated
   * @return array translation data
   * @internal param int $postId the post id to get data for
   */
  public function getTranslationData($post, $userSelection)
  {
    $result = array();

    foreach ($this->textAccessors as $id => $textAccessor) {
      $result[$id] = $textAccessor->getTexts($post, $userSelection);
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