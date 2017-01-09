<?php
namespace Supertext\Polylang\Settings;

use Supertext\Polylang\Helper\Library;

/**
 * Simple abstract class to build backend settings pages
 * @package Supertext\Polylang\Settings
 * @author Michael Sebel <michael@comotive.ch>
 */
abstract class AbstractPage
{
  /**
   * @var Library the library
   */
  protected $library = NULL;

  /**
   * Create references to core and library for convenience
   */
  public function __construct($library)
  {
    $this->library = $library;

    add_action('admin_init', array($this, 'control'));
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