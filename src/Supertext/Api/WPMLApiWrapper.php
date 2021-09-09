<?php

namespace Supertext\Api;

/**
 * Class WPMLApiWrapper
 * @package Supertext\Api
 */
class WPMLApiWrapper implements IMultilang
{
    private $newPostUrls = array();

    public function __construct()
    {
        add_filter('wpml_link_to_translation', array($this, 'addNewPostUrl'), 5, 3);
    }

    /**
     * Add new post url for language and post
     */
    public function addNewPostUrl($link, $post_id, $lang)
    {
        if (!isset($this->newPostUrls[$post_id])) {
            $this->newPostUrls[$post_id] = array();
        }

        $this->newPostUrls[$post_id][$lang] = urldecode(html_entity_decode($link));

        return $link;
    }

    /**
     * Get all configured languages
     * @return list of languages
     */
    public function getLanguages()
    {
        if (function_exists('icl_get_languages')) {
            $languageList = icl_get_languages();
            $langObjs = array();
            foreach ($languageList as $lang) {
                $langObj = array('name' => $lang['native_name'], 'slug' => $lang['code']);
                array_push($langObjs, (object) $langObj);
            }
            return $langObjs;
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
        $postLang = apply_filters(
            'wpml_element_language_code',
            null,
            array('element_id' => $postId, 'element_type' => 'post')
        );

        return $postLang !== null ? $postLang : false;
    }

    /**
     * Get the language of a term
     * @param $termId
     * @return string language of the term or false if not found
     */
    public function getTermLanguage($termId)
    {
        $termTax = get_term($termId)->taxonomy;
        $termLang = apply_filters(
            'wpml_element_language_code',
            null,
            array('element_id' => $termId, 'element_type' => $termTax)
        );

        return $termLang !== null ? $termLang : false;
    }

    /**
     * Get the post in a specific language
     * @param int $postId the post in for example german
     * @param string $language the language of the translation you want (i.e. en)
     * @return int|null post id or null if not found
     */
    public function getPostInLanguage($postId, $language)
    {
        if (function_exists('icl_object_id')) {
            $postType = get_post_type($postId);
            return icl_object_id($postId, $postType, false, $language);
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
        if (function_exists('icl_object_id')) {
            $termTax = get_term($termId)->taxonomy;
            return icl_object_id($termId, $termTax, false, $language);
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
        $postType = get_post_type($sourcePostId);
        $trid = empty($_GET['trid']) ? apply_filters('wpml_element_trid', NULL, $sourcePostId, 'post_' . $postType) : $_GET['trid'];

        $this->setPostLanguage($targetPostId, $postType, $targetLanguage, $trid);
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
        $trid = empty($_GET['trid']) ? apply_filters('wpml_element_trid', NULL, $sourceTermId, 'tax_' . $taxonomy) : $_GET['trid'];

        $this->setTermLanguage($targetTermId, $taxonomy, $targetLanguage, $trid);
    }

    /**
     * Set the language of a post
     * @param $postId
     * @param $language
     * @param $trid
     */
    private function setPostLanguage($postId, $postType, $language, $trid)
    {
        do_action('wpml_set_element_language_details', array(
            'element_id' => $postId,
            'element_type' => 'post_' . $postType,
            'trid' => $trid,
            'language_code' => $language
        ));
        // for args explanation visit: https://wpml.org/wpml-hook/wpml_set_element_language_details/
    }

    /**
     * Set the language of a term
     * @param $termId
     * @param $language
     */
    private function setTermLanguage($termId, $taxonomy, $language, $trid)
    {
        do_action('wpml_set_element_language_details', array(
            'element_id' => $termId,
            'element_type' => 'tax_' . $taxonomy,
            'trid' => $trid,
            'language_code' => $language
        ));
        // for args explanation visit: https://wpml.org/wpml-hook/wpml_set_element_language_details/
    }

    /**
     * Copy the meta data if the wpml translation management plugin is not active
     * @param int $targetPostId the translate post id
     */
    public function copyMetaData($sourcePostId, $targetPostId)
    {
        if (!is_plugin_active('wpml-translation-management/plugin.php')) {
            $metaKeys = array_keys(get_post_meta($sourcePostId));
            foreach ($metaKeys as $metaKey) {
                update_post_meta($targetPostId, $metaKey, get_post_meta($sourcePostId, $metaKey, true));
            }
        }
    }
}
