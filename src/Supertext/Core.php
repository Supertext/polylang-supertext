<?php

namespace Supertext;

use Supertext\Backend\ContentProvider;
use Supertext\Backend\Menu;
use Supertext\Backend\Log;
use Supertext\Backend\AdminExtension;
use Supertext\Backend\AjaxRequestHandler;
use Supertext\Backend\CallbackHandler;
use Supertext\Helper\Library;
use Supertext\Helper\Constant;
use Supertext\Helper\TextProcessor;
use Supertext\Proofreading\Proofreading;
use Supertext\TextAccessors\AcfTextAccessor;
use Supertext\TextAccessors\AllInOneSeoPackTextAccessor;
use Supertext\TextAccessors\BeaverBuilderTextAccessor;
use Supertext\TextAccessors\BePageBuilderTextAccessor;
use Supertext\TextAccessors\CustomFieldsTextAccessor;
use Supertext\TextAccessors\ElementorTextAccessor;
use Supertext\TextAccessors\DiviBuilderTextAccessor;
use Supertext\TextAccessors\ITextAccessor;
use Supertext\TextAccessors\PostTextAccessor;
use Supertext\TextAccessors\PostMediaTextAccessor;
use Supertext\TextAccessors\PostTaxonomyTextAccessor;
use Supertext\TextAccessors\SiteOriginTextAccessor;
use Supertext\TextAccessors\VisualComposerTextAccessor;
use Supertext\TextAccessors\YoastSeoTextAccessor;
use Supertext\Settings\SettingsPage;
use Supertext\Settings\ToolsPage;

/**
 * Core Class that initializes the plugins features
 * @package Supertext
 */
class Core
{
  /**
   * @var Core the current plugin instance
   */
  private static $instance = null;
  /**
   * @var Library the library of global functions
   */
  private $library = null;
  /**
   * @var SettingsPage the backend menu handler
   */
  private $settingsPage = null;
  /**
   * @var Menu the backend menu handler
   */
  private $menu = null;
  /**
   * @var Log the backend menu handler
   */
  private $log = null;
  /**
   * @var AdminExtension the translation library
   */
  private $adminExtension = null;
  /**
   * @var ITextAccessor[] the array of content accessors
   */
  private $textAccessors = null;
  /**
   * @var ContentProvider the content provider
   */
  private $contentProvider = null;
  /**
   * @var AjaxRequestHandler the ajax request handler
   */
  private $ajaxRequestHandler = null;
  /**
   * @var CallbackHandler the callback handler
   */
  private $callbackHandler = null;

  /**
   * Creates the instance and saves reference
   */
  public function __construct()
  {
    self::$instance = $this;
  }

  /**
   * @return Core return the core instance
   */
  public static function getInstance()
  {
    return self::$instance;
  }

  /**
   * Loads the plugin (registers needed actions, assets and subcomponents)
   */
  public function load()
  {
    if (is_admin()) {
      add_action('init', array($this, 'registerAdminAssets'));
      add_action('init', array($this, 'registerLocalizationScripts'));

      // Load translations
      load_plugin_textdomain('supertext', false, 'polylang-supertext/resources/languages');
      load_plugin_textdomain('supertext-langs', false, 'polylang-supertext/resources/languages');

      // Load needed subcomponents in admin after the theme is setup
      add_action('after_setup_theme', array($this, 'initializeAfterThemeSetup'));
    }

    add_action('wp_ajax_nopriv_sttr_callback', array($this, 'handleCallback'));
  }

  /**
   * Initalization after the theme is setup
   */
  public function initializeAfterThemeSetup()
  {
    $this->settingsPage = new SettingsPage($this->getLibrary(), $this->getTextAccessors());
    $this->menu = new Menu($this->settingsPage, new ToolsPage($this->getLibrary(), array($this->getCallbackHandler(), "handleInternalWriteBackRequest")));
    $this->adminExtension = new AdminExtension($this->getLibrary(), $this->getLog());
    $this->ajaxRequestHandler = new AjaxRequestHandler(
      $this->getLibrary(),
      $this->getLog(),
      $this->getContentProvider()
    );

    $this->checkVersion();
    $this->checkEnvironment();
  }

  /**
   * @return CallbackHandler the callback handler
   */
  public function getCallbackHandler()
  {
    if ($this->callbackHandler === null) {
      $this->callbackHandler = new CallbackHandler($this->getLibrary(), $this->getLog(), $this->getContentProvider());
    }

    return $this->callbackHandler;
  }

  /**
   * Registers all assets
   */
  public function registerAdminAssets()
  {
    $suffix = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

    wp_register_style(Constant::SETTINGS_STYLE_HANDLE, SUPERTEXT_RESOURCE_URL . '/styles/settings' . $suffix . '.css', array(), SUPERTEXT_PLUGIN_REVISION);
    wp_register_style(Constant::ADMIN_EXTENSION_STYLE_HANDLE, SUPERTEXT_RESOURCE_URL . '/styles/admin-extension' . $suffix . '.css', array(), SUPERTEXT_PLUGIN_REVISION);
    wp_register_style(Constant::JSTREE_STYLE_HANDLE, SUPERTEXT_RESOURCE_URL . '/scripts/jstree/themes/wordpress-dark/style' . $suffix . '.css', array(), SUPERTEXT_PLUGIN_REVISION);

    wp_register_script(Constant::ADMIN_EXTENSION_SCRIPT_HANDLE, SUPERTEXT_RESOURCE_URL . '/scripts/admin-extension-library' . $suffix . '.js', array('jquery', 'wp-util', 'underscore'), SUPERTEXT_PLUGIN_REVISION, true);
    wp_register_script(Constant::SETTINGS_SCRIPT_HANDLE, SUPERTEXT_RESOURCE_URL . '/scripts/settings-library' . $suffix . '.js', array('jquery', 'wp-util', 'underscore'), SUPERTEXT_PLUGIN_REVISION);
    wp_register_script(Constant::JSTREE_SCRIPT_HANDLE, SUPERTEXT_RESOURCE_URL . '/scripts/jstree/jstree' . $suffix . '.js', array('jquery'), SUPERTEXT_PLUGIN_REVISION);

    $blockEditorScriptDeps = array('wp-blocks', 'wp-dom-ready', 'wp-edit-post');

    if ($this->getLibrary()->isPolylangActivated()) {
      array_push($blockEditorScriptDeps, 'pll_block-editor-plugin');
    }

    wp_register_script(Constant::BLOCK_EDITOR_SCRIPT_HANDLE, SUPERTEXT_RESOURCE_URL . '/scripts/block-editor-library' . $suffix . '.js', $blockEditorScriptDeps, SUPERTEXT_PLUGIN_REVISION);
  }

  /**
   * Registers localization scripts
   */
  public function registerLocalizationScripts()
  {
    $translation_array = array(
      'languages' => array(),
      'generalError' => esc_js(__('An error occurred', 'supertext')),
      'networkError' => esc_js(__('A network error occurred', 'supertext')),
      'validationError' => esc_js(__('Validation error', 'supertext')),
      'offerTranslation' => esc_js(__('Order translation', 'supertext')),
      'offerProofread' => esc_js(__('Order proofreading', 'supertext')),
      'confirmUnsavedPost' => esc_js(__('The post was not saved. If you proceed with the translation, the unsaved changes will be lost.', 'supertext')),
      'errorValidationNotAllPostInSameLanguage' => esc_js(__('Please only select posts in the same language.', 'supertext')),
      'errorValidationSomePostInTranslation' => esc_js(__('Blocked posts cannot be translated.', 'supertext')),
      'errorValidationSelectContent' => esc_js(__('Please select content to be translated.', 'supertext')),
      'errorValidationSelectTargetLanguage' => esc_js(__('Please select the target language.', 'supertext')),
      'errorValidationSelectQuote' => esc_js(__('Please choose a quote.', 'supertext')),
      'orderModalTitle' => esc_js(__('Your Supertext translation order', 'supertext')),
      'sendChangesModalTitle' => esc_js(__('Send changes to Supertext', 'supertext')),
      'orderProofreading' => esc_js(__('Order proofreading', 'supertext')),
      'orderTranslation' => esc_js(__('Order translation', 'supertext')),
      'cancel' => esc_js(__('Cancel', 'supertext')),
      'back' => esc_js(__('Back', 'supertext')),
      'next' => esc_js(__('Next', 'supertext')),
      'close' => esc_js(__('Close window', 'supertext')),
      'alertPleaseSelect' => esc_js(__('Please select at least one post', 'supertext')),
      'alreadyBeingTranslatedInto' => esc_js(__('<i>{0}</i> is already being translated into {1} (order id: {2})', 'supertext')),
      'fieldDependentOn' => esc_js(__("This field cannot be deselected. Another field ({0}) depends on it.", 'supertext')),
    );

    $library = $this->getLibrary();
    if ($library->isLanguageMappingConfiguredProperly()) {
      $languages = $library->getConfiguredLanguages();
      foreach ($languages as $language) {
        $translation_array['languages'][$language->slug] = esc_js(__($library->toSuperCode($language->slug), 'supertext-langs'));
      }
    }

    wp_localize_script(Constant::ADMIN_EXTENSION_SCRIPT_HANDLE, 'supertextTranslationL10n', $translation_array);
  }

  /**
   * Handles a callback
   */
  public function handleCallback()
  {
    $this->getCallbackHandler()->handleRequest();
  }

  /**
   * Do stuff when plugin gets activated
   */
  public static function onActivation()
  {
    $versionMigration = new VersionMigration(new Library());

    $previousInstalledVersion = get_option(Constant::VERSION_OPTION);

    if (!$previousInstalledVersion) {
      return;
    }

    $versionMigration->migrate($previousInstalledVersion, SUPERTEXT_PLUGIN_VERSION);
  }

  /**
   * Do stuff, if plugin is deactivated
   */
  public static function onDeactivation()
  {
  }

  /**
   * Do stuff, if plugin is uninstalled
   */
  public static function onUninstall()
  {
    $library = new Library();

    $library->deleteSettingOption();
  }

  /**
   * @return Library gets the library, might be instantiated only if needed
   */
  private function getLibrary()
  {
    if ($this->library === null) {
      $this->library = new Library();
    }

    return $this->library;
  }

  /**
   * @return ITextAccessor[] the array of content accessors, might be instantiated only if needed
   */
  private function getTextAccessors()
  {
    if ($this->textAccessors === null) {
      $this->textAccessors = $this->createTextAccessors();
    }

    return $this->textAccessors;
  }

  /**
   * @return ContentProvider the content provider, might be instantiated only if needed
   */
  private function getContentProvider()
  {
    if ($this->contentProvider === null) {
      $this->contentProvider = new ContentProvider($this->getTextAccessors(), $this->getLibrary());
    }

    return $this->contentProvider;
  }

  /**
   * @return Log the logger, might be instantiated only if needed
   */
  private function getLog()
  {
    if ($this->log === null) {
      $this->log = new Log();
    }

    return $this->log;
  }

  /**
   * Creates the array of content accessors
   * @return ITextAccessor[] the array content accessors
   */
  private function createTextAccessors()
  {
    $library = $this->getLibrary();
    $textProcessor = new TextProcessor($library);

    $textAccessors = array(
      'post' => new PostTextAccessor($textProcessor),
      'media' => new PostMediaTextAccessor($library),
      'taxonomy' => new PostTaxonomyTextAccessor($library),
      'custom-fields' => new CustomFieldsTextAccessor($textProcessor, $library)
    );

    if ($library->isPluginActive('wordpress-seo/wp-seo.php')) {
      $textAccessors['yoast_seo'] = new YoastSeoTextAccessor($textProcessor, $library);
    }

    if ($library->isPluginActive('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
      $textAccessors['all_in_one_seo'] = new AllInOneSeoPackTextAccessor($textProcessor, $library);
    }

    if ($library->isPluginActive('advanced-custom-fields/acf.php') || $library->isPluginActive('advanced-custom-fields-pro/acf.php') || class_exists('ACF')) {
      $textAccessors['acf'] = new AcfTextAccessor($textProcessor, $library);
    }

    if ($library->isPluginActive('be-page-builder/be-page-builder.php')) {
      $textAccessors['be_page_builder'] = new BePageBuilderTextAccessor($textProcessor, $library);
    }

    if ($library->isPluginActive('beaver-builder-lite-version/fl-builder.php') || $library->isPluginActive('bb-plugin/fl-builder.php')) {
      $textAccessors['post']->uncheckPostContentPerDefault();
      $textAccessors['beaver_builder'] = new BeaverBuilderTextAccessor($textProcessor);
    }

    if ($library->isPluginActive('siteorigin-panels/siteorigin-panels.php')) {
      $textAccessors['siteorigin_panels'] = new SiteOriginTextAccessor($textProcessor);
    }

    if ($library->isPluginActive('js_composer/js_composer.php') || $library->isPluginActive('js_composer_salient/js_composer.php')) {
      $textAccessors['post'] = new VisualComposerTextAccessor($textProcessor, $library);
    }

    if ($library->isPluginActive('divi-builder/divi-builder.php') || defined('ET_CORE_VERSION') ) {
      $textAccessors['post'] = new DiviBuilderTextAccessor($textProcessor, $library);
    }

    if ($library->isPluginActive('elementor/elementor.php')) {
      $textAccessors['post']->uncheckPostContentPerDefault();
      $textAccessors['elementor'] = new ElementorTextAccessor($textProcessor, $library);
    }

    return $textAccessors;
  }

  /**
   * Check environment
   */
  private function checkEnvironment()
  {
    if (!get_option(Constant::ENVIRONMENT_ADJUSTED_OPTION, false)) {
      $this->settingsPage->addDefaultSettings();
      update_option(Constant::ENVIRONMENT_ADJUSTED_OPTION, true);
    }
  }

  /**
   * Check plugin versions and activate again if changed
   */
  private function checkVersion()
  {
    $previousInstalledVersion = get_option(Constant::VERSION_OPTION);

    if ($previousInstalledVersion != SUPERTEXT_PLUGIN_VERSION) {
      $versionMigration = new VersionMigration($this->getLibrary());
      $versionMigration->migrate($previousInstalledVersion, SUPERTEXT_PLUGIN_VERSION);
      update_option(Constant::VERSION_OPTION, SUPERTEXT_PLUGIN_VERSION);
    }
  }
}
