<?php

namespace Supertext\Polylang\Helper;


class DiviBuilderContentAccessor extends PostContentAccessor implements ITranslationAware
{
  /**
   * @param TextProcessor $textProcessor
   */
  public function __construct($textProcessor)
  {
    parent::__construct($textProcessor);
  }

  public function prepareTargetPost($sourcePost, $targetPost)
  {
    update_post_meta($targetPost->ID, '_et_pb_use_builder', get_post_meta($sourcePost->ID, '_et_pb_use_builder', true));
    update_post_meta($targetPost->ID, '_et_pb_ab_bounce_rate_limit', get_post_meta($sourcePost->ID, '_et_pb_ab_bounce_rate_limit', true));
    update_post_meta($targetPost->ID, '_et_pb_ab_stats_refresh_interval', get_post_meta($sourcePost->ID, '_et_pb_ab_stats_refresh_interval', true));
    update_post_meta($targetPost->ID, '_et_pb_enable_shortcode_tracking', get_post_meta($sourcePost->ID, '_et_pb_enable_shortcode_tracking', true));
    update_post_meta($targetPost->ID, '_et_pb_custom_css', get_post_meta($sourcePost->ID, '_et_pb_custom_css', true));
    update_post_meta($targetPost->ID, '_et_pb_light_text_color', get_post_meta($sourcePost->ID, '_et_pb_light_text_color', true));
    update_post_meta($targetPost->ID, '_et_pb_dark_text_color', get_post_meta($sourcePost->ID, '_et_pb_dark_text_color', true));
    update_post_meta($targetPost->ID, '_et_pb_content_area_background_color', get_post_meta($sourcePost->ID, '_et_pb_content_area_background_color', true));
    update_post_meta($targetPost->ID, '_et_pb_section_background_color', get_post_meta($sourcePost->ID, '_et_pb_section_background_color', true));
  }
}