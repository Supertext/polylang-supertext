<?php

namespace Supertext\Polylang\Helper;


class PluginFieldDefinitions
{
  public static function getYoastSeoFieldDefinitions()
  {
    return array(
      'label' => 'Yoast SEO',
      'type' => 'group',
      'sub_field_definitions' => array(
        '_yoast_wpseo_title' => array(
          'label' => __('SEO-optimized title', 'polylang-supertext'),
          'type' => 'field'
        ),
        '_yoast_wpseo_metadesc' => array(
          'label' => __('SEO-optimized description', 'polylang-supertext'),
          'type' => 'field'
        ),
        '_yoast_wpseo_focuskw' => array(
          'label' => __('Focus keywords', 'polylang-supertext'),
          'type' => 'field'
        ),
        '_yoast_wpseo_opengraph-title' => array(
          'label' => __('Facebook title', 'polylang-supertext'),
          'type' => 'field'
        ),
        '_yoast_wpseo_opengraph-description' => array(
          'label' => __('Facebook description', 'polylang-supertext'),
          'type' => 'field'
        )
      )
    );
  }
}