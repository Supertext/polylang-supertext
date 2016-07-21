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
use Supertext\Polylang\Helper\TextProcessor;
use Supertext\Polylang\Helper\PostContentAccessor;
use Supertext\Polylang\Helper\PostMediaContentAccessor;
use Supertext\Polylang\Helper\AcfContentAccessor;
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
    wp_register_style(Constant::SETTINGS_STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/settings.css', array(), SUPERTEXT_PLUGIN_REVISION);
    wp_register_style(Constant::ADMIN_EXTENSION_STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/admin-extension.css', array(), SUPERTEXT_PLUGIN_REVISION);
    wp_register_style(Constant::JSTREE_STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/jstree/themes/wordpress-dark/style.min.css', array(), SUPERTEXT_PLUGIN_REVISION);

    wp_register_script(Constant::ADMIN_EXTENSION_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/admin-extension-library.js', array('jquery', 'wp-util', 'underscore'), SUPERTEXT_PLUGIN_REVISION, true);
    wp_register_script(Constant::SETTINGS_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/settings-library.js', array('jquery'), SUPERTEXT_PLUGIN_REVISION);
    wp_register_script(Constant::JSTREE_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/jstree/jstree.min.js', array('jquery'), SUPERTEXT_PLUGIN_REVISION);
  }

  /**
   * Registers localization scripts
   */
  public function registerLocalizationScripts()
  {
    $translation_array = array(
      'languages' => array(),
      'addNewUser' => esc_js(__('Add user', 'polylang-supertext')),
      'inTranslationText' => esc_js(Constant::IN_TRANSLATION_TEXT),
      'deleteUser' => esc_js(__('Delete user', 'polylang-supertext')),
      'translationCreation' => esc_js(__('Translation is being initialized. Please wait a moment.', 'polylang-supertext')),
      'generalError' => esc_js(__('An error occurred', 'polylang-supertext')),
      'networkError' => esc_js(__('A network error occurred', 'polylang-supertext')),
      'validationError' => esc_js(__('Validation error', 'polylang-supertext')),
      'offerTranslation' => esc_js(__('Order translation', 'polylang-supertext')),
      'translationOrderError' => esc_js(__('The order couldn\'t be sent to Supertext. Please try again.', 'polylang-supertext')),
      'confirmUnsavedArticle' => esc_js(__('The article was not saved. If you proceed with the translation, the unsaved changes will be lost.', 'polylang-supertext')),
      'alertUntranslatable' => esc_js(__('The article cannot be translated because there is an unfinished translation task. Please use the original article to order a translation.', 'polylang-supertext')),
      'errorTitleNotAllPostInSameLanguage' => esc_js(__('Language mismatch', 'polylang-supertext')),
      'errorValidationNotAllPostInSameLanguage' => esc_js(__('Please select only post in the same language.', 'polylang-supertext')),
      'errorValidationSomePostInTranslation' => esc_js(__('Posts in translation cannot be translated.', 'polylang-supertext')),
      'errorValidationSelectTargetLanguage' => esc_js(__('Please select the target language.', 'polylang-supertext')),
      'errorValidationSelectQuote' => esc_js(__('Please choose a quote.', 'polylang-supertext')),
      'modalTitle' => esc_js(__('Your Supertext translation order', 'polylang-supertext')),
      'orderTranslation' => esc_js(__('Order translation', 'polylang-supertext')),
      'cancel' => esc_js(__('Cancel', 'polylang-supertext')),
      'back' => esc_js(__('Back', 'polylang-supertext')),
      'next' => esc_js(__('Next', 'polylang-supertext')),
      'close' => esc_js(__('Close window', 'polylang-supertext')),
      'alertPleaseSelect' => esc_js(__('Please select at least one item', 'polylang-supertext')),
    );

    $library = $this->getLibrary();
    if($library->isWorking()){
      $languages = Multilang::getLanguages();
      foreach($languages as $language){
        $translation_array['languages'][$language->slug] =  esc_js(__($library->mapLanguage($language->slug), 'polylang-supertext-langs'));
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

    //Migrate options
    if (isset($options[Helper\Constant::SETTING_SHORTCODES])) {
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
  }

  /**
   * Do stuff, if plugin is deactivated
   */
  public static function onDeactivation()
  {
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
      'custom-fields' => new CustomFieldsContentAccessor($textProcessor, $this->getLibrary())
    );

    $pcfContentAccessor = new PcfContentAccessor($textProcessor, $this->getLibrary());

    if (WordPress::isPluginActive('wordpress-seo/wp-seo.php')) {
      $pcfContentAccessor->registerPluginFieldDefinitions('yoast_seo', PluginFieldDefinitions::getYoastSeoFieldDefinitions());
    }

    if($pcfContentAccessor->hasRegisteredPluginFieldDefinitions())
    {
      $contentAccessors['pcf'] = $pcfContentAccessor;
    }

    if (WordPress::isPluginActive('advanced-custom-fields/acf.php') || WordPress::isPluginActive('advanced-custom-fields-pro/acf.php')) {
      $contentAccessors['acf'] = new AcfContentAccessor($textProcessor, $this->getLibrary());
    }

    if (WordPress::isPluginActive('beaver-builder-lite-version/fl-builder.php')) {
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