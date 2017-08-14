<?php

namespace Supertext\Polylang\TextAccessors;
use Supertext\Polylang\Api\Multilang;

/**
 * Class PostTaxonomyTextAccessor
 * @package Supertext\Polylang\TextAccessors
 */
class PostTaxonomyTextAccessor implements ITextAccessor
{
   public function __construct()
   {
     $this->knownTranslatableTaxonomies = array(
       'category' => __('Categories', 'polylang-supertext'),
       'post_tag' => __('Tags', 'polylang-supertext'),
       'product_cat' => __('Categories', 'polylang-supertext'),
       'product_tag' => __('Tags', 'polylang-supertext'),
     );

   }

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

    foreach($this->knownTranslatableTaxonomies as $name => $title)
    {
      $terms = wp_get_object_terms($postId, $name);
      if(is_wp_error($terms) || !count($terms)) {
        continue;
      }

      $translatableFields[] = array(
        'title' => $title,
        'name' => $name,
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
    $selectedTranslatableFields = array();
    foreach($this->knownTranslatableTaxonomies as $name => $title)
    {
      $selectedTranslatableFields[$name] = true;
    }

    return $this->getTexts($post, $selectedTranslatableFields);
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    foreach($selectedTranslatableFields as $name => $selected){
      if(!$selected){
        continue;
      }

      $terms = wp_get_object_terms($post->ID, $name);
      if(is_wp_error($terms)) {
        continue;
      }

      foreach($terms as $term){
        $texts[$name][$term->term_id] = $term->name;
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
