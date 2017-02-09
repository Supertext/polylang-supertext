<?php

namespace Supertext\Polylang\TextAccessors;

/**
 * Interface ITextAccessor
 * @package Supertext\Polylang\TextAccessors
 */
interface ITextAccessor
{
  public function getName();

  public function getTranslatableFields($postId);

  public function getRawTexts($post);

  public function getTexts($post, $selectedTranslatableFields);

  public function setTexts($post, $texts);
}