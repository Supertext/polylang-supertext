<?php

namespace Supertext\Polylang\Helper;

/**
 * Interface ITranslationAware
 * @package Supertext\Polylang\Helper
 */
interface ITranslationAware
{
  public function prepareTranslationPost($post, $translationPost);
}