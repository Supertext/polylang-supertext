<?php

namespace Supertext\Polylang\Helper;
use Supertext\Polylang\Api\Multilang;

/**
 * Class PostTaxonomyContentAccessor
 * @package Supertext\Polylang\Helper
 */
class PostTaxonomyContentAccessor implements IContentAccessor
{
  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName()
  {
    return __('Taxonomy', 'polylang-supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = array();

    if(count(wp_get_object_terms($postId, 'category'))){
      $translatableFields[] = array(
        'title' => __('Categories', 'polylang-supertext'),
        'name' => 'post_categories',
        'checkedPerDefault' => false
      );
    }

    if(count(wp_get_object_terms($postId, 'post_tag'))) {
      $translatableFields[] = array(
        'title' => __('Tags', 'polylang-supertext'),
        'name' => 'post_tags',
        'checkedPerDefault' => false
      );
    }

    return $translatableFields;
  }

  /**
   * @param $post
   * @return array
   */
  public function getRawTexts($post)
  {
    return $this->getTexts($post, array('post_categories' => true, 'post_tags' => true));
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array('category' => array(), 'post_tag' => array());

    if ($selectedTranslatableFields['post_categories']) {
      $terms = wp_get_object_terms($post->ID, 'category');

      foreach($terms as $term){
        $texts['category'][$term->term_id] = $term->name;
      }
    }

    if ($selectedTranslatableFields['post_tags']) {
      $terms = wp_get_object_terms($post->ID, 'post_tag');

      foreach($terms as $term){
        $texts['post_tag'][$term->term_id] = $term->name;
      }
    }

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    $postLanguage = Multilang::getPostLanguage($post->ID);

    foreach($texts as $taxonomy => $terms){
      foreach($terms as $sourceTermId => $term){
        $translationTermId = Multilang::getTermInLanguage($sourceTermId, $postLanguage);

        if($translationTermId == null)
        {
          $translationTerm = wp_insert_term($term, $taxonomy);

          if($translationTerm instanceof \WP_Error){
            continue;
          }

          $translationTermId = $translationTerm['term_id'];

          $this->SetTermLanguages($translationTermId, $postLanguage, $sourceTermId);
        }else{
          wp_update_term($translationTermId, $taxonomy, array(
            'name' => $term,
            'slug' => sanitize_title($term)
          ));
        }

        wp_set_object_terms($post->ID, array(intval($translationTermId)), $taxonomy, true);
      }
    }
  }

  /**
   * @param $translationTermId
   * @param $postLanguage
   * @param $sourceTermId
   */
  private function SetTermLanguages($translationTermId, $postLanguage, $sourceTermId)
  {
    Multilang::setTermLanguage($translationTermId, $postLanguage);

    $termsLanguageMappings = array(
      $postLanguage => $translationTermId
    );

    foreach (Multilang::getLanguages() as $language) {
      $languageTermId = Multilang::getTermInLanguage($sourceTermId, $language->slug);
      if ($languageTermId) {
        $termsLanguageMappings[$language->slug] = $languageTermId;
      }
    }

    Multilang::saveTermTranslations($termsLanguageMappings);
  }
}