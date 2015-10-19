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
  const SHORTCODE_TAG = 'div';
  const SHORTCODE_TAG_CLASS = 'polylang-supertext-shortcode';

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
    $userMap = isset($options[Constant::SETTING_USER_MAP]) ? $options[Constant::SETTING_USER_MAP] : null;

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
    return (isset($options[Constant::SETTING_WORKING]) && $options[Constant::SETTING_WORKING] == 1);
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
      $result['post']['post_content'] =  $this->replaceShortcodesWithNodes($post->post_content);
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

  /**
   * @param $postId the id of the post to translate
   * @return array the list of custom fields definitions (available for the post)
   */
  public function getCustomFieldDefinitions($postId){
    $postCustomFields = get_post_custom($postId);
    $options = $this->getSettingOption();
    $savedCustomFieldsDefinitions = isset($options[Constant::SETTING_CUSTOM_FIELDS]) ? $options[Constant::SETTING_CUSTOM_FIELDS] : array();

    $selectableCustomFieldDefinitions = array();

    foreach ($postCustomFields as $key => $value) {
      foreach ($savedCustomFieldsDefinitions as $savedCustomFieldDefinition) {
        if(preg_match('/^'.$savedCustomFieldDefinition['meta_key'].'$/', $key)){
          $selectableCustomFieldDefinitions[] = $savedCustomFieldDefinition;
        }
      }
    }

    return $selectableCustomFieldDefinitions;
  }

  /**
   * @param $postId the id of the post to translate
   * @param array $selectedCustomFieldIds the ids of the selected custom field definitions
   * @return array the list of custom field keys and values
   */
  public function getCustomFieldsForTranslation($postId, $selectedCustomFieldIds = array()){
    $postCustomFields = get_post_custom($postId);
    $options = $this->getSettingOption();
    $savedCustomFieldsDefinitions = isset($options[Constant::SETTING_CUSTOM_FIELDS]) ? $options[Constant::SETTING_CUSTOM_FIELDS] : array();

    $customFields = array();

    foreach ($postCustomFields as $customFieldKey => $customFieldValue) {
      foreach ($savedCustomFieldsDefinitions as $savedCustomFieldDefinition) {
        if(!in_array($savedCustomFieldDefinition['id'], $selectedCustomFieldIds)){
          continue;
        }

        if(preg_match('/^'.$savedCustomFieldDefinition['meta_key'].'$/', $customFieldKey)){
          $customFields[] = array(
            'key' => $customFieldKey,
            'value' => $customFieldValue
          );
        }
      }
    }

    return $customFields;
  }

  /**
   * Puts the shortcode back in the content by replacing the shortcode html nodes
   * @param string post content to process
   * @return string post content with shortcodes
   */
  public function putShortcodesBack($content)
  {
    $doc = new \DOMDocument();
    $doc->loadHTML($content);
    $newContent = $content;

    $shortcodeNodes = $doc->getElementsByTagName(self::SHORTCODE_TAG);

    foreach ($shortcodeNodes as $shortcodeNode) {
      $nodeClass = $shortcodeNode->attributes->getNamedItem('class')->nodeValue;

      if($nodeClass !== self::SHORTCODE_TAG_CLASS){
        continue;
      }

      $attributes = '';
      $textAttributeNodes = $shortcodeNode->getElementsByTagName('span');
      $inputAttributeNodes = $shortcodeNode->getElementsByTagName('input');

      foreach ($textAttributeNodes as $textAttributeNode) {
        $attributeName = $textAttributeNode->attributes->getNamedItem('name')->nodeValue;
        $attributeValue = $textAttributeNode->nodeValue;

        $attributes .= $attributeName.'="'.$attributeValue.'" ';
      }

      foreach ($inputAttributeNodes as $inputAttributeNode) {
        $attributeName = $inputAttributeNode->attributes->getNamedItem('name')->nodeValue;
        $attributeValue = $inputAttributeNode->attributes->getNamedItem('value')->nodeValue;

        $attributes .= $attributeName.'="'.$attributeValue.'" ';
      }

      $shortcodeName = $shortcodeNode->attributes->getNamedItem('name')->nodeValue;

      $shortcode = '['.$shortcodeName.' '.trim($attributes).']';

      $shortcodeNodePattern = '/'.$this->createShortcodeNode($shortcodeName, '(\s*((<span.*)|(<input.*))\s*)*', true).'/';

      $newContent = preg_replace($shortcodeNodePattern, $shortcode, $newContent, 1);
    }

    return $newContent;
  }

  /**
   * Replaces the shortcodes with html nodes
   * @param string post content to process
   * @return string post content with replaced shortcodes
   */
  private function replaceShortcodesWithNodes($content)
  {
    $options = $this->getSettingOption();
    $savedShortcodes = isset($options[Constant::SETTING_SHORTCODES]) ? $options[Constant::SETTING_SHORTCODES] : array();
    $regex = get_shortcode_regex();

    return preg_replace_callback( "/$regex/s", function($m) use ($savedShortcodes){
      return $this->replaceShortcode($m, $savedShortcodes);
    }, $content);
  }

  /**
   * Effectively replace one shortcode with a node
   * @param $m matches
   * @param $savedShortcodes saved shortcodes
   * @return string replacement string
   */
  private function replaceShortcode($m, $savedShortcodes){
    //return escaped shortcodes, do not replace
    //return not translatable shortcodes
    if(($m[1] == '[' && $m[6] == ']') || !isset($savedShortcodes[$m[2]])){
      return $m[0];
    }

    $tag = $m[2];
    $attributes = shortcode_parse_atts( $m[3] );
    $savedShortcodeAttributes = $savedShortcodes[$tag];

    $attributeNodes = '';

    foreach ($attributes as $name => $value) {
      if(in_array($name, $savedShortcodeAttributes)){
        $attributeNodes .= '<span name="'.$name.'">'.$value.'</span>';
      }else{
        $attributeNodes .= '<input type="hidden" name="'.$name.'" value="'.$value.'">';
      }
    }

    return $this->createShortcodeNode($tag, $attributeNodes);
  }

  /**
   * Creates a html shortcode node
   * @param $name name of the shortcode
   * @param $value value
   * @param bool $regex if is used within regex
   * @return string the html node
   */
  private function createShortcodeNode($name, $value, $regex = false)
  {
      return '<'.self::SHORTCODE_TAG.' class="'.self::SHORTCODE_TAG_CLASS.'" name="'.$name.'">'.$value.'<'.($regex ? '\\' : '').'/'.self::SHORTCODE_TAG.'>';
  }
} 