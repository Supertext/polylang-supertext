<?php

namespace Supertext\TextAccessors;

/**
 * Interface ITextAccessor
 * @package Supertext\TextAccessors
 */
interface ITextAccessor
{
  public function getName();

  public function getTranslatableFields($postId);

  public function getRawTexts($post);

  public function getTexts($post, $selectedTranslatableFields);

  public function setTexts($post, $texts);
}