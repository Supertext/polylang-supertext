<?php

namespace Supertext\Helper;

/**
 * Class TranslationMeta
 * @package Supertext\Helper
 */
class TranslationMeta extends PostMeta implements IWriteBackMeta
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

  public function getOrderType()
  {
    return self::TRANSLATION;
  }

  public function getReferenceHash()
  {
    return $this->get(self::IN_TRANSLATION_REFERENCE_HASH);
  }

  public function getContentMetaData()
  {
    return $this->get(self::META_DATA);
  }

  public function getSuccessLogEntry()
  {
    return __('translation saved successfully', 'supertext');
  }

  public function isInProgress()
  {
    return $this->is(self::IN_TRANSLATION);
  }

  public function markAsComplete()
  {
    // All good, set translation flag false
    $this->set(self::IN_TRANSLATION, false);
    $this->set(self::TRANSLATION_DATE, get_post_field('post_modified', $this->postId));
  }
}
