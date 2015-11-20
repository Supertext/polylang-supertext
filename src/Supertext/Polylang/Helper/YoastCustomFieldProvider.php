<?php

namespace Supertext\Polylang\Helper;

/**
 * The ACF Custom Field provider
 * @package Supertext\Polylang\Helper
 */
class YoastCustomFieldProvider implements ICustomFieldProvider
{
  const PLUGIN_NAME = 'WP SEO by Yoast';

  public function getPluginName()
  {
    return self::PLUGIN_NAME;
  }

  /**
   * @return array multidimensional list of custom fields definitions
   */
  public function getCustomFieldDefinitions()
  {
    $customFields =  array(
      array(
        'id' => '_yoast_wpseo_title',
        'label' => __('SEO-optimized title', 'polylang-supertext'),
        'type' => 'field',
        'meta_key_regex' => '_yoast_wpseo_title'
      ),
      array(
        'id' => '_yoast_wpseo_metadesc',
        'label' => __('SEO-optimized description', 'polylang-supertext'),
        'type' => 'field',
        'meta_key_regex' => '_yoast_wpseo_metadesc'
      ),
      array(
        'id' => '_yoast_wpseo_focuskw',
        'label' => __('Focus keywords', 'polylang-supertext'),
        'type' => 'field',
        'meta_key_regex' => '_yoast_wpseo_focuskw'
      ),
      array(
        'id' => '_yoast_wpseo_opengraph-title',
        'label' => __('Facebook title', 'polylang-supertext'),
        'type' => 'field',
        'meta_key_regex' => '_yoast_wpseo_opengraph-title'
      ),
      array(
        'id' => '_yoast_wpseo_opengraph-description',
        'label' => __('Facebook description', 'polylang-supertext'),
        'type' => 'field',
        'meta_key_regex' => '_yoast_wpseo_opengraph-description'
      )
    );

    return $customFields;
  }
}