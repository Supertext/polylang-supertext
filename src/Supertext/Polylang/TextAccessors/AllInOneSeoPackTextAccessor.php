<?php

namespace Supertext\Polylang\TextAccessors;

use Supertext\Polylang\Helper\Constant;

class AllInOneSeoPackTextAccessor extends AbstractPluginCustomFieldsTextAccessor implements IAddDefaultSettings
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