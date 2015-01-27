<?php
namespace Supertext\Polylang\Settings;

use Supertext\Polylang\Core;
use Supertext\Polylang\Api\Library;

/**
 * Simple abstract class to build backend settings pages
 * @package Supertext\Polylang\Settings
 * @author Michael Sebel <michael@comotive.ch>
 */
abstract class AbstractPage
{
  /**
   * @var Core the plugin core
   */
  protected $core = NULL;
  /**
   * @var Library the library
   */
  protected $library = NULL;

  /**
   * Create references to core and library for convenience
   */
  public function __construct()
  {
    $this->core = Core::getInstance();
    $this->library = $this->core->getLibrary();
    add_action('admin_init', array($this, 'control'));
  }

  /**
   * @return Core the plugin core
   */
  public function getCore()
  {
    return $this->core;
  }

  /**
   * @param string $viewName the view to be included
   * @param mixed $context this variable can be used in the view
   */
  public function includeView($viewName, $context = NULL)
  {
    include SUPERTEXT_POLYLANG_VIEW_PATH . $viewName . '.php';
  }

  /**
   * Displays the actual backend menu
   */
  abstract public function display();

  /**
   * Saves the backend menu ad admin_init
   */
  abstract public function control();
} 