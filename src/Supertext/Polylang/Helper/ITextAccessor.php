<?php

namespace Supertext\Polylang\Helper;


interface ITextAccessor
{
  public function getTexts($post, $userSelection);

  public function setTexts($post, $texts);

  public function prepareTranslationPost($post, $translationPost);
}