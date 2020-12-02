<?php

namespace Supertext\Backend;


/**
 * The menu handler
 * @package Supertext\Backend
 * @author Michael Sebel <michael@comotive.ch>
 */
class Menu
{
  private $settingsPage;

  /**
   * Injects the registration of settings menu
   */
  public function __construct($settingsPage)
  {
    $this->settingsPage = $settingsPage;

    add_action('admin_menu', array($this, 'addBackendMenus'));
  }

  /**
   * Add option page for supertext settings
   */
  public function addBackendMenus()
  {
    add_options_page(
      __('Supertext - Settings', 'supertext'),
      __('Supertext', 'supertext'),
      'administrator',
      'supertext-settings',
      array($this->settingsPage, 'display')
    );
  }
}