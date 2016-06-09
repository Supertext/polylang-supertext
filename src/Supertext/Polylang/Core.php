<?php

namespace Supertext\Polylang;

use Supertext\Polylang\Api\Library;
use Supertext\Polylang\Backend\ContentProvider;
use Supertext\Polylang\Backend\Menu;
use Supertext\Polylang\Backend\Log;
use Supertext\Polylang\Backend\Translation;
use Supertext\Polylang\Helper\BeaverBuilderTextAccessor;
use Supertext\Polylang\Helper\Constant;

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
  protected static $instance = NULL;
  /**
   * @var Library the library of global functions
   */
  protected $library = NULL;
  /**
   * @var Menu the backend menu handler
   */
  protected $menu = NULL;
  /**
   * @var Log the backend menu handler
   */
  protected $log = NULL;
  /**
   * @var Translation the translation library
   */
  protected $translation = NULL;
  /**
   * @var ContentProvider the content provider
   */
  protected $contentProvider = NULL;
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
      $this->menu = new Menu();
      $this->log = new Log();
      $this->translation = new Translation();
    }

    $this->library = new Library();
    $this->contentProvider = new ContentProvider($this->library);

    $this->checkVersion();
  }

  /**
   * @return Library the library class
   */
  public function getLibrary()
  {
    return $this->library;
  }


  /**
   * @return Log the logger, might be instantiated only if needed
   */
  public function getLog()
  {
    if ($this->log === NULL) {
      $this->log = new Log();
    }

    return $this->log;
  }

  /**
   * @return Translation the translation object
   */
  public function getTranslation()
  {
    return $this->translation;
  }

  public function getContentProvider()
  {
    return $this->contentProvider;
  }

  /**
   * Registers all assets
   */
  public function registerAdminAssets()
  {
    wp_register_style(Constant::STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/style.css', array(), SUPERTEXT_PLUGIN_REVISION);
    wp_register_style(Constant::POST_STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/post.css', array(), SUPERTEXT_PLUGIN_REVISION);
    wp_register_style(Constant::JSTREE_STYLE_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/jstree/themes/wordpress-dark/style.min.css', array(), SUPERTEXT_PLUGIN_REVISION);

    wp_register_script(Constant::GLOBAL_SCRIPT_HANDLE, SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/global-library.js', array('jquery'), SUPERTEXT_PLUGIN_REVISION);
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
    if (isset($options[Helper\Constant::SETTING_SHORTCODES])){
      //Check options state and update if needed
      $shortcodes = $options[Helper\Constant::SETTING_SHORTCODES];
      $checkedShortcodes = array();

      foreach ($shortcodes as $key => $shortcode) {
        if(!is_array($shortcode['attributes'])){
          $shortcode['attributes'] = array();
          $checkedShortcodes[$key] = $shortcode;
          continue;
        }

        if(empty($shortcode['attributes'])){
          $checkedShortcodes[$key] = $shortcode;
          continue;
        }

        $checkedAttributes = array();
        foreach ($shortcode['attributes'] as $attribute) {
          if(!is_array($attribute)){
            $checkedAttributes[] = array('name' => $attribute, 'encoding' => '');
          }else{
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

  private function checkVersion()
  {
    if (get_option(Constant::VERSION_OPTION) != SUPERTEXT_PLUGIN_VERSION) {
      $this->onActivation();
      update_option(Constant::VERSION_OPTION, SUPERTEXT_PLUGIN_VERSION);
    }
  }
} 