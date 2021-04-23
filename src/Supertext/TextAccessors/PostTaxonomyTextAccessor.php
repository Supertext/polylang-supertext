<?php

namespace Supertext\TextAccessors;

use Supertext\Helper\Library;

/**
 * Class PostTaxonomyTextAccessor
 * @package Supertext\TextAccessors
 */
class PostTaxonomyTextAccessor implements ITextAccessor
{
  /**
   * @var Library library
   */
  protected $library;

  /**
   * @param Library $library
   */
  public function __construct($library)
  {
    $this->library = $library;
    $this->knownTranslatableTaxonomies = array(
      'category' => __('Categories', 'supertext'),
      'post_tag' => __('Tags', 'supertext'),
      'product_cat' => __('Categories', 'supertext'),
      'product_tag' => __('Tags', 'supertext'),
    );
  }

  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName()
  {
    return __('Taxonomy', 'supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = array();

    foreach ($this->knownTranslatableTaxonomies as $name => $title) {
      $terms = wp_get_object_terms($postId, $name);
      if (is_wp_error($terms) || !count($terms)) {
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
    foreach ($this->knownTranslatableTaxonomies as $name => $title) {
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

    foreach ($selectedTranslatableFields as $name => $selected) {
      if (!$selected) {
        continue;
      }

      $terms = wp_get_object_terms($post->ID, $name);
      if (is_wp_error($terms)) {
        continue;
      }

      foreach ($terms as $term) {
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
    $postLanguage = $this->library->getMultilang()->getPostLanguage($post->ID);

    foreach ($texts as $taxonomy => $terms) {
      foreach ($terms as $sourceTermId => $term) {
        $translationTermId = $this->library->getMultilang()->getTermInLanguage($sourceTermId, $postLanguage);

        if ($translationTermId == null) {
          $translationTerm = wp_insert_term($term, $taxonomy);

          if ($translationTerm instanceof \WP_Error) {
            continue;
          }

          $translationTermId = $translationTerm['term_id'];

          $this->library->getMultilang()->assignLanguageToNewTargetTerm($sourceTermId, $translationTermId, $postLanguage, $taxonomy);

        } else {
          wp_update_term($translationTermId, $taxonomy, array(
            'name' => $term,
            'slug' => sanitize_title($term)
          ));
        }

        wp_set_object_terms($post->ID, array(intval($translationTermId)), $taxonomy, true);
      }
    }
  }
}
