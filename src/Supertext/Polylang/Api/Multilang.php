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
   * @param int $postId the post in for example german
   * @param string $language the language of the translation you want (i.e. en)
   * @return int|null post id or null if not found
   */
  public static function getPostInLanguage($postId, $language)
  {
    if (function_exists('pll_get_post')) {
      return pll_get_post($postId, $language);
    }
  }

  /**
   * @param int $post_id post id
   * @param string $lang language code
   */
  public static function setPostLanguage($postId, $language){
    if (function_exists('pll_set_post_language')) {
      return pll_set_post_language($postId, $language);
    }
  }

  /**
   * @param array $arr an associative array of translations with language code as key and post id as value
   */
  public static function savePostTranslations($arr){
    if (function_exists('pll_save_post_translations')) {
      return pll_save_post_translations($arr);
    }
  }
} 