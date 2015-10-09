<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Settings\SettingsPage;

/**
 * The menu handler
 * @package Supertext\Polylang\Backend
 * @author Michael Sebel <michael@comotive.ch>
 */
class Menu
{
  /**
   * Injects the registration of settings menu
   */
  public function __construct()
  {
    add_action('admin_menu', array($this, 'addBackendMenus'));
  }

  /**
   * Add option page for supertext settings
   */
  public function addBackendMenus()
  {
    $settingsPage = new SettingsPage();

    add_options_page(
      __('Supertext API - Settings', 'polylang-supertext'),
      __('Supertext API', 'polylang-supertext'),
      'administrator',
      'supertext-polylang-settings',
      array($settingsPage, 'display')
    );
  }
}