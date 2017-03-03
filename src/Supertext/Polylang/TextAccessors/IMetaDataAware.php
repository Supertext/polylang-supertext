<?php

namespace Supertext\Polylang\TextAccessors;

/**
 * Interface IMetaDataAware
 * @package Supertext\Polylang\TextAccessors
 */
interface IMetaDataAware
{
  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getContentMetaData($post, $selectedTranslatableFields);

  /**
   * @param $post
   * @param $translationMetaData
   */
  public function setContentMetaData($post, $translationMetaData);
}