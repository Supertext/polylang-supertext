<?php

namespace Supertext\Polylang\TextAccessors;


use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\Library;
use Supertext\Polylang\Helper\TextProcessor;

class DiviBuilderTextAccessor extends PostTextAccessor implements IMetaDataAware, IAddDefaultSettings
{
  /**
   * @var Library library
   */
  private $library;

  /**
   * @param TextProcessor $textProcessor
   * @param $library
   */
  public function __construct($textProcessor, $library)
  {
    parent::__construct($textProcessor);

    $this->library = $library;
  }

  /**
   * Adds default settings
   */
  public function addDefaultSettings()
  {
    $shortcodeSettings = $this->library->getSettingOption(Constant::SETTING_SHORTCODES);

    $shortcodeSettings['et_pb_[^\s|\]]+'] = array(
      'content_encoding' => null,
      'attributes' => array(
        array('name' => 'more_text', 'encoding' => ''),
        array('name' => 'alt', 'encoding' => ''),
        array('name' => 'title_text', 'encoding' => ''),
        array('name' => 'title', 'encoding' => ''),
        array('name' => 'button_one_text', 'encoding' => ''),
        array('name' => 'button_two_text', 'encoding' => ''),
        array('name' => 'logo_alt_text', 'encoding' => ''),
        array('name' => 'logo_title', 'encoding' => ''),
        array('name' => 'prev_text', 'encoding' => ''),
        array('name' => 'next_text', 'encoding' => ''),
        array('name' => 'name', 'encoding' => ''),
        array('name' => 'button_text', 'encoding' => ''),
        array('name' => 'job_title', 'encoding' => ''),
        array('name' => 'heading', 'encoding' => ''),
        array('name' => 'title1_overlay', 'encoding' => ''),
        array('name' => 'title2_overlay', 'encoding' => '')
      )
    );

    $this->library->saveSettingOption(Constant::SETTING_SHORTCODES, $shortcodeSettings);
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getContentMetaData($post, $selectedTranslatableFields)
  {
    return array(
      '_et_pb_use_builder' => get_post_meta($post->ID, '_et_pb_use_builder', true),
      '_et_pb_ab_bounce_rate_limit' => get_post_meta($post->ID, '_et_pb_ab_bounce_rate_limit', true),
      '_et_pb_ab_stats_refresh_interval' => get_post_meta($post->ID, '_et_pb_ab_stats_refresh_interval', true),
      '_et_pb_enable_shortcode_tracking' => get_post_meta($post->ID, '_et_pb_enable_shortcode_tracking', true),
      '_et_pb_custom_css' => get_post_meta($post->ID, '_et_pb_custom_css', true),
      '_et_pb_light_text_color' => get_post_meta($post->ID, '_et_pb_light_text_color', true),
      '_et_pb_dark_text_color' => get_post_meta($post->ID, '_et_pb_dark_text_color', true),
      '_et_pb_content_area_background_color' => get_post_meta($post->ID, '_et_pb_content_area_background_color', true),
      '_et_pb_section_background_color' => get_post_meta($post->ID, '_et_pb_section_background_color', true),
    );
  }

  /**
   * @param $post
   * @param $translationMetaData
   */
  public function setContentMetaData($post, $translationMetaData)
  {
    foreach($translationMetaData as $key => $value){
      update_post_meta($post->ID, $key, $value);
    }
  }
}