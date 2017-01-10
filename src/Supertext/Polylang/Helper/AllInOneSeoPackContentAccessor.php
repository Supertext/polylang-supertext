<?php

namespace Supertext\Polylang\Helper;

class AllInOneSeoPackContentAccessor extends AbstractPluginCustomFieldsContentAccessor
{
  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName()
  {
    return __('All In One SEO Pack (Plugin)', 'polylang-supertext');
  }

  /**
   * @return array
   */
  protected function getFieldDefinitions()
  {
    return array(
      array(
        'id' => 'group_general',
        'label' => 'All in One SEO',
        'type' => 'group',
        'sub_field_definitions' => array(
          array(
            'id' => 'field_aioseop_title',
            'label' => __('SEO title', 'polylang-supertext'),
            'type' => 'field',
            'meta_key_regex' => '_aioseop_title'
          ),
          array(
            'id' => 'field_aioseop_description',
            'label' => __('SEO description', 'polylang-supertext'),
            'type' => 'field',
            'meta_key_regex' => '_aioseop_description'
          ),
          array(
            'id' => 'field_aioseop_keywords',
            'label' => __('SEO keywords', 'polylang-supertext'),
            'type' => 'field',
            'meta_key_regex' => '_aioseop_keywords'
          )
        )
      )
    );
  }
}