<?php

namespace Supertext\Polylang\Helper;

/**
 * Interface ITranslationAware
 * @package Supertext\Polylang\Helper
 */
interface ITranslationAware
{
  public function prepareTargetPost($sourcePost, $targetPost);
}