<?php

namespace Supertext\Polylang\Api;

/**
 * This wraps the polylang multilang functions so nothing crashes if polylang is deactivated
 * @package Supertext\Polylang\Api
 * @author Michael Sebel <michael@comotive.ch>
 */
class Multilang
{

  private static $multilangApi = null;

  /**
   * Get all in polylang configured languages
   * @return \PLL_Language[] list of languages
   */
  public static function getLanguages()
  {
    return self::getMultilangApi()->getLanguages();
  }

  /**
   * @param int $postId the post id
   * @return string language of the post or false if not found
   */
  public static function getPostLanguage($postId)
  {
    return self::getMultilangApi()->getPostLanguage($postId);
  }

  /**
   * @param $termId
   * @return string language of the term or false if not found
   */
  public static function getTermLanguage($termId)
  {
    return self::getMultilangApi()->getTermLanguage($termId);
  }

  /**
   * @param int $postId the post in for example german
   * @param string $language the language of the translation you want (i.e. en)
   * @return int|null post id or null if not found
   */
  public static function getPostInLanguage($postId, $language)
  {
    return self::getMultilangApi()->getPostInLanguage($postId, $language);
  }

  /**
   * @param int $termId the term id
   * @param string $language the language of the translation you want
   * @return false|int|null term id or null if not found
   */
  public static function getTermInLanguage($termId, $language)
  {
    return self::getMultilangApi()->getTermInLanguage($termId, $language);
  }

  /**
   * Get the urls for creating a new post for the different languages
   * @return array two dimensional array with [post id][language code] to url mapping
   */
  public static function getNewPostUrls()
  {
    return self::getMultilangApi()->getNewPostUrls();
  }

  /**
   * @param $postId
   * @param $language
   * @param $trid
   */
  public static function setPostLanguage($postId, $language, $trid = false)
  {
    self::getMultilangApi()->setPostLanguage($postId, $language, $trid);
  }

  /**
   * @param $termId
   * @param $language
   */
  public static function setTermLanguage($termId, $language)
  {
    self::getMultilangApi()->setTermLanguage($termId, $language);
  }

  /**
   * @param array $arr an associative array of translations with language code as key and post id as value
   */
  public static function savePostTranslations($arr)
  {
    self::getMultilangApi()->savePostTranslations($arr);
  }

  /**
   * @param array $arr an associative array of translations with language code as key and term id as value
   */
  public static function saveTermTranslations($arr)
  {
    self::getMultilangApi()->saveTermTranslations($arr);
  }

  /**
   * Get whether WPML is active or not
   */
  public static function isWpmlActive()
  {
    return (function_exists('icl_object_id') && is_plugin_active('sitepress-multilingual-cms/sitepress.php'));
  }

  /**
   * @return \Supertext\Polylang\Api\IMultilangApi
   */
  private static function getMultilangApi()
  {
    if (self::$multilangApi == null) {
      self::$multilangApi = self::isWpmlActive() ? new WPMLApiWrapper() : new PolylangApiWrapper();
    }

    return self::$multilangApi;
  }
}
