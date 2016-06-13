<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Helper\BeaverBuilderTextAccessor;
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\PostMediaTextAccessor;
use Supertext\Polylang\Helper\PostTextAccessor;
use Supertext\Polylang\Helper\TextProcessor;

/**
 * Processes post content
 * @package Supertext\Polylang\Backend
 */
class ContentProvider
{
  /**
   * @var array|null the text processors
   */
  private $textAccessors = null;
  private $library;

  public function __construct($library)
  {
    $this->textAccessors = array(
      'post' => new PostTextAccessor(new TextProcessor($library)),
      'media' => new PostMediaTextAccessor(),
      'acf' => new AcfTextAccessor(),
      'beaver_builder' => new BeaverBuilderTextAccessor()
    );

    $this->library = $library;
  }

  /**
   * @param $post
   * @param array $userSelection user selection of items to be translated
   * @return array translation data
   * @internal param int $postId the post id to get data for
   */
  public function getTranslationData($post, $userSelection)
  {
    $result = array();

    // Get the selected custom fields
    foreach ($this->getCustomFieldsForTranslation($post->ID, array_keys($userSelection)) as $meta_key => $value) {
      $result['meta'][$meta_key] = $this->replaceShortcodes($value);
    }


    foreach ($this->textAccessors as $id => $textAccessor) {
      $result[$id] = $textAccessor->getTexts($post, $userSelection);
    }

    // Let developers add their own fields
    $result = apply_filters('translation_data_for_post', $result, $post->ID);

    return $result;
  }

  public function SaveTranslatedData($post, $translationPost, $json)
  {
    foreach ($json->Groups as $translationGroup) {
      if (isset($this->textAccessors[$translationGroup->GroupId])) {
        $textAccessor = $this->textAccessors[$translationGroup->GroupId];

        $textAccessor->prepareTranslationPost($post, $translationPost);

        $texts = array();

        foreach ($translationGroup->Items as $translationItem) {
          $texts[$translationItem->Id] = $translationItem->Content;
        }

        $textAccessor->setTexts($translationPost, $texts);
      }
    }
  }

  /**
   * @param $fieldId the field id
   * @return string the field name created from the field id
   */
  public function getFieldNameFromId($fieldId)
  {
    return str_replace(' ', '_', $fieldId);
  }

  /**
   * @param $postId the id of the post to translate
   * @return array the list of custom fields definitions (available for the post)
   */
  public function getCustomFieldDefinitions($postId)
  {
    $postCustomFields = get_post_meta($postId);
    $options = $this->library->getSettingOption();
    $savedCustomFieldsDefinitions = isset($options[Constant::SETTING_CUSTOM_FIELDS]) ? $options[Constant::SETTING_CUSTOM_FIELDS] : array();

    $selectableCustomFieldDefinitions = array();

    foreach ($postCustomFields as $meta_key => $value) {
      foreach ($savedCustomFieldsDefinitions as $savedCustomFieldDefinition) {
        if (preg_match('/^' . $savedCustomFieldDefinition['meta_key_regex'] . '$/', $meta_key)) {
          $selectableCustomFieldDefinitions[] = $savedCustomFieldDefinition;
        }
      }
    }

    return $selectableCustomFieldDefinitions;
  }

  /**
   * @param $postId the id of the post to translate
   * @param array $selectedCustomFieldNames the ids of the selected custom field definitions
   * @return array the list of custom field keys and values
   */
  public function getCustomFieldsForTranslation($postId, $selectedCustomFieldNames = array())
  {
    $postCustomFields = get_post_meta($postId);
    $options = $this->library->getSettingOption();
    $savedCustomFieldsDefinitions = isset($options[Constant::SETTING_CUSTOM_FIELDS]) ? $options[Constant::SETTING_CUSTOM_FIELDS] : array();

    $customFields = array();

    foreach ($postCustomFields as $meta_key => $value) {
      foreach ($savedCustomFieldsDefinitions as $savedCustomFieldDefinition) {
        if (!in_array($this->getFieldNameFromId($savedCustomFieldDefinition['id']), $selectedCustomFieldNames)) {
          continue;
        }

        if (preg_match('/^' . $savedCustomFieldDefinition['meta_key_regex'] . '$/', $meta_key)) {
          $customFields[$meta_key] = is_array($value) ? $value[0] : $value;
        }
      }
    }

    return $customFields;
  }
}