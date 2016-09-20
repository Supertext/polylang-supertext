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
use Supertext\Polylang\Helper\IContentAccessor;
use Supertext\Polylang\Helper\Library;
use Supertext\Polylang\Helper\BeaverBuilderContentAccessor;
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\CustomFieldsContentAccessor;
use Supertext\Polylang\Helper\PcfContentAccessor;
use Supertext\Polylang\Helper\PluginFieldDefinitions;
use Supertext\Polylang\Helper\PostTaxonomyContentAccessor;
use Supertext\Polylang\Helper\TextProcessor;
use Supertext\Polylang\Helper\PostContentAccessor;
use Supertext\Polylang\Helper\PostMediaContentAccessor;
use Supertext\Polylang\Helper\AcfContentAccessor;
use Supertext\Polylang\Settings\SettingsPage;

//TODO refactor class (extract plugin dependent logic...)
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
    }

    $this->checkVersion();
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
    wp_register_script(Constant::SETTINGS_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/settings-library' . $suffix . '.js', array('jquery'), SUPERTEXT_PLUGIN_REVISION);
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
      'modalTitle' => esc_js(__('Your Supertext translation order', 'polylang-supertext')),
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
        $translation_array['languages'][$language->slug] = esc_js(__($library->mapLanguage($language->slug), 'polylang-supertext-langs'));
      }
    }

    wp_localize_script(Constant::ADMIN_EXTENSION_SCRIPT_HANDLE, 'supertextTranslationL10n', $translation_array);
  }

  /**
   * Do stuff when plugin gets activated
   */
  public static function onActivation()
  {
    $library = new Library();

    $options = $library->getSettingOption();

    if (isset($options[Helper\Constant::SETTING_CUSTOM_FIELDS]) && get_option(Constant::VERSION_OPTION) < 1.8) {
      $library->saveSetting(Helper\Constant::SETTING_CUSTOM_FIELDS, array());
    }

    self::migrateOldShortcodeSettings($options, $library);

    self::addWellKnownShortcodeSettings($options, $library);
  }

  /**
   * Do stuff, if plugin is deactivated
   */
  public static function onDeactivation()
  {
  }

  /**
   * Migrates old shortcode settings
   * @param $options
   * @param Library $library
   */
  private static function migrateOldShortcodeSettings($options, $library)
  {
    if (!isset($options[Helper\Constant::SETTING_SHORTCODES])) {
      return;
    }

    //Check options state and update if needed
    $shortcodes = $options[Helper\Constant::SETTING_SHORTCODES];
    $checkedShortcodes = array();

    foreach ($shortcodes as $key => $shortcode) {
      if (!is_array($shortcode['attributes'])) {
        $shortcode['attributes'] = array();
        $checkedShortcodes[$key] = $shortcode;
        continue;
      }

      if (empty($shortcode['attributes'])) {
        $checkedShortcodes[$key] = $shortcode;
        continue;
      }

      $checkedAttributes = array();
      foreach ($shortcode['attributes'] as $attribute) {
        if (!is_array($attribute)) {
          $checkedAttributes[] = array('name' => $attribute, 'encoding' => '');
        } else {
          $checkedAttributes[] = $attribute;
        }
      }

      $shortcode['attributes'] = $checkedAttributes;
      $checkedShortcodes[$key] = $shortcode;
    }

    $library->saveSetting(Helper\Constant::SETTING_SHORTCODES, $checkedShortcodes);
  }

  /**
   * Adds well known shortcode settings depending on installed plugins
   * @param $options
   * @param Library $library
   */
  private static function addWellKnownShortcodeSettings($options, $library)
  {
    $shortcodeSettings = isset($options[Helper\Constant::SETTING_SHORTCODES]) ? $options[Helper\Constant::SETTING_SHORTCODES] : array();

    if (WordPress::isPluginActive('js_composer/js_composer.php') || WordPress::isPluginActive('js_composer_salient/js_composer.php')){
      $shortcodeSettings['vc_raw_html'] = array(
        'content_encoding' => 'rawurl,base64',
        'attributes' => array()
      );

      $shortcodeSettings['vc_custom_heading'] = array(
        'content_encoding' => null,
        'attributes' => array(
          array('name' => 'text', 'encoding' => '')
        )
      );
    }

    if (WordPress::isPluginActive('be-page-builder/be-page-builder.php')) {
      $shortcodeSettings['special_heading'] = array(
        'content_encoding' => null,
        'attributes' => array(
          array('name' => 'title_content', 'encoding' => '')
        )
      );

      $shortcodeSettings['special_heading2'] = array(
        'content_encoding' => null,
        'attributes' => array(
          array('name' => 'title_content', 'encoding' => '')
        )
      );

      $shortcodeSettings['special_heading3'] = array(
        'content_encoding' => null,
        'attributes' => array(
          array('name' => 'title_content', 'encoding' => '')
        )
      );

      $shortcodeSettings['button'] = array(
        'content_encoding' => null,
        'attributes' => array(
          array('name' => 'button_text', 'encoding' => '')
        )
      );
    }

    $library->saveSetting(Helper\Constant::SETTING_SHORTCODES, $shortcodeSettings);
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
    $textProcessor = new TextProcessor($this->getLibrary());

    $contentAccessors = array(
      'post' => new PostContentAccessor($textProcessor),
      'media' => new PostMediaContentAccessor(),
      'taxonomy' => new PostTaxonomyContentAccessor(),
      'custom-fields' => new CustomFieldsContentAccessor($textProcessor, $this->getLibrary())
    );

    $pcfContentAccessor = new PcfContentAccessor($textProcessor, $this->getLibrary());

    if (WordPress::isPluginActive('wordpress-seo/wp-seo.php')) {
      $pcfContentAccessor->registerPluginFieldDefinitions('yoast_seo', PluginFieldDefinitions::getYoastSeoFieldDefinitions());
    }

    if (WordPress::isPluginActive('be-page-builder/be-page-builder.php')) {
      $pcfContentAccessor->registerPluginFieldDefinitions('be_pb', PluginFieldDefinitions::getBePageBuilderFieldDefinitions());
    }

    if ($pcfContentAccessor->hasRegisteredPluginFieldDefinitions()) {
      $contentAccessors['pcf'] = $pcfContentAccessor;
    }

    if (WordPress::isPluginActive('advanced-custom-fields/acf.php') || WordPress::isPluginActive('advanced-custom-fields-pro/acf.php')) {
      $contentAccessors['acf'] = new AcfContentAccessor($textProcessor, $this->getLibrary());
    }

    if (WordPress::isPluginActive('beaver-builder-lite-version/fl-builder.php') || WordPress::isPluginActive('bb-plugin/fl-builder.php')) {
      $contentAccessors['beaver_builder'] = new BeaverBuilderContentAccessor($textProcessor);
    }

    return $contentAccessors;
  }

  /**
   * Check plugin versions and activate again if changed
   */
  private function checkVersion()
  {
    if (get_option(Constant::VERSION_OPTION) != SUPERTEXT_PLUGIN_VERSION) {
      $this->onActivation();
      update_option(Constant::VERSION_OPTION, SUPERTEXT_PLUGIN_VERSION);
    }
  }
}