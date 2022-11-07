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
    $structuralData = array(
      'title' => __('Copy structural meta data', 'supertext'),
      'name' => 'sttr-structural-meta-data',
      'checkedPerDefault' => false
    );
    $additionalFields = array($structuralData);

    if ($this->hasAcfBlocks(get_post($postId)->post_content)) {
      array_push($additionalFields, array(
        'title' => __('Blocks', 'supertext'),
        'name' => 'sttr-blocks-meta-data',
        'checkedPerDefault' => true,
        'dependencies' => array('post' => 'post_content')
      ));
    }

    return array_merge($additionalFields, $translatableFields);
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

    if (!$this->hasAcfBlocks($post->post_content) || !isset($selectedTranslatableFields['sttr-blocks-meta-data'])) {
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
    if (!$this->hasAcfBlocks($post->post_content)) {
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

  private function hasAcfBlocks($post_content)
  {
    $necessaryBlockFunctionsExist = function_exists('acf_register_block_type') && function_exists('parse_blocks') && function_exists('serialize_blocks') && function_exists('has_blocks');

    return $necessaryBlockFunctionsExist && has_blocks($post_content) && strpos($post_content, '<!-- wp:' . self::ACF_BLOCK_NAME_PREFIX) !== false;
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

      $blockId = $this->createAcfBlockId($block);
      $data = $block['attrs']['data'];
      $metaKeys = array_keys($data);

      foreach ($savedFieldDefinitions[$this->pluginId] as $savedFieldDefinition) {
        $metaKeyMatches = preg_grep('/^' . $savedFieldDefinition['meta_key_regex'] . '$/', $metaKeys);
        $serializedKey = isset($savedFieldDefinition['serialized_key']) ? $savedFieldDefinition['serialized_key'] : null;

        foreach ($metaKeyMatches as $metaKeyMatch) {
          $textKey =  $blockId . '_' . $metaKeyMatch;
          $text = $data[$metaKeyMatch];

          $texts[$textKey] = $this->getAcfBlockAttributeTextValue($text, $serializedKey, $texts[$textKey]);
        }
      }
    }

    return $texts;
  }

  private function getAcfBlockAttributeTextValue($text, $serializedKey, $currentTextValue)
  {
    if (!is_array($text)) {
      return $this->textProcessor->replaceShortcodes($text);
    }

    if ($serializedKey === null) {
      return $text;
    }

    $value = isset($currentTextValue) ? $currentTextValue : array();
    $value[$serializedKey] = $this->textProcessor->replaceShortcodes($text[$serializedKey]);
    return $value;
  }

  private function setAcfBlockTexts($post, $acfBlockTexts)
  {
    $blocks = parse_blocks($post->post_content);

    foreach ($acfBlockTexts as $id => $text) {
      preg_match(self::ACF_BLOCK_TEXT_ID_REGEX, $id, $idRegexMatches);

      $blockId = $idRegexMatches[1];
      $metaKey = $idRegexMatches[2];

      $value = is_array($text) ? $this->getTextValuesFromArray($text) : $this->getTextValue($text);

      foreach ($blocks as &$block) {
        if (!$this->isAcfBlock($block)) {
          continue;
        }

        $currentBlockId = $this->createAcfBlockId($block);

        if ($currentBlockId !== $blockId) {
          continue;
        }

        $block['attrs']['data'][$metaKey] = is_array($value) ? array_merge($block['attrs']['data'][$metaKey], $value) : $value;
      }
    }

    $post->post_content = serialize_blocks($blocks);
  }

  private function getTextValuesFromArray($text)
  {
    $value = array();

    foreach ($text as $serializedKey => $content) {
      $value[$serializedKey] = $this->getTextValue($content);
    }

    return $value;
  }

  private function getTextValue($text)
  {
    $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
    $value = $this->textProcessor->replaceShortcodeNodes($decodedContent);
    return $value;
  }

  private function isAcfBlock($block)
  {
    return strpos($block['blockName'], self::ACF_BLOCK_NAME_PREFIX) === 0 && isset($block['attrs']) && isset($block['attrs']['data']);
  }

  private function createAcfBlockId($block)
  {
    $data = ksort($block['attrs']['data']);
    $dataId = md5($block['blockName'] . json_encode($data));
    return 'block_' .  $dataId;
  }
}
