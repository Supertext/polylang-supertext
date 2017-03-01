<?php

namespace Supertext\Polylang\TextAccessors;

/**
 * Interface ITranslationAware
 * @package Supertext\Polylang\TextAccessors
 */
interface ITranslationAware
{
  /**
   * @param $sourcePostId
   * @param $targetPostId
   * @param $selectedTranslatableFields
   */
  public function getTranslationMetaData($sourcePostId, $targetPostId, $selectedTranslatableFields);

  /**
   * @param $sourcePostId
   * @param $targetPostId
   * @param $translationMetaData
   */
  public function prepareSettingTexts($sourcePostId, $targetPostId, $translationMetaData);
}