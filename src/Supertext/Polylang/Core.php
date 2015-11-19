<?php

namespace Supertext\Polylang;

use Supertext\Polylang\Api\Library;
use Supertext\Polylang\Backend\Menu;
use Supertext\Polylang\Backend\Log;
use Supertext\Polylang\Backend\Translation;
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
      add_action('init', array($this, 'RegisterAdminAssets'));

      // Load needed subcomponents in admin
      $this->menu = new Menu();
      $this->log = new Log();
      $this->translation = new Translation();
    }

    // Always loaded components
    $this->library = new Library();
  }

  /**
   * @return Library the library class
   */
  public function getLibrary()
  {
    return $this->library;
  }

  /**
   * @return Translation the translation object
   */
  public function getTranslation()
  {
    return $this->translation;
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
   * Registers all assets
   */
  public function RegisterAdminAssets()
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

    if (!isset($options[Helper\Constant::SETTING_SHORTCODES])) {
      $library->saveSetting(Helper\Constant::SETTING_SHORTCODES,
        array(
          'vc_raw_html' => array(
            'content_encoding' => 'rawurl,base64',
            'attributes' => array()
          ),
          'vc_custom_heading' => array(
            'content_encoding' => null,
            'attributes' => array('text')
          )
        )
      );
    }
  }

  /**
   * Do stuff, if plugin is deactivated
   */
  public static function onDeactivation()
  {

  }
} 