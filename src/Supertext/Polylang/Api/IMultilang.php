<?php

namespace Supertext\Polylang\Api;

interface IMultilang
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
   * Assign a language to a target post
   * @param $targetPostId target post id
   */
  public function assignLanguageToNewTargetPost(
    $sourcePostId,
    $targetPostId,
    $targetLanguage
  );

  /**
   * Assign a language to a target term
   * @param $targetTermId target term id
   */
  public function assignLanguageToNewTargetTerm(
    $sourceTermId,
    $targetTermId,
    $targetLanguage,
    $taxonomy
  );
}
