<?php

namespace Supertext\Polylang;

use Supertext\Polylang\Api\Library;
use Supertext\Polylang\Backend\Menu;
use Supertext\Polylang\Backend\Translation;

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
    // Load needed subcomponents
    if (is_admin()) {
      $this->menu = new Menu();
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
   * Do stuff when plugin gets activated
   */
  public static function onActivation()
  {

  }

  /**
   * Do stuff, if plugin is deactivated
   */
  public static function onDeactivation()
  {

  }
} 