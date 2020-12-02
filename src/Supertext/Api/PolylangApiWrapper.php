<?php

namespace Supertext\Api;

/**
 * Class PolylangApiWrapper
 * @package Supertext\Api
 */
class PolylangApiWrapper implements IMultilang
{
    private $newPostUrls = array();

    public function __construct()
    {
        add_filter('pll_get_new_post_translation_link', array($this, 'addNewPostUrl'), 100, 3);
    }

    /**
     * Add new post url for language and post
     */
    public function addNewPostUrl($link, $language, $post_id)
    {
        if (!isset($this->newPostUrls[$post_id])) {
            $this->newPostUrls[$post_id] = array();
        }

        $this->newPostUrls[$post_id][$language->slug] = urldecode(html_entity_decode($link));

        return $link;
    }

    /**
     * Get all configured languages
     * @return list of languages
     */
    public function getLanguages()
    {
        if (function_exists('pll_languages_list')) {
            return pll_languages_list(
                array('fields' => array())
            );
        }

        return array();
    }

    /**
     * Get the language of a post
     * @param int $postId the post id
     * @return string language of the post or false if not found
     */
    public function getPostLanguage($postId)
    {
        if (function_exists('pll_get_post_language')) {
            return pll_get_post_language($postId);
        }

        return false;
    }

    /**
     * Get the language of a term
     * @param $termId
     * @return string language of the term or false if not found
     */
    public function getTermLanguage($termId)
    {
        if (function_exists('pll_get_term_language')) {
            return pll_get_term_language($termId);
        }

        return false;
    }

    /**
     * Get the post in a specific language
     * @param int $postId the post in for example german
     * @param string $language the language of the translation you want (i.e. en)
     * @return int|null post id or null if not found
     */
    public function getPostInLanguage($postId, $language)
    {
        if (function_exists('pll_get_post')) {
            return pll_get_post($postId, $language);
        }

        return null;
    }

    /**
     * Get the term in a specific language
     * @param int $termId the term id
     * @param string $language the language of the translation you want
     * @return false|int|null term id or null if not found
     */
    public function getTermInLanguage($termId, $language)
    {
        if (function_exists('pll_get_term')) {
            return pll_get_term($termId, $language);
        }

        return null;
    }

    /**
     * Get the urls for creating a new post for the different languages
     * @return array two dimensional array with [post id][language code] to url mapping
     */
    public function getNewPostUrls()
    {
        return $this->newPostUrls;
    }

    /**
     * Assign a language to a target post
     * @param $targetPostId target post id
     */
    public function assignLanguageToNewTargetPost(
        $sourcePostId,
        $targetPostId,
        $targetLanguage
    ) {
        $this->setPostLanguage($targetPostId, $targetLanguage);

        $postsLanguageMappings = array(
            $targetLanguage => $targetPostId
        );

        foreach ($this->getLanguages() as $language) {
            $languagePostId = $this->getPostInLanguage($sourcePostId, $language->slug);
            if ($languagePostId) {
                $postsLanguageMappings[$language->slug] = $languagePostId;
            }
        }

        $this->savePostTranslations($postsLanguageMappings);
    }

    /**
     * Assign a language to a target term
     * @param $targetTermId target term id
     */
    public function assignLanguageToNewTargetTerm(
        $sourceTermId,
        $targetTermId,
        $targetLanguage,
        $taxonomy
    ) {
        $this->setTermLanguage($targetTermId, $targetLanguage);

        $termsLanguageMappings = array(
            $targetLanguage => $targetTermId
        );

        foreach ($this->getLanguages() as $language) {
            $languageTermId = $this->getTermInLanguage($sourceTermId, $language->slug);
            if ($languageTermId) {
                $termsLanguageMappings[$language->slug] = $languageTermId;
            }
        }

        $this->saveTermTranslations($termsLanguageMappings);
    }

    /**
     * Set the language of a post
     * @param $postId
     * @param $language
     * @param $trid
     */
    private function setPostLanguage($postId, $language, $trid = false)
    {
        if (function_exists('pll_set_post_language')) {
            pll_set_post_language($postId, $language);
        }
    }

    /**
     * Set the language of a term
     * @param $termId
     * @param $language
     */
    private function setTermLanguage($termId, $language)
    {
        if (function_exists('pll_set_term_language')) {
            pll_set_term_language($termId, $language);
        }
    }

    /**
     * Save the translation settings for a post
     * @param array $arr an associative array of translations with language code as key and post id as value
     */
    private function savePostTranslations($arr)
    {
        if (function_exists('pll_save_post_translations')) {
            pll_save_post_translations($arr);
        }
    }

    /**
     * Save the translation settings for a term
     * @param array $arr an associative array of translations with language code as key and term id as value
     */
    private function saveTermTranslations($arr)
    {
        if (function_exists('pll_save_term_translations')) {
            pll_save_term_translations($arr);
        }
    }
}
