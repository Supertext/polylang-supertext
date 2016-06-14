<?php

namespace Supertext\Polylang\Helper;


class AcfTextAccessor implements ITextAccessor
{
  /**
   * @var
   */
  private $textProcessor;

  public function __construct($textProcessor)
  {
    $this->textProcessor = $textProcessor;
  }

  public function getTexts($post, $userSelection)
  {

  }

  public function setTexts($post, $texts)
  {


  }

  public function prepareTranslationPost($post, $translationPost)
  {

  }
}