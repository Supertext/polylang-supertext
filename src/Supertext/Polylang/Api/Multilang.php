<?php

namespace Supertext\Polylang\Api;

/**
 * This wraps the polylang multilang functions so nothing crashes if polylang is deactivated
 * @package Supertext\Polylang\Api
 * @author Michael Sebel <michael@comotive.ch>
 */
class Multilang
{
  /**
   * Get all in polylang configured languages
   * @return \PLL_Language[] list of languages
   */
  public static function getLanguages()
  {
    if (function_exists('pll_languages_list')) {
      return pll_languages_list(
        array('fields' => array())
      );
    }

    return array();
  }

  /**
   * @param int $postId the post id
   * @return string language of the post or false if not found
   */
  public static function getPostLanguage($postId)
  {
    if (function_exists('pll_get_post_language')) {
      return pll_get_post_language($postId);
    }

    return false;
  }

  /**
   * @param $termId
   * @return string language of the term or false if not found
   */
  public static function getTermLanguage($termId)
  {
    if (function_exists('pll_get_term_language')) {
      return pll_get_term_language($termId);
    }

    return false;
  }

  /**
   * @param int $postId the post in for example german
   * @param string $language the language of the translation you want (i.e. en)
   * @return int|null post id or null if not found
   */
  public static function getPostInLanguage($postId, $language)
  {
    if (function_exists('pll_get_post')) {
      return pll_get_post($postId, $language);
    }

    return null;
  }

  /**
   * @param int $termId the term id
   * @param string $language the language of the translation you want
   * @return false|int|null term id or null if not found
   */
  public static function getTermInLanguage($termId, $language)
  {
    if (function_exists('pll_get_term')) {
      return pll_get_term($termId, $language);
    }

    return null;
  }

  /**
   * @param $postId
   * @param $language
   */
  public static function setPostLanguage($postId, $language){
    if (function_exists('pll_set_post_language')) {
      pll_set_post_language($postId, $language);
    }
  }

  /**
   * @param $termId
   * @param $language
   */
  public static function setTermLanguage($termId, $language){
    if (function_exists('pll_set_term_language')) {
      pll_set_term_language($termId, $language);
    }
  }

  /**
   * @param array $arr an associative array of translations with language code as key and post id as value
   */
  public static function savePostTranslations($arr){
    if (function_exists('pll_save_post_translations')) {
      pll_save_post_translations($arr);
    }
  }

  /**
   * @param array $arr an associative array of translations with language code as key and term id as value
   */
  public static function saveTermTranslations($arr){
    if (function_exists('pll_save_term_translations')) {
      pll_save_term_translations($arr);
    }
  }
} 