<?php

namespace Supertext\Polylang\Helper;

class BePageBuilderContentAccessor extends AbstractPluginCustomFieldsContentAccessor
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