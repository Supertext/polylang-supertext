<?php

namespace Supertext\TextAccessors;

use Supertext\Helper\Constant;

/**
 * Class AcfTextAccessor
 * @package Supertext\TextAccessors
 */
class AcfTextAccessor extends AbstractPluginCustomFieldsTextAccessor implements IMetaDataAware
{
  const META_KEY_DELIMITER = '_(\\d+_)?';
  const ACF_BLOCK_NAME_PREFIX = 'acf/';
  const ACF_BLOCK_ID_PREFIX = 'block_';
  const ACF_BLOCK_TEXT_ID_REGEX = '/(block_[^_]+)_(.*)/';

  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName()
  {
    return __('Advanced Custom Fields (Plugin)', 'supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = parent::getTranslatableFields($postId);

    return array_merge(array(array(
      'title' => __('Copy structural meta data', 'supertext'),
      'name' => 'sttr-structural-meta-data',
      'checkedPerDefault' => true
    )), $translatableFields);
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getContentMetaData($post, $selectedTranslatableFields)
  {
    if (!isset($selectedTranslatableFields['sttr-structural-meta-data'])) {
      return array();
    }

    $fields = get_fields($post->ID);

    return $this->getMetaData($post->ID, '', $fields);
  }

  /**
   * @param $post
   * @param $translationMetaData
   */
  public function setContentMetaData($post, $translationMetaData)
  {
    foreach ($translationMetaData as $key => $value) {
      update_post_meta($post->ID, $key, $value);
    }
  }

  /**
   * Handle special case when ACF blocks are being used.
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = parent::getTexts($post, $selectedTranslatableFields);

    if (!$this->hasAcfBlocks($post)) {
      return $texts;
    }

    return $this->addAcfBlockTexts($post, $texts);
  }

  /**
   * Handle special case when ACF blocks are being used.
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    if (!$this->hasAcfBlocks($post)) {
      parent::setTexts($post, $texts);
      return;
    }

    $metaTexts = array();
    $acfBlockTexts = array();

    foreach ($texts as $id => $text) {
      if (strpos($id, self::ACF_BLOCK_ID_PREFIX) === 0) {
        $acfBlockTexts[$id] = $text;
      } else {
        $metaTexts[$id] = $text;
      }
    }

    $this->setAcfBlockTexts($post, $acfBlockTexts);

    parent::setTexts($post, $metaTexts);
  }

  /**
   * @return array
   */
  protected function getFieldDefinitions()
  {
    $fieldGroups = function_exists('acf_get_field_groups') ? acf_get_field_groups() : apply_filters('acf/get_field_groups', array());
    $acfFieldDefinition = array();

    foreach ($fieldGroups as $fieldGroup) {
      $fieldGroupId = $fieldGroup['key'];
      $fields = function_exists('acf_get_fields') ? acf_get_fields($fieldGroup) : apply_filters('acf/field_group/get_fields', array(), $fieldGroupId);

      $acfFieldDefinition[] = array(
        'id' => $fieldGroupId,
        'label' => $fieldGroup['title'],
        'type' => 'group',
        'sub_field_definitions' => $this->getSubFieldDefinitions($fields)
      );
    }

    return $acfFieldDefinition;
  }

  /**
   * @param $fields
   * @param string $metaKeyPrefix
   * @return array
   */
  private function getSubFieldDefinitions($fields, $metaKeyPrefix = '')
  {
    $group = array();

    foreach ($fields as $field) {
      $metaKey = $metaKeyPrefix . $field['name'];
      $fieldId = $field['key'];

      $newElement = array(
        'id' => $fieldId,
        'label' => $field['label']
      );

      if ($field['type'] === 'flexible_content') {
        $newElement['type'] = 'group';
        $newElement['sub_field_definitions'] = $this->getFlexibleContentFieldDefinitions($field['layouts'], $metaKey, $fieldId);
      } elseif (isset($field['sub_fields'])) {
        $newElement['type'] = 'group';
        $newElement['sub_field_definitions'] = $this->getSubFieldDefinitions($field['sub_fields'], $metaKey . self::META_KEY_DELIMITER);
      } elseif ($field['type'] === 'link' && $field['return_format'] === 'array') {
        $newElement['type'] = 'group';
        $newElement['sub_field_definitions'] = array(
          array(
            'id' => $fieldId . '_title',
            'label' => $field['label'] . ' title',
            'type' => 'field',
            'meta_key_regex' => $metaKey,
            'serialized_key' => 'title'
          ),
          array(
            'id' => $fieldId . '_url',
            'label' => $field['label'] . ' url',
            'type' => 'field',
            'meta_key_regex' => $metaKey,
            'serialized_key' => 'url'
          )
        );
      } else {
        $newElement['type'] = 'field';
        $newElement['meta_key_regex'] = $metaKey;
      }

      $group[] = $newElement;
    }

    return $group;
  }

  private function getFlexibleContentFieldDefinitions($layouts, $metaKey, $flexibleContentFieldId)
  {
    $subFieldDefinitions = array();

    $layoutId = 0;
    foreach ($layouts as $layout) {
      $layoutSubFieldDefinitions = $this->getSubFieldDefinitions($layout['sub_fields'], $metaKey . self::META_KEY_DELIMITER);

      $subFieldDefinitions[] = array(
        'id' => $flexibleContentFieldId . '_layout_' . $layoutId,
        'label' => $layout['label'],
        'type' => 'group',
        'sub_field_definitions' => $layoutSubFieldDefinitions
      );

      ++$layoutId;
    }

    return $subFieldDefinitions;
  }

  private function getMetaData($postId, $parentKey, $fields)
  {
    $metaData = array();

    foreach ($fields as $fieldKey => $fieldValue) {
      if (!is_array($fieldValue)) {
        continue;
      }

      $currentKey = $parentKey . $fieldKey;
      $currentMetaValue = get_post_meta($postId, $currentKey, true);

      if (!empty($currentMetaValue)) {
        $metaData[$currentKey] = $currentMetaValue;
      }

      $subMetaData = $this->getMetaData($postId, $currentKey . '_', $fieldValue);

      $metaData = array_merge($metaData, $subMetaData);
    }

    return $metaData;
  }

  private function hasAcfBlocks($post)
  {
    $necessaryBlockFunctionsExist = function_exists('acf_register_block_type') && function_exists('parse_blocks') && function_exists('serialize_blocks') && function_exists('has_blocks');

    return $necessaryBlockFunctionsExist && has_blocks($post) && strpos($post->post_content, '<!-- wp:' . self::ACF_BLOCK_NAME_PREFIX) !== false;
  }

  private function addAcfBlockTexts($post, $texts)
  {
    $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);

    if (!isset($savedFieldDefinitions[$this->pluginId])) {
      return $texts;
    }

    $blocks = parse_blocks($post->post_content);

    foreach ($blocks as $block) {
      if (!$this->isAcfBlock($block)) {
        continue;
      }

      $blockId = $block['attrs']['id'];
      $data = $block['attrs']['data'];
      $metaKeys = array_keys($data);

      foreach ($savedFieldDefinitions[$this->pluginId] as $savedFieldDefinition) {
        $metaKeyMatches = preg_grep('/^' . $savedFieldDefinition['meta_key_regex'] . '$/', $metaKeys);

        foreach ($metaKeyMatches as $metaKeyMatch) {
          $textKey =  $blockId . '_' . $metaKeyMatch;
          $texts[$textKey] = $this->textProcessor->replaceShortcodes($data[$metaKeyMatch]);
        }
      }
    }

    return $texts;
  }

  private function setAcfBlockTexts($post, $acfBlockTexts){
    $blocks = parse_blocks($post->post_content);

    foreach ($acfBlockTexts as $id => $text) {
      preg_match(self::ACF_BLOCK_TEXT_ID_REGEX, $id, $idRegexMatches);

      $blockId = $idRegexMatches[1];
      $metaKey = $idRegexMatches[2];
      $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
      $value = $this->textProcessor->replaceShortcodeNodes($decodedContent);

      foreach ($blocks as &$block) {
        if (!$this->isAcfBlock($block)) {
          continue;
        }

        $currentBlockId = $block['attrs']['id'];

        if ($currentBlockId !== $blockId) {
          continue;
        }

        $block['attrs']['data'][$metaKey] = $value;
      }
    }

    $post->post_content = serialize_blocks($blocks);
  }

  private function isAcfBlock($block)
  {
    return strpos($block['blockName'], self::ACF_BLOCK_NAME_PREFIX) === 0 && isset($block['attrs']) && isset($block['attrs']['id']) && isset($block['attrs']['data']);
  }
}
