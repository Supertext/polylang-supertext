<?php

namespace Supertext;

use Supertext\Helper\Constant;
use Supertext\Helper\Library;
use Supertext\Helper\TextProcessor;
use Supertext\Helper\TranslationMeta;
use Supertext\TextAccessors\AcfTextAccessor;

class VersionMigration
{
  /**
   * @type Library Supertext\Helper\Library
   */
  private $library;

  /**
   * @param $library Supertext\Helper\Library
   */
  public function __construct($library)
  {

    $this->library = $library;
  }

  public function migrate($previousVersion, $currentVersion)
  {
    $options = $this->library->getSettingOption();

    if ($previousVersion < 1.8) {
      $this->clearCustomFieldSettings($options);
    }

    if ($previousVersion < 2.8) {
      $this->migrateAcfSettings();
    }

    if ($previousVersion < 3.8 && ($this->library->isPluginActive('advanced-custom-fields/acf.php') || $this->library->isPluginActive('advanced-custom-fields-pro/acf.php'))) {
      $this->migrateAcfIds();
    }

    if ($previousVersion < 4.21) {
      $this->migrateShortcodeSettings();
    }

    $this->migrateOldTranslationDataToTranslationMeta();

    $this->updateApiServerUrl();
  }

  public function replaceAfcIds()
  {
    $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);
    $savedAcfFieldDefinitions = $savedFieldDefinitions['acf'];
    $acfTextAccessor = new AcfTextAccessor(new TextProcessor($this->library), $this->library);
    $fieldDefinitions = $acfTextAccessor->getSettingsViewBundle()['context']['fieldDefinitions'];

    while (($field = array_shift($fieldDefinitions))) {
      if (!empty($field['sub_field_definitions'])) {
        $fieldDefinitions = array_merge($fieldDefinitions, $field['sub_field_definitions']);
        continue;
      }

      foreach ($savedAcfFieldDefinitions as &$savedAcfFieldDefinition) {
        if (isset($field['meta_key_regex']) && $savedAcfFieldDefinition['meta_key_regex'] == $field['meta_key_regex']) {
          $savedAcfFieldDefinition['id'] = $field['id'];
        }
      }
    }

    $savedFieldDefinitions['acf'] = $savedAcfFieldDefinitions;
    $this->library->saveSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS, $savedFieldDefinitions);
  }

  /**
   * @param $options
   */
  private function clearCustomFieldSettings($options)
  {
    if (isset($options[Constant::SETTING_CUSTOM_FIELDS])) {
      $this->library->saveSettingOption(Constant::SETTING_CUSTOM_FIELDS, array());
    }
  }

  private function migrateOldTranslationDataToTranslationMeta()
  {
    $queryForLegacyTranslationFlag = new \WP_Query(array(
      'meta_key' => '_in_st_translation',
      'post_status' => 'any',
      'post_type' => get_post_types('', 'names'),
    ));

    foreach ($queryForLegacyTranslationFlag->posts as $post) {
      $meta = TranslationMeta::of($post->ID);
      $meta->set(TranslationMeta::TRANSLATION, true);
      $meta->set(TranslationMeta::IN_TRANSLATION, true);
      $meta->set(TranslationMeta::IN_TRANSLATION_REFERENCE_HASH, get_post_meta($post->ID, '_in_translation_ref_hash', true));
    }
  }

  private function migrateAcfSettings()
  {
    $savedAcfFieldDefinitions = $this->library->getSettingOption('acfFields');
    if (count($savedAcfFieldDefinitions)) {
      $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);
      $savedFieldDefinitions['acf'] = $savedAcfFieldDefinitions;
      $this->library->saveSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS, $savedFieldDefinitions);
    }
  }

  private function migrateAcfIds()
  {
    $savedFieldDefinitions = $this->library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);

    if (empty($savedFieldDefinitions['acf'])) {
      return;
    }

    add_action('wp_loaded', array($this, "replaceAfcIds"));
  }

  private function updateApiServerUrl()
  {
    $apiSettings = $this->library->getSettingOption(Constant::SETTING_API);
    $apiServerUrl = $apiSettings['apiServerUrl'];
    $version = "v1/";

    if (!empty($apiServerUrl) && substr($apiServerUrl, -strlen($version)) === $version) {
      $apiSettings['apiServerUrl'] = substr($apiServerUrl, 0, strlen($apiServerUrl) - strlen($version));
      $this->library->saveSettingOption(Constant::SETTING_API, $apiSettings);
    }
  }

  private function migrateShortcodeSettings()
  {
    $shortcodeSettings = $this->library->getSettingOption(Constant::SETTING_SHORTCODES);

    if (isset($shortcodeSettings['shortcodes'])) {
      return;
    }

    $newShortcodeSettings = array(
      'shortcodes' => $shortcodeSettings,
      'isShortcodeReplacementDisabled' => false
    );


    $this->library->saveSettingOption(Constant::SETTING_SHORTCODES, $newShortcodeSettings);
  }
}
