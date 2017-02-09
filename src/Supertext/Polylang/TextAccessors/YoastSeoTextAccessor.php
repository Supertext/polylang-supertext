<?php

namespace Supertext\Polylang\TextAccessors;

use Supertext\Polylang\Helper\Constant;

class YoastSeoTextAccessor extends AbstractPluginCustomFieldsTextAccessor implements IAddDefaultSettings
{
  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName()
  {
    return __('Yoast SEO (Plugin)', 'polylang-supertext');
  }

  /**
   * Adds default settings
   */
  public function addDefaultSettings()
  {
    $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);
    $savedFieldDefinitions[$this->pluginId] = $this->getFieldDefinitions()[0]['sub_field_definitions'];
    $this->library->saveSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS, $savedFieldDefinitions);
  }

  /**
   * @return array
   */
  protected function getFieldDefinitions()
  {
    return array(
      array(
        'id' => 'group_general',
        'label' => 'Yoast SEO',
        'type' => 'group',
        'sub_field_definitions' => array(
          array(
            'id' => 'field_yoast_wpseo_title',
            'label' => __('SEO-optimized title', 'polylang-supertext'),
            'type' => 'field',
            'meta_key_regex' => '_yoast_wpseo_title'
          ),
          array(
            'id' => 'field_yoast_wpseo_metadesc',
            'label' => __('SEO-optimized description', 'polylang-supertext'),
            'type' => 'field',
            'meta_key_regex' => '_yoast_wpseo_metadesc'
          ),
          array(
            'id' => 'field_yoast_wpseo_focuskw',
            'label' => __('Focus keywords', 'polylang-supertext'),
            'type' => 'field',
            'meta_key_regex' => '_yoast_wpseo_focuskw'
          ),
          array(
            'id' => 'field_yoast_wpseo_opengraph-title',
            'label' => __('Facebook title', 'polylang-supertext'),
            'type' => 'field',
            'meta_key_regex' => '_yoast_wpseo_opengraph-title'
          ),
          array(
            'id' => 'field_yoast_wpseo_opengraph-description',
            'label' => __('Facebook description', 'polylang-supertext'),
            'type' => 'field',
            'meta_key_regex' => '_yoast_wpseo_opengraph-description'
          )
        )
      )
    );
  }
}