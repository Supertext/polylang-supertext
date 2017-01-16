<?php

namespace Supertext\Polylang;

use Comotive\Util\WordPress;
use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Backend\ContentProvider;
use Supertext\Polylang\Backend\Menu;
use Supertext\Polylang\Backend\Log;
use Supertext\Polylang\Backend\AdminExtension;
use Supertext\Polylang\Backend\AjaxRequestHandler;
use Supertext\Polylang\Backend\CallbackHandler;
use Supertext\Polylang\Helper\AllInOneSeoPackContentAccessor;
use Supertext\Polylang\Helper\BePageBuilderContentAccessor;
use Supertext\Polylang\Helper\DiviBuilderContentAccessor;
use Supertext\Polylang\Helper\IAddDefaultSettings;
use Supertext\Polylang\Helper\IContentAccessor;
use Supertext\Polylang\Helper\Library;
use Supertext\Polylang\Helper\BeaverBuilderContentAccessor;
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\CustomFieldsContentAccessor;
use Supertext\Polylang\Helper\PostMeta;
use Supertext\Polylang\Helper\PostTaxonomyContentAccessor;
use Supertext\Polylang\Helper\SiteOriginContentAccessor;
use Supertext\Polylang\Helper\TextProcessor;
use Supertext\Polylang\Helper\PostContentAccessor;
use Supertext\Polylang\Helper\PostMediaContentAccessor;
use Supertext\Polylang\Helper\AcfContentAccessor;
use Supertext\Polylang\Helper\VisualComposerContentAccessor;
use Supertext\Polylang\Helper\YoastSeoContentAccessor;
use Supertext\Polylang\Settings\SettingsPage;

/**
 * Core Class that initializes the plugins features
 * @package Supertext\Polylang
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
   * @var IContentAccessor[] the array of content accessors
   */
  private $contentAccessors = null;
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
   * @deprecated
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
      load_plugin_textdomain('polylang-supertext', false, 'polylang-supertext/resources/languages');
      load_plugin_textdomain('polylang-supertext-langs', false, 'polylang-supertext/resources/languages');

      // Load needed subcomponents in admin
      $this->menu = new Menu(new SettingsPage($this->getLibrary(), $this->getContentAccessors()));
      $this->adminExtension = new AdminExtension($this->getLibrary(), $this->getLog());
      $this->ajaxRequestHandler = new AjaxRequestHandler($this->getLibrary(), $this->getLog(), $this->getContentProvider());

      $this->checkVersion();
      $this->checkEnvironment();
    }

    add_action( 'wp_ajax_nopriv_sttr_callback', array($this, 'handleCallback'));
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

    wp_register_style(Constant::SETTINGS_STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/settings' . $suffix . '.css', array(), SUPERTEXT_PLUGIN_REVISION);
    wp_register_style(Constant::ADMIN_EXTENSION_STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/admin-extension' . $suffix . '.css', array(), SUPERTEXT_PLUGIN_REVISION);
    wp_register_style(Constant::JSTREE_STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/jstree/themes/wordpress-dark/style' . $suffix . '.css', array(), SUPERTEXT_PLUGIN_REVISION);

    wp_register_script(Constant::ADMIN_EXTENSION_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/admin-extension-library' . $suffix . '.js', array('jquery', 'wp-util', 'underscore'), SUPERTEXT_PLUGIN_REVISION, true);
    wp_register_script(Constant::SETTINGS_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/settings-library' . $suffix . '.js', array('jquery', 'wp-util', 'underscore'), SUPERTEXT_PLUGIN_REVISION);
    wp_register_script(Constant::JSTREE_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/jstree/jstree' . $suffix . '.js', array('jquery'), SUPERTEXT_PLUGIN_REVISION);
  }

  /**
   * Registers localization scripts
   */
  public function registerLocalizationScripts()
  {
    $translation_array = array(
      'languages' => array(),
      'generalError' => esc_js(__('An error occurred', 'polylang-supertext')),
      'networkError' => esc_js(__('A network error occurred', 'polylang-supertext')),
      'validationError' => esc_js(__('Validation error', 'polylang-supertext')),
      'offerTranslation' => esc_js(__('Order translation', 'polylang-supertext')),
      'confirmUnsavedPost' => esc_js(__('The post was not saved. If you proceed with the translation, the unsaved changes will be lost.', 'polylang-supertext')),
      'errorValidationNotAllPostInSameLanguage' => esc_js(__('Please only select posts in the same language.', 'polylang-supertext')),
      'errorValidationSomePostInTranslation' => esc_js(__('Blocked posts cannot be translated.', 'polylang-supertext')),
      'errorValidationSelectContent' => esc_js(__('Please select content to be translated.', 'polylang-supertext')),
      'errorValidationSelectTargetLanguage' => esc_js(__('Please select the target language.', 'polylang-supertext')),
      'errorValidationSelectQuote' => esc_js(__('Please choose a quote.', 'polylang-supertext')),
      'orderModalTitle' => esc_js(__('Your Supertext translation order', 'polylang-supertext')),
      'sendChangesModalTitle' => esc_js(__('Send changes to Supertext', 'polylang-supertext')),
      'orderTranslation' => esc_js(__('Order translation', 'polylang-supertext')),
      'cancel' => esc_js(__('Cancel', 'polylang-supertext')),
      'back' => esc_js(__('Back', 'polylang-supertext')),
      'next' => esc_js(__('Next', 'polylang-supertext')),
      'close' => esc_js(__('Close window', 'polylang-supertext')),
      'alertPleaseSelect' => esc_js(__('Please select at least one post', 'polylang-supertext')),
      'alreadyBeingTranslatedInto' => esc_js(__('<i>{0}</i> is already being translated into {1} (order id: {2})', 'polylang-supertext')),
    );

    $library = $this->getLibrary();
    if ($library->getPluginStatus()->isPluginConfiguredProperly) {
      $languages = Multilang::getLanguages();
      foreach ($languages as $language) {
        $translation_array['languages'][$language->slug] = esc_js(__($library->toSuperCode($language->slug), 'polylang-supertext-langs'));
      }
    }

    wp_localize_script(Constant::ADMIN_EXTENSION_SCRIPT_HANDLE, 'supertextTranslationL10n', $translation_array);
  }

  /**
   * Handles a callback
   */
  public function handleCallback(){
    $this->getCallbackHandler()->handleRequest();
  }

  /**
   * Do stuff when plugin gets activated
   */
  public static function onActivation()
  {
    $library = new Library();

    $options = $library->getSettingOption();

    if (isset($options[Helper\Constant::SETTING_CUSTOM_FIELDS]) && get_option(Constant::VERSION_OPTION) < 1.8) {
      $library->saveSettingOption(Helper\Constant::SETTING_CUSTOM_FIELDS, array());
    }

    $queryForLegacyTranslationFlag = new \WP_Query(array(
      'meta_key' => '_in_st_translation'
    ));

    foreach($queryForLegacyTranslationFlag->posts as $post){
      $postMeta = PostMeta::from($post->ID);
      $postMeta->set(PostMeta::TRANSLATION, true);
      $postMeta->set(PostMeta::IN_TRANSLATION, true);
      $postMeta->set(PostMeta::IN_TRANSLATION_REFERENCE_HASH, get_post_meta($post->ID, '_in_translation_ref_hash', true));
    }

    $savedAcfFieldDefinitions = $library->getSettingOption('acfFields');
    if(count($savedAcfFieldDefinitions) && get_option(Constant::VERSION_OPTION) < 2.8){
      $savedFieldDefinitions = $library->getSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS);
      $savedFieldDefinitions['acf'] = $savedAcfFieldDefinitions;
      $library->saveSettingOption(Constant::SETTING_PLUGIN_CUSTOM_FIELDS, $savedFieldDefinitions);
    }
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
   * Adds well known shortcode settings depending on installed plugins
   * @param Library $library
   */
  private function addDefaultSettings()
  {
    $contentAccessors = $this->getContentAccessors();
    foreach($contentAccessors as $contentAccessor){
      if($contentAccessor instanceof IAddDefaultSettings){
        $contentAccessor->addDefaultSettings();
      }
    }
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
   * @return IContentAccessor[] the array of content accessors, might be instantiated only if needed
   */
  private function getContentAccessors()
  {
    if ($this->contentAccessors === null) {
      $this->contentAccessors = $this->createContentAccessors();
    }

    return $this->contentAccessors;
  }

  /**
   * @return ContentProvider the content provider, might be instantiated only if needed
   */
  private function getContentProvider()
  {
    if ($this->contentProvider === null) {
      $this->contentProvider = new ContentProvider($this->getContentAccessors(), $this->getLibrary());
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
   * @return IContentAccessor[] the array content accessors
   */
  private function createContentAccessors()
  {
    $library = $this->getLibrary();
    $textProcessor = new TextProcessor($library);

    $contentAccessors = array(
      'post' => new PostContentAccessor($textProcessor),
      'media' => new PostMediaContentAccessor(),
      'taxonomy' => new PostTaxonomyContentAccessor(),
      'custom-fields' => new CustomFieldsContentAccessor($textProcessor, $library)
    );

    if (WordPress::isPluginActive('wordpress-seo/wp-seo.php')) {
      $contentAccessors['yoast_seo'] = new YoastSeoContentAccessor($textProcessor, $library);
    }

    if (WordPress::isPluginActive('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
      $contentAccessors['all_in_one_seo'] = new AllInOneSeoPackContentAccessor($textProcessor, $library);
    }

    if (WordPress::isPluginActive('advanced-custom-fields/acf.php') || WordPress::isPluginActive('advanced-custom-fields-pro/acf.php')) {
      $contentAccessors['acf'] = new AcfContentAccessor($textProcessor, $library);
    }

    if (WordPress::isPluginActive('be-page-builder/be-page-builder.php')) {
      $contentAccessors['be_page_builder'] = new BePageBuilderContentAccessor($textProcessor, $library);
    }

    if (WordPress::isPluginActive('beaver-builder-lite-version/fl-builder.php') || WordPress::isPluginActive('bb-plugin/fl-builder.php')) {
      $contentAccessors['beaver_builder'] = new BeaverBuilderContentAccessor($textProcessor);
    }

    if (WordPress::isPluginActive('siteorigin-panels/siteorigin-panels.php')) {
      $contentAccessors['siteorigin_panels'] = new SiteOriginContentAccessor($textProcessor);
    }

    if (WordPress::isPluginActive('js_composer/js_composer.php') || WordPress::isPluginActive('js_composer_salient/js_composer.php')){
      $contentAccessors['post'] = new VisualComposerContentAccessor($textProcessor, $library);
    }

    if (WordPress::isPluginActive('divi-builder/divi-builder.php')) {
      $contentAccessors['post'] = new DiviBuilderContentAccessor($textProcessor, $library);
    }

    return $contentAccessors;
  }

  /**
   * Check environment
   */
  private function checkEnvironment()
  {
    if (!get_option(Constant::ENVIRONMENT_ADJUSTED_OPTION, false)) {
      $this->addDefaultSettings();
      update_option(Constant::ENVIRONMENT_ADJUSTED_OPTION, true);
    }
  }

  /**
   * Check plugin versions and activate again if changed
   */
  private function checkVersion()
  {
    if (get_option(Constant::VERSION_OPTION) != SUPERTEXT_PLUGIN_VERSION) {
      Core::onActivation();
      update_option(Constant::VERSION_OPTION, SUPERTEXT_PLUGIN_VERSION);
    }
  }
}