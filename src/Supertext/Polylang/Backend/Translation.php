<?php

namespace Supertext\Polylang\Backend;
use Supertext\Polylang\Core;
use Supertext\Polylang\Helper\Constant;

/**
 * Serves as a helper for the translation inject to the user
 * @package Supertext\Polylang\Backend
 * @author Michael Sebel <michael@comotive.ch>
 */
class Translation
{
  /**
   * @var string the text that marks a post as "in translation"
   */
  const IN_TRANSLATION_TEXT = '[in Translation...]';
  /**
   * Various filters to change and/or display things
   */
  public function __construct()
  {
    add_action('admin_init', array($this, 'addBackendAssets'));
    add_action('current_screen', array($this, 'addScreenbasedAssets'));
    add_action('admin_footer', array($this, 'addTranslations'));
    add_action('admin_footer', array($this, 'printWorkingState'));
    add_action('media_upload_gallery', array($this, 'disableGalleryInputs'));

    // Only autosave translation if necessary
    if ($_GET['translation-service'] == 1) {
      add_filter('default_title', array($this, 'filterTranslatingPost'), 10, 2);
    }
  }

  /**
   * Directly save a new post in translation and redirect to edit screen
   * @param $postContent the post content
   * @param $post the post object
   * @return string the title (1:1, if not a translating post
   */
  public function filterTranslatingPost($postContent, $post)
  {
    // Set state to prevent override the title attribute with emtpy (default state: auto-draft)
    $post->post_status = 'draft';

    // Go trough post data and add translation text value
    foreach ($_POST as $field_name => $field_value) {
      $field_name_parts = explode('_', $field_name);
      // Fields with text definition
      if ($field_name_parts[0] == 'to') {
        switch ($field_name_parts[1]) {
          case 'post':
            switch ($field_name_parts[2]) {
              case 'image':
                // Set all images to default
                $attachments = get_children(array('post_parent' => $post->ID, 'post_type' => 'attachment', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC'));
                foreach ($attachments as $attachement_post) {
                  $attachement_post->post_title = self::IN_TRANSLATION_TEXT;
                  $attachement_post->post_content = self::IN_TRANSLATION_TEXT;
                  $attachement_post->post_excerpt = self::IN_TRANSLATION_TEXT;
                  // Update meta and update attachmet post
                  update_post_meta($attachement_post->ID, '_wp_attachment_image_alt', addslashes(self::IN_TRANSLATION_TEXT));
                  wp_update_post($attachement_post);
                }
                break;

              default:
                // Translate a wp defualt field
                $post->{'post_' . $field_name_parts[2]} = self::IN_TRANSLATION_TEXT;
                break;
            }
            break;

          case 'excerpt':
            // Falls Service mit Feature (ShareArticle)
            update_post_meta($post->ID, '_excerpt_' . $field_name_parts[2], self::IN_TRANSLATION_TEXT);
            update_post_meta($post->ID, '_modified_excerpt_' . $field_name_parts[2], 1);
            break;

          default:
            break;
        }
      }
    }

    // Save the changed post
    // A JS listeing to translation-service=1 will automatically save the new translation
    wp_update_post($post);

    // Return the same untouched title, if nothing should happen
    return $post->post_title;
  }

  /**
   * Only includes resources for post translation management
   * @param \WP_Screen $screen the screen shown
   */
  public function addScreenbasedAssets($screen)
  {
    if ($screen->base == 'post' && ($_GET['action'] == 'edit' || $_GET['translation-service'] == 1)) {
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
          inTranslationText : "' . esc_js(self::IN_TRANSLATION_TEXT) . '",
          deleteUser : "' . esc_js(__('Benutzer entfernen', 'polylang-supertext')) . '",
          translationCreation : "' . esc_js(__('Die Übersetzung wird initialisiert, bitte warten.', 'polylang-supertext')) . '",
          generalError : "' . esc_js(__('Es ist ein Fehler aufgetreten.', 'polylang-supertext')) . '",
          offerTranslation : "' . esc_js(__('Übersetzung beauftragen', 'polylang-supertext')) . '",
          translationOrderError : "' . esc_js(__('Der Auftrag konnte nicht an Supertext gesendet werden. Bitte versuchen Sie es erneut.', 'polylang-supertext')) . '",
          confirmUnsavedArticle : "' . esc_js(__('Der Artikel wurde nicht gespeichert.\nFalls du mit der Übersetzung fortfährst, werden deine Änderungen verworfen.', 'polylang-supertext')) . '",
          alertUntranslatable : "' . esc_js(__('Der Artikel kann nicht übersetzt werden, da dieser selbst noch übersetzt wird.\nBitte verwende den Original Artikel zur Übersetzung.', 'polylang-supertext')) . '",
          offerConfirm_Price : "' . esc_js(__('Sie bestellen eine Übersetzung bis zum {deadline} Uhr, zum Preis von {price}.', 'polylang-supertext')) . '",
          offerConfirm_Binding : "' . esc_js(__('Diese Übersetzungsbeauftragung ist verbindlich.', 'polylang-supertext')) . '",
          offerConfirm_EmailInfo : "' . esc_js(__('Sie werden per E-Mail informiert, sobald die Übersetzung abgeschlossen wurde.', 'polylang-supertext')) . '",
          offerConfirm_Confirm : "' . esc_js(__('Bitte bestätigen Sie die Bestellung.', 'polylang-supertext')) . '"
        };
      </script>
    ';
  }

  /**
   * Print a working state hidden field
   */
  public function printWorkingState()
  {
    $working = 1;
    $library = Core::getInstance()->getLibrary();

    // See if the plugin is generally working
    if (!$library->isWorking()) {
      $working = 0;
    }

    // See if the user has credentials
    $userId = get_current_user_id();
    $cred = $library->getUserCredentials($userId);

    // Check credentials and api key
    if (strlen($cred['stUser']) == 0 || strlen($cred['stApi']) == 0 || $cred['stUser'] == Constant::DEFAULT_API_USER) {
      $working = 0;
    }

    // Print the field
    echo '<input type="hidden" id="supertextPolylangWorking" value="' . $working . '" />';
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