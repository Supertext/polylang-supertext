<?php

namespace Supertext\Polylang\Helper;


interface ITranslationAware
{
  public function prepareTranslationPost($post, $translationPost);
}