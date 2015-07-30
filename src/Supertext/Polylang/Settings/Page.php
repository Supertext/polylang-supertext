<?php

namespace Supertext\Polylang\Settings;
use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Helper\Constant;

/**
 * The supertext / polylang main settings page
 * @package Supertext\Polylang\Settings
 * @author Michael Sebel <michael@comotive.ch>
 */
class Page extends AbstractPage
{

  /**
   * Displays the main settings page
   */
  public function display()
  {
    // Display the page with typical entry infos
    echo '
      <div class="wrap">
        <h2>' . __('Settings › Supertext API', 'polylang-supertext') . '</h2>
        ' . $this->addResources() . '
        ' . $this->showSystemMessage() . '
        <form method="post" action="' . get_admin_url() . 'options-general.php?page=' . $_GET['page'] . '">
    ';

    // Include the views
    $this->includeView('backend/settings-users', $this);
    $this->includeView('backend/settings-languages', $this);

    // Close the form
    echo '
        <p><input type="submit" class="button button-primary" name="saveStPlSettings" value="' . __('Save settings', 'polylang-supertext') . '" /></p>
      </form>
    </div>
    ';
  }

  /**
   * Add js/css resources needed on this page
   * I know this is crap, will be fixed in near future
   */
  protected function addResources()
  {
    return '
      <link rel="stylesheet" href="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/styles/style.css?v=' . SUPERTEXT_PLUGIN_REVISION . '" />
      <script type="text/javascript" src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/settings-library.js?v=' . SUPERTEXT_PLUGIN_REVISION . '"></script>
    ';
  }

  /**
   * @return string system message, if given, otherwise void
   */
  protected function showSystemMessage()
  {
    if ($_REQUEST['message'] == 'saved') {
      return '
        <div id="message" class="updated fade">
          <p>' . __('Settings saved', 'polylang-supertext') . '</p>
        </div>
      ';
    }
  }

  /**
   * Saves user and language settings to options
   */
  public function control()
  {
    if (isset($_POST['saveStPlSettings'])) {
      // Saving the user mappings
      $userMap = array();
      foreach ($_POST['selStWpUsers'] as $key => $id) {
        if (intval($id) > 0) {
          $userMap[] = array(
            'wpUser' => intval($_POST['selStWpUsers'][$key]),
            'stUser' => $_POST['fieldStUser'][$key],
            'stApi' => $_POST['fieldStApi'][$key]
          );
        }
      }

      // Crappily create the language array
      $languageMap = array();
      foreach ($_POST as $postField => $stLanguage) {
        if (substr($postField, 0, strlen('sel_st_language_')) == 'sel_st_language_') {
          $language = substr($postField, strlen('sel_st_language_'));
          $languageMap[$language] = $stLanguage;
        }
      }

      // Put into the options
      $this->library->saveSetting(Constant::SETTING_USER_MAP, $userMap);
      $this->library->saveSetting(Constant::SETTING_LANGUAGE_MAP, $languageMap);

      // Set the plugin to working mode, if both arrays are saved
      if (count($userMap) > 0 && count($languageMap) == count(Multilang::getLanguages())) {
        $this->library->saveSetting(Constant::SETTING_WORKING, 1);
      } else {
        $this->library->saveSetting(Constant::SETTING_WORKING, 0);
      }

      // Redirect to same page with message
      wp_redirect(get_admin_url() . 'options-general.php?message=saved&page=' . $_GET['page']);
    }
  }
} 