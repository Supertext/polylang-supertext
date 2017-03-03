<?php

namespace Supertext\Polylang\Helper;

/**
 * Class TranslationMeta
 * @package Supertext\Polylang\Helper
 */
class TranslationMeta extends PostMeta
{
  const TRANSLATION = 'translation';
  const IN_TRANSLATION = 'inTranslation';
  const IN_TRANSLATION_REFERENCE_HASH = 'inTranslationRefHash';
  const SOURCE_LANGUAGE_CODE = 'sourceLanguageCode';
  const TRANSLATION_DATE = "translationDate";
  const META_DATA = "metaData";

  private static $translationProperties = '_sttr_translation_properties';

  public static function of($postId)
  {
    return new TranslationMeta($postId, self::$translationProperties);
  }
}