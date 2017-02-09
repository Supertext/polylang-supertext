<?php

namespace Supertext\Polylang\TextAccessors;

/**
 * Interface ITranslationAware
 * @package Supertext\Polylang\TextAccessors
 */
interface ITranslationAware
{
  public function prepareTargetPost($sourcePost, $targetPost);
}