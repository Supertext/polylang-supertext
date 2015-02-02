<?php

namespace Supertext\Polylang\Backend;

/**
 * Serves as a helper for the translation inject to the user
 * @package Supertext\Polylang\Backend
 * @author Michael Sebel <michael@comotive.ch>
 */
class Translation
{
  /**
   * Various filters to change and/or display things
   */
  public function __construct()
  {
    add_action('admin_init', array($this, 'addBackendAssets'));
    add_action('current_screen', array($this, 'addScreenbasedAssets'));
    add_action('admin_footer', array($this, 'addTranslations'));
    add_action('media_upload_gallery', array($this, 'disableGalleryInputs'));
  }

  /**
   * Only includes resources for post translation management
   * @param \WP_Screen $screen the screen shown
   */
  public function addScreenbasedAssets($screen)
  {
    if ($screen->base == 'post' && $_GET['action'] == 'edit') {
      // SCripts to inject translation
      wp_enqueue_script(
        'supertext-translation-library',
        SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/translation-library.js',
        array('jquery', 'supertext-global-library'),
        SUPERTEXT_PLUGIN_REVISION,
        true
      );

      // Styles for post backend and offer page
      wp_enqueue_style(
        'supertext-post-style',
        SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/post.css',
        array(),
        SUPERTEXT_PLUGIN_REVISION
      );
    }
  }

  /**
   * Add the global backend libraries and css
   */
  public function addBackendAssets()
  {
    wp_enqueue_script(
      'supertext-global-library',
      SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/global-library.js',
      array('jquery'),
      SUPERTEXT_PLUGIN_REVISION,
      false
    );
  }

  /**
   * Add JS translations to the i18n Supertext object that has already been loaded now
   */
  public function addTranslations()
  {
    echo '
      <script type="text/javascript">
        Supertext.i18n = {
          addNewUser : "' . esc_js(__('Benutzer hinzufügen', 'polylang-supertext')) . '",
          deleteUser : "' . esc_js(__('Benutzer entfernen', 'polylang-supertext')) . '",
          generalError : "' . esc_js(__('Es ist ein Fehler aufgetreten.', 'polylang-supertext')) . '",
          offerTranslation : "' . esc_js(__('Übersetzung beauftragen', 'polylang-supertext')) . '",
          confirmUnsavedArticle : "' . esc_js(__('Der Artikel wurde nicht gespeichert.\nFalls du mit der Übersetzung fortfährst, werden deine Änderungen verworfen.', 'polylang-supertext')) . '",
          alertUntranslatable : "' . esc_js(__('Der Artikel kann nicht übersetzt werden, da dieser selbst noch übersetzt wird.\nBitte verwende den Original Artikel zur Übersetzung.', 'polylang-supertext')) . '"
        };
      </script>
    ';
  }

  /**
   * Disable gallery inputs (only called if the media viewer is opened
   */
  public function disableGalleryInputs()
  {
    echo '
      <script type="text/javascript">
        Supertext.Polylang.disableGalleryInputs();
      </script>
    ';
  }
} 