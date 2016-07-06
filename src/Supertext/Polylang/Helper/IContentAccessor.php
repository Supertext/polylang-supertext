<?php

namespace Supertext\Polylang\Helper;

/**
 * Interface IContentAccessor
 * @package Supertext\Polylang\Helper
 */
interface IContentAccessor
{
  public function getTranslatableFields($postId);

  public function getTexts($post, $selectedTranslatableFields);

  public function setTexts($post, $texts);
}