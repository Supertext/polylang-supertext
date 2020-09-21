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

    if(self::is_wpml()){
      $languageList = icl_get_languages();
      $langObjs = array();
      foreach($languageList as $lang){
        $langObj = array('name' => $lang['native_name'], 'slug' => $lang['code']);
        array_push($langObjs, (object) $langObj);
      }
      return $langObjs;
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

    if(self::is_wpml()){
      $postLang = apply_filters(
        'wpml_element_language_code',
        null,
        array( 'element_id'=> $postId , 'element_type'=> 'post')
      );
      if($postLang !== null){
        return $postLang;
      }
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

    if(self::is_wpml()){
      $termTax = get_term($termId)->taxonomy;
      $termLang = apply_filters(
        'wpml_element_language_code',
        null,
        array( 'element_id'=> $termId , 'element_type'=> $termTax)
      );

      if($termLang !== null){
        return $termLang;
      }
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

    if(self::is_wpml()){
      $postType = get_post_type($postId);
      return icl_object_id($postId, $postType, false, $language);
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

    if(self::is_wpml()){
      $termTax = get_term($termId)->taxonomy;
      return icl_object_id($termId, $termTax, false, $language);
    }

    return null;
  }

  /**
   * @param $postId
   * @param $language
   * @param $trid
   */
  public static function setPostLanguage($postId, $language, $trid = false){
    if (function_exists('pll_set_post_language')) {
      pll_set_post_language($postId, $language);
    }

    if(self::is_wpml()){
      $postType = get_post_type($postId);
      do_action( 'wpml_set_element_language_details', array(
        'element_id' => $postId,
        'element_type' => 'post_' . $postType,
        'trid' => $trid,
        'language_code' => $language
      ));
    }
    // for args explanation visit: https://wpml.org/wpml-hook/wpml_set_element_language_details/

  }

  /**
   * @param $termId
   * @param $language
   */
  public static function setTermLanguage($termId, $language){
    if (function_exists('pll_set_term_language')) {
      pll_set_term_language($termId, $language);
    }

    if(self::is_wpml()){
      $termType = get_term_by('id', $termId)->taxonomy;
      do_action( 'wpml_set_element_language_details', array(
        'element_id' => $termId,
        'element_type' => 'tax_' . $termType,
        'trid' => false,
        'language_code' => $language
      ));
      // for args explanation visit: https://wpml.org/wpml-hook/wpml_set_element_language_details/
    }
  }

  /**
   * @param array $arr an associative array of translations with language code as key and post id as value
   */
  public static function savePostTranslations($arr){
    if (function_exists('pll_save_post_translations')) {
      pll_save_post_translations($arr);
    }

    if(self::is_wpml()){
      foreach($arr as $langCode => $postId){
        $postTrid = apply_filters('wpml_element_trid', null, $postId);
        $postType = get_post_type($postId);

        $postSrcLang = apply_filters('wpml_element_language_details',  null, array('element_id' => $postId, 'element_type' => $postType));
        global $wpdb;
        $wpdb->update(
          $wpdb->prefix.'icl_translations',
          array(
            'trid' => $postTrid,
            'language_code' => $langCode,
            'source_language_code' => $postSrcLang
          ),
          array(
            'element_type' => $postType,
            'element_id' => $postId
          )
        );
      }
    }
    // source: https://wordpress.stackexchange.com/questions/20143/plugin-wpml-how-to-create-a-translation-of-a-post-using-the-wpml-api
    // source: https://wordpress.stackexchange.com/questions/147652/how-to-update-records-using-wpdb
  }

  /**
   * @param array $arr an associative array of translations with language code as key and term id as value
   */
  public static function saveTermTranslations($arr){
    if (function_exists('pll_save_term_translations')) {
      pll_save_term_translations($arr);
    }

    if(self::is_wpml()){
      foreach($arr as $langCode => $termId){
        $termTrid = apply_filters('wpml_element_trid', null, $termId);
        $tridSrcLang = apply_filters('wpml_element_language_details',  null, array('element_id' => $termId, 'element_type' => 'tax_category'))->source_language_code;
        global $wpdb;
        $wpdb->update(
          $wpdb->prefix.'icl_translations',
          array(
            'trid' => $termTrid,
            'language_code' => $langCode,
            'source_language_code' => $tridSrcLang
          ),
          array(
            'element_type' => 'tax_category',
            'element_id' => $termId
          )
        );
      }
    }
  }

  /**
   * Check if the CMS has wmpl and if it is active
   * @return bool returns true if wpml is installed and active
   */
  public static function is_wpml(){
    return (function_exists('icl_object_id') && is_plugin_active('sitepress-multilingual-cms/sitepress.php'));
  }

  /**
   * @param $trid translation-post id (same as $original_id)
   * @param $lang_code language code
   * @param $source_language source language code
   * @param $original_id original/source post
   * @return string absolute link to create a translation for a post
   */
  public static function generate_wpml_link( $trid, $lang_code, $source_language, $original_id )
  {
    $link = 'post-new.php?' . http_build_query(
        array(
          'lang' => $lang_code,
          'post_type' => get_post_type($original_id),
          'trid' => $trid,
          'source_lang' => $source_language,
          'from_post' => $original_id,
          'new_lang' => $lang_code
        )
      );

    return admin_url() . $link;
  }
}