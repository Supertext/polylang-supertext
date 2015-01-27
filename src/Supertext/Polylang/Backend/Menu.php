<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Settings\Page;

class Menu
{

  public function __construct()
  {
    add_action('admin_menu', array($this, 'addBackendMenus'));
  }

  public function addBackendMenus()
  {
    $settingsPage = new Page();

    add_options_page(
      __('Supertext API - Einstellungen', 'polylang-supertext'),
      __('Supertext API', 'polylang-supertext'),
      'administrator',
      'supertext-polylang-settings',
      array($settingsPage, 'display')
    );
  }
} 