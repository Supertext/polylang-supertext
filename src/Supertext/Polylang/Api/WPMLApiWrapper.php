<?php

namespace Supertext\Polylang\Api;

/**
 * Class WPMLApiWrapper
 * @package Supertext\Polylang\Api
 */
class WPMLApiWrapper implements IMultilangApi
{
    private $newPostUrls = array();

    public function __construct()
    {
        add_filter('wpml_link_to_translation', array($this, 'addNewPostUrl'), 100, 3);
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
     * Set the language of a post
     * @param $postId
     * @param $language
     * @param $trid
     */
    public function setPostLanguage($postId, $language, $trid = false)
    {
        $postType = get_post_type($postId);
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
    public function setTermLanguage($termId, $language)
    {
        $termType = get_term_by('id', $termId)->taxonomy;
        do_action('wpml_set_element_language_details', array(
            'element_id' => $termId,
            'element_type' => 'tax_' . $termType,
            'trid' => false,
            'language_code' => $language
        ));
        // for args explanation visit: https://wpml.org/wpml-hook/wpml_set_element_language_details/
    }

    /**
     * Save the translation settings for a post
     * @param array $arr an associative array of translations with language code as key and post id as value
     */
   public function savePostTranslations($arr)
    {
        /* foreach ($arr as $langCode => $postId) {
            $postTrid = apply_filters('wpml_element_trid', null, $postId);
            $postType = get_post_type($postId);

            $postSrcLang = apply_filters('wpml_element_language_details',  null, array('element_id' => $postId, 'element_type' => $postType));
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'icl_translations',
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
        // source: https://wordpress.stackexchange.com/questions/20143/plugin-wpml-how-to-create-a-translation-of-a-post-using-the-wpml-api
        // source: https://wordpress.stackexchange.com/questions/147652/how-to-update-records-using-wpdb*/
    }

    /**
     * Save the translation settings for a term
     * @param array $arr an associative array of translations with language code as key and term id as value
     */
    public function saveTermTranslations($arr)
    {
        foreach ($arr as $langCode => $termId) {
            $termTrid = apply_filters('wpml_element_trid', null, $termId);
            $tridSrcLang = apply_filters('wpml_element_language_details',  null, array('element_id' => $termId, 'element_type' => 'tax_category'))->source_language_code;
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'icl_translations',
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

    /**
     * Assign a language to a target post using query strings as parameters
     * @param $targetPostId target post id
     */
    public function assignLanguageToNewTargetPost($targetPostId)
    {
        $trid = $_GET['trid'];
        $targetLanguage = $_GET['lang'];

        $this->setPostLanguage($targetPostId, $targetLanguage, $trid);
    }
}
