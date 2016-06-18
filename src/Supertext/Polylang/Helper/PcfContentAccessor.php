<?php

namespace Supertext\Polylang\Helper;

use Comotive\Util\WordPress;
use Comotive\Util\ArrayManipulation;

class PcfContentAccessor implements IContentAccessor, ISettingsAware
{
  /**
   * @var text processor
   */
  private $textProcessor;

  /**
   * @var library
   */
  private $library;

  public function __construct($textProcessor, $library)
  {
    $this->textProcessor = $textProcessor;
    $this->library = $library;
  }

  public function getTranslatableFields($postId)
  {
    $options = $this->library->getSettingOption();
    $savedPcfFields = isset($options[Constant::SETTING_PCF_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_PCF_FIELDS]) : array();
    $pcfFieldDefinitions = $this->getPcfFieldDefinitions();

    $translatableFields = array();

    foreach ($savedPcfFields as $savedPcfField) {
      if (!get_post_meta($postId, $savedPcfField, true)) {
        continue;
      }

      foreach ($pcfFieldDefinitions as $pcfFieldDefinition) {
        $subFieldDefinition = $pcfFieldDefinition['sub_field_definitions'];

        $translatableFields[] = array(
          'title' => $subFieldDefinition[$savedPcfField]['label'],
          'name' => $savedPcfField,
          'default' => true
        );

      }
    }

    return array(
      'source_name' => __('Plugin defined custom fields', 'polylang-supertext'),
      'fields' => $translatableFields
    );
  }

  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    foreach($selectedTranslatableFields as $id => $selected){
      $texts[$id] = get_post_meta($post->ID, $id, true);
    }

    return $texts;
  }

  public function setTexts($post, $texts)
  {
    foreach($texts as $id => $text){
      $decodedContent = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
      update_post_meta($post->ID, $id, $decodedContent);
    }
  }

  public function prepareTranslationPost($post, $translationPost)
  {
  }

  public function getSettingsViewBundle()
  {
    $options = $this->library->getSettingOption();
    $savedPcfFields = isset($options[Constant::SETTING_PCF_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_PCF_FIELDS]) : array();

    return array(
      'view' => 'backend/settings-pcf',
      'context' => array(
        'pcfFieldDefinitions' => $this->getPcfFieldDefinitions(),
        'savedPcfFields' => $savedPcfFields
      )
    );
  }

  public function SaveSettings($postData)
  {
    $checkedPcfFields = explode(',', $postData['pcf']['checkedPcfFields']);

    $pcfFieldsToSave = array();

    foreach ($checkedPcfFields as $checkedPcfField) {
      if (strpos($checkedPcfField, 'group_') === 0) {
        continue;
      }

      $pcfFieldsToSave[] = $checkedPcfField;
    }

    $this->library->saveSetting(Constant::SETTING_PCF_FIELDS, $pcfFieldsToSave);
  }

  private function getPcfFieldDefinitions()
  {
    $pcfFieldDefinitions = array();

    if (WordPress::isPluginActive('wordpress-seo/wp-seo.php')) {

      $pcfFieldDefinitions['group_yoast_seo'] = array(
        'label' => 'Yoast SEO',
        'type' => 'group',
        'sub_field_definitions' => array(
          '_yoast_wpseo_title' => array(
            'label' => __('SEO-optimized title', 'polylang-supertext'),
            'type' => 'field'
          ),
          '_yoast_wpseo_metadesc' => array(
            'label' => __('SEO-optimized description', 'polylang-supertext'),
            'type' => 'field'
          ),
          '_yoast_wpseo_focuskw' => array(
            'label' => __('Focus keywords', 'polylang-supertext'),
            'type' => 'field'
          ),
          '_yoast_wpseo_opengraph-title' => array(
            'label' => __('Facebook title', 'polylang-supertext'),
            'type' => 'field'
          ),
          '_yoast_wpseo_opengraph-description' => array(
            'label' => __('Facebook description', 'polylang-supertext'),
            'type' => 'field'
          )
        )
      );
    }

    return $pcfFieldDefinitions;
  }
}