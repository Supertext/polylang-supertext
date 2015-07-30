<?php

namespace Supertext\Polylang\Api;

use Supertext\Polylang\Helper\Constant;

/**
 * A supertext global function library
 * @package Supertext\Polylang\Api
 * @author Michael Sebel <michael@comotive.ch>
 */
class Library
{
  /**
   * @param string $language polylang language code
   * @return string equivalent supertext language code
   */
  public function mapLanguage($language)
  {
    $options = $this->getSettingOption();
    foreach ($options[Constant::SETTING_LANGUAGE_MAP] as $polyKey => $stKey) {
      if ($language == $polyKey) {
        return $stKey;
      }
    }

    return false;
  }

  /**
   * @param int $userId wordpress user
   * @return array user configuration for supertext api calls
   */
  public function getUserCredentials($userId)
  {
    $options = $this->getSettingOption();
    $userMap = $options[Constant::SETTING_USER_MAP];

    if (is_array($userMap)) {
      foreach ($userMap as $config) {
        if ($config['wpUser'] == $userId) {
          return $config;
        }
      }
    }

    // Default user, so it doesn't crash
    return array(
      'wpUser' => $userId,
      'stUser' => Constant::DEFAULT_API_USER,
      'stApi' => ''
    );
  }

  /**
   * @return array full settings array
   */
  public function getSettingOption()
  {
    return get_option(Constant::SETTINGS_OPTION, array());
  }

  /**
   * @param string $subSetting key
   * @param array|mixed $value saved value
   */
  public function saveSetting($subSetting, $value)
  {
    $options = $this->getSettingOption();
    $options[$subSetting] = $value;
    update_option(Constant::SETTINGS_OPTION, $options);
  }

  /**
   * @return bool true if workingly configured
   */
  public function isWorking()
  {
    $options = $this->getSettingOption();
    return ($options[Constant::SETTING_WORKING] == 1);
  }

  /**
   * Get an API wrapper as an authenticated user
   * @param int $userId
   * @return Wrapper prepared api wrapper
   */
  public function getUserWrapper($userId = 0)
  {
    // Get currently logged in user, if no user given
    if ($userId == 0) {
      $userId = get_current_user_id();
    }

    // Try to find credentials
    $userId = intval($userId);
    $credentials = $this->getUserCredentials($userId);

    // Get the ready to call instance
    return Wrapper::getInstance(
      $credentials['stUser'],
      $credentials['stApi']
    );
  }

  /**
   * @param int $postId the post id to get data for
   * @param array $pattern translation pattern
   * @return array translation data
   */
  public function getTranslationData($postId, $pattern)
  {
    $post = get_post($postId);
    $result = array();

    if ($pattern['post_title'] == true) {
      $result['post']['post_title'] = $post->post_title;
    }
    if ($pattern['post_content'] == true) {
      $result['post']['post_content'] = $post->post_content;
    }
    if ($pattern['post_excerpt'] == true) {
      $result['post']['post_excerpt'] = $post->post_excerpt;
    }

    // Gallery
    if ($pattern['post_image'] == true) {
      $attachments = get_children(array('post_parent' => $postId, 'post_type' => 'attachment', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC'));
      foreach ($attachments as $gallery_post) {
        $array_name = 'gallery_image_' . $gallery_post->ID;
        $result[$array_name]['post_title'] = $gallery_post->post_title;
        $result[$array_name]['post_content'] = $gallery_post->post_content;
        $result[$array_name]['post_excerpt'] = $gallery_post->post_excerpt;
        $result[$array_name]['image_alt'] = get_post_meta($gallery_post->ID, '_wp_attachment_image_alt', true);
      }
    }

    // Let developers add their own fields
    $result = apply_filters('translation_data_for_post', $result, $postId);

    return $result;
  }
} 