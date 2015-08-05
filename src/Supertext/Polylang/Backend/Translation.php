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

    // Load translations
    load_plugin_textdomain('polylang-supertext', false, 'polylang-supertext/resources/languages');
    load_plugin_textdomain('polylang-supertext-langs', false, 'polylang-supertext/resources/languages');
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
          resourceUrl : "' . get_bloginfo('wpurl') . '",
          addNewUser : "' . esc_js(__('Add user', 'polylang-supertext')) . '",
          inTranslationText : "' . esc_js(self::IN_TRANSLATION_TEXT) . '",
          deleteUser : "' . esc_js(__('Delete user', 'polylang-supertext')) . '",
          translationCreation : "' . esc_js(__('Translation is being initialized. Please wait a second.', 'polylang-supertext')) . '",
          generalError : "' . esc_js(__('An error occured.', 'polylang-supertext')) . '",
          offerTranslation : "' . esc_js(__('Order article translation', 'polylang-supertext')) . '",
          translationOrderError : "' . esc_js(__('The order couldn\'t be sent to Supertext. Please try again.', 'polylang-supertext')) . '",
          confirmUnsavedArticle : "' . esc_js(__('The article wasn\'t saved. If you proceed with the translation, the unsaved changes are lost.', 'polylang-supertext')) . '",
          alertUntranslatable : "' . esc_js(__('The article can\'t be translated, because it has an unfinished translation task. Please use the original article to order a translation.', 'polylang-supertext')) . '",
          offerConfirm_Price : "' . esc_js(__('You order a translation until {deadline}, for the price of {price}.', 'polylang-supertext')) . '",
          offerConfirm_Binding : "' . esc_js(__('This translation order is obliging.', 'polylang-supertext')) . '",
          offerConfirm_EmailInfo : "' . esc_js(__('You will be informed by e-mail as soon as the translation of your article is finished.', 'polylang-supertext')) . '",
          offerConfirm_Confirm : "' . esc_js(__('Please confirm your order.', 'polylang-supertext')) . '"
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