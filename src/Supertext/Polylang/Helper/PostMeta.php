<?php

namespace Supertext\Polylang\Helper;


class PostMeta
{
  const TRANSLATION_PROPERTIES = '_sttr_translation_properties';
  const IN_TRANSLATION = 'inTranslation';
  const IN_TRANSLATION_REFERENCE_HASH = 'inTranslationRefHash';
  const SOURCE_LANGUAGE = 'sourceLanguage';

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
        self::SOURCE_LANGUAGE => ''
      );
    }

    return new PostMeta($postId, $translationProperties);
  }

  public function is($key)
  {
    return $this->translationProperties[$key] === true;
  }

  public function get($key)
  {
    return $this->translationProperties[$key];
  }

  public function getPublicProperties()
  {
    return array(
      self::IN_TRANSLATION => $this->is(self::IN_TRANSLATION),
      self::SOURCE_LANGUAGE => $this->translationProperties[self::SOURCE_LANGUAGE]
    );
  }

  public function set($key, $value)
  {
    $this->translationProperties[$key] = $value;
    update_post_meta($this->postId, self::TRANSLATION_PROPERTIES, $this->translationProperties);
  }
}