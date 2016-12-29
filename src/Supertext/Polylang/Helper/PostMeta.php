<?php

namespace Supertext\Polylang\Helper;


class PostMeta
{
  const TRANSLATION_PROPERTIES = '_sttr_translation_properties';
  const IN_TRANSLATION = 'inTranslation';
  const IN_TRANSLATION_REFERENCE_HASH = 'inTranslationRefHash';
  const SOURCE_LANGUAGE_CODE = 'sourceLanguageCode';
  const TRANSLATION_DATE = "translationDate";
  const TRANSLATION_DATA = "translationData";

  private $postId;
  private $translationProperties;

  private function __construct($postId, $translationProperties)
  {
    $this->postId = $postId;
    $this->translationProperties = $translationProperties;
  }

  public static function from($postId)
  {
    $translationProperties = get_post_meta($postId, self::TRANSLATION_PROPERTIES, true);

    if(empty($translationProperties)){
      $translationProperties = array(
        self::IN_TRANSLATION => false,
        self::IN_TRANSLATION_REFERENCE_HASH => '',
        self::SOURCE_LANGUAGE_CODE => '',
        self::TRANSLATION_DATE => '',
        self::TRANSLATION_DATA => ''
      );
    }

    return new PostMeta($postId, $translationProperties);
  }

  public function is($key)
  {
    return isset($this->translationProperties[$key]) && $this->translationProperties[$key] === true;
  }

  public function get($key)
  {
    return isset($this->translationProperties[$key]) ? $this->translationProperties[$key] : null;
  }

  public function getPublicProperties()
  {
    return array(
      self::IN_TRANSLATION => $this->is(self::IN_TRANSLATION),
      self::SOURCE_LANGUAGE_CODE => $this->translationProperties[self::SOURCE_LANGUAGE_CODE]
    );
  }

  public function set($key, $value)
  {
    $this->translationProperties[$key] = $value;
    update_post_meta($this->postId, self::TRANSLATION_PROPERTIES, $this->translationProperties);
  }
}