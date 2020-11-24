<?php

namespace Supertext\Polylang\Api;

interface IMultilangApi
{
  /**
   * Get all configured languages
   * @return list of languages
   */
  public function getLanguages();

  /**
   * Get the language of a post
   * @param int $postId the post id
   * @return string language of the post or false if not found
   */
  public function getPostLanguage($postId);

  /**
   * Get the language of a term
   * @param $termId
   * @return string language of the term or false if not found
   */
  public function getTermLanguage($termId);

  /**
   * Get the post in a specific language
   * @param int $postId the post in for example german
   * @param string $language the language of the translation you want (i.e. en)
   * @return int|null post id or null if not found
   */
  public function getPostInLanguage($postId, $language);

  /**
   * Get the term in a specific language
   * @param int $termId the term id
   * @param string $language the language of the translation you want
   * @return false|int|null term id or null if not found
   */
  public function getTermInLanguage($termId, $language);

  /**
   * Get the urls for creating a new post for the different languages
   * @return array two dimensional array with [post id][language code] to url mapping
   */
  public function getNewPostUrls();

  /**
   * Set the language of a post
   * @param $postId
   * @param $language
   * @param $trid
   */
  public function setPostLanguage($postId, $language, $trid = false);

  /**
   * Set the language of a term
   * @param $termId
   * @param $language
   */
  public function setTermLanguage($termId, $language);

  /**
   * Save the translation settings for a post
   * @param array $arr an associative array of translations with language code as key and post id as value
   */
  public function savePostTranslations($arr);

  /**
   * Save the translation settings for a term
   * @param array $arr an associative array of translations with language code as key and term id as value
   */
  public function saveTermTranslations($arr);

  /**
   * Assign a language to a target post using query strings as parameters
   * @param $targetPostId target post id
   */
  public function assignLanguageToNewTargetPost($targetPostId);
}