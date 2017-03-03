<?php

namespace Supertext\Polylang\TextAccessors;

use Supertext\Polylang\Helper\Constant;

class BePageBuilderTextAccessor extends AbstractPluginCustomFieldsTextAccessor implements IAddDefaultSettings
{
  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName()
  {
    return __('BE page builder (Plugin)', 'polylang-supertext');
  }

  /**
   * Adds default settings
   */
  public function addDefaultSettings()
  {
    $shortcodeSettings = $this->library->getSettingOption(Constant::SETTING_SHORTCODES);

    $shortcodeSettings['special_heading\d?'] = array(
      'content_encoding' => null,
      'attributes' => array(
        array('name' => 'title_content', 'encoding' => '')
      )
    );

    $shortcodeSettings['button'] = array(
      'content_encoding' => null,
      'attributes' => array(
        array('name' => 'button_text', 'encoding' => '')
      )
    );

    $this->library->saveSettingOption(Constant::SETTING_SHORTCODES, $shortcodeSettings);

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
        'label' => 'BE page builder',
        'type' => 'group',
        'sub_field_definitions' => array(
          array(
            'id' => 'field_be_pb_content',
            'label' => __('Content', 'polylang-supertext'),
            'type' => 'field',
            'meta_key_regex' => '_be_pb_content'
          )
        )
      )
    );
  }
}