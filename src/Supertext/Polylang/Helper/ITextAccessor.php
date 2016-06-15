<?php

namespace Supertext\Polylang\Helper;


interface ITextAccessor
{
  public function getTranslatableFields($postId);

  public function getTexts($post, $selectedTranslatableFields);

  public function setTexts($post, $texts);

  public function prepareTranslationPost($post, $translationPost);
}