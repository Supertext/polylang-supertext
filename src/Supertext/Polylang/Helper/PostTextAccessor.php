<?php

namespace Supertext\Polylang\Helper;


class PostTextAccessor implements ITextAccessor
{
  /**
   * @var the text processor
   */
  private $textProcessor;

  public function __construct($textProcessor)
  {

    $this->textProcessor = $textProcessor;
  }

  public function getTexts($post, $userSelection)
  {
    $texts = array();

    if ($userSelection['post_title']) {
      $texts['post_title'] = $post->post_title;
    }

    if ($userSelection['post_content']) {
      $texts['post_content'] = $this->textProcessor->replaceShortcodes($post->post_content);
    }

    if ($userSelection['post_excerpt']) {
      $texts['post_excerpt'] = $post->post_excerpt;
    }

    return $texts;
  }

  public function setTexts($post, $texts)
  {
    foreach ($texts as $id => $text) {
      $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');

      if ($id === 'post_content') {
        $decodedContent = $this->textProcessor->replaceShortcodeNodes($decodedContent);
      }

      $post->{$id} = $decodedContent;
    }
  }

  public function prepareTranslationPost($post, $translationPost)
  {
  }
}