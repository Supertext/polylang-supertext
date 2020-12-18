<?php

namespace Supertext\Polylang\Backend;


/**
 * The menu handler
 * @package Supertext\Polylang\Backend
 * @author Michael Sebel <michael@comotive.ch>
 */
class Menu
{
  private $settingsPage;
  private $toolsPage;

  /**
   * Injects the registration of settings menu
   */
  public function __construct($settingsPage, $toolsPage)
  {
    $this->settingsPage = $settingsPage;
    $this->toolsPage = $toolsPage;

    add_action('admin_menu', array($this, 'addBackendMenus'));
  }

  /**
   * Add option page for supertext settings
   */
  public function addBackendMenus()
  {
    add_options_page(
      __('Supertext - Settings', 'polylang-supertext'),
      __('Supertext', 'polylang-supertext'),
      'administrator',
      'supertext-polylang-settings',
      array($this->settingsPage, 'display')
    );

    add_submenu_page(
      'tools.php',
      __('Supertext - Tools', 'polylang-supertext'),
      __('Supertext', 'polylang-supertext'),
      'administrator',
      'supertext-tools',
      array($this->toolsPage, 'display')
    );

    // hide the tool for now
    remove_submenu_page('tools.php', 'supertext-tools');
  }
}