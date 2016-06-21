<?php

namespace Supertext\Polylang;

use Comotive\Util\WordPress;
use Supertext\Polylang\Backend\ContentProvider;
use Supertext\Polylang\Backend\Menu;
use Supertext\Polylang\Backend\Log;
use Supertext\Polylang\Backend\OfferBox;
use Supertext\Polylang\Backend\Translation;
use Supertext\Polylang\Backend\AjaxRequestHandler;
use Supertext\Polylang\Backend\CallbackHandler;
use Supertext\Polylang\Helper\Library;
use Supertext\Polylang\Helper\BeaverBuilderContentAccessor;
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\CustomFieldsContentAccessor;
use Supertext\Polylang\Helper\PcfContentAccessor;
use Supertext\Polylang\Helper\TextProcessor;
use Supertext\Polylang\Helper\PostContentAccessor;
use Supertext\Polylang\Helper\PostMediaContentAccessor;
use Supertext\Polylang\Helper\AcfContentAccessor;
use Supertext\Polylang\Settings\SettingsPage;

/**
 * Core Class that initializes the plugins features
 * @package Supertext\Polylang
 * @author Michael Sebel <michael@comotive.ch>
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
   * @var Translation the translation library
   */
  private $translation = null;
  /**
   * @var
   */
  private $contentAccessors = null;
  /**
   * @var ContentProvider the content provider
   */
  private $contentProvider = null;

  private $offerBox = null;

  private $ajaxRequestHandler = null;

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

  public function load()
  {
    if (is_admin()) {
      add_action('init', array($this, 'registerAdminAssets'));

      // Load needed subcomponents in admin
      $this->menu = new Menu(new SettingsPage($this->getLibrary(), $this->getContentAccessors()));
      $this->translation = $this->getTranslation();
    }

    $this->checkVersion();
  }

  public function getOfferBox()
  {
    if ($this->offerBox === null) {
      $this->offerBox = new OfferBox($this->getLibrary(), $this->getTranslation(), $this->getContentProvider());
    }

    return $this->offerBox;
  }

  public function getAjaxRequestHandler()
  {
    if ($this->ajaxRequestHandler === null) {
      $this->ajaxRequestHandler = new AjaxRequestHandler($this->getLibrary(), $this->getLog(), $this->getContentProvider());
    }

    return $this->ajaxRequestHandler;
  }

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
    wp_register_style(Constant::STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/style.css', array(), SUPERTEXT_PLUGIN_REVISION);
    wp_register_style(Constant::POST_STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/post.css', array(), SUPERTEXT_PLUGIN_REVISION);
    wp_register_style(Constant::JSTREE_STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/jstree/themes/wordpress-dark/style.min.css', array(), SUPERTEXT_PLUGIN_REVISION);

    wp_register_script(Constant::TRANSLATION_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/translation-library.js', array('jquery'), SUPERTEXT_PLUGIN_REVISION, true);
    wp_register_script(Constant::SETTINGS_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/settings-library.js', array('jquery'), SUPERTEXT_PLUGIN_REVISION);
    wp_register_script(Constant::JSTREE_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/jstree/jstree.min.js', array('jquery'), SUPERTEXT_PLUGIN_REVISION);
  }

  /**
   * Do stuff when plugin gets activated
   */
  public static function onActivation()
  {
    $library = new Library();

    $options = $library->getSettingOption();

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

  private function getLibrary(){
    if($this->library === null){
      $this->library = new Library();
    }

    return $this->library;
  }

  private function getContentAccessors(){
    if($this->contentAccessors === null){
      $this->contentAccessors = $this->CreateContentAccessors();
    }

    return $this->contentAccessors;
  }

  private function getContentProvider()
  {
    if ($this->contentProvider === null) {
      $this->contentProvider = new ContentProvider($this->contentAccessors, $this->getLibrary());
    }

    return $this->contentProvider;
  }

  private function getTranslation()
  {
    if($this->translation === null){
      $this->translation = new Translation($this->getLibrary(), $this->getLog());
    }

    return $this->translation;
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

  private function CreateContentAccessors()
  {
    $textProcessor = new TextProcessor($this->getLibrary());

    $contentAccessors = array(
      'post' => new PostContentAccessor($textProcessor),
      'media' => new PostMediaContentAccessor(),
      'custom-fields' => new CustomFieldsContentAccessor($textProcessor, $this->getLibrary()),
      'pcf' => new PcfContentAccessor($textProcessor, $this->getLibrary())
    );

    if (WordPress::isPluginActive('advanced-custom-fields/acf.php') || WordPress::isPluginActive('advanced-custom-fields-pro/acf.php')) {
      $contentAccessors['acf'] = new AcfContentAccessor($textProcessor, $this->getLibrary());
    }

    if (WordPress::isPluginActive('beaver-builder-lite-version/fl-builder.php')) {
      $contentAccessors['beaver_builder'] = new BeaverBuilderContentAccessor();
    }

    return $contentAccessors;
  }

  private function checkVersion()
  {
    if (get_option(Constant::VERSION_OPTION) != SUPERTEXT_PLUGIN_VERSION) {
      $this->onActivation();
      update_option(Constant::VERSION_OPTION, SUPERTEXT_PLUGIN_VERSION);
    }
  }
}