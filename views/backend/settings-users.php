<?php
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Settings\Page;
use Comotive\Util\ArrayManipulation;

/** @var Page $context */
$library = $context->getCore()->getLibrary();
$options = $library->getSettingOption();
$userMap = ArrayManipulation::forceArray($options[Constant::SETTING_USER_MAP]);

// Get all users
$wpUsers = get_users();
$userList = array();
$userOptions = '';

foreach ((array) $wpUsers as $wpUser) {
  $userList[] = $wpUser->display_name . '$$' . $wpUser->data->user_login . '$$' . $wpUser->data->ID;
}

natcasesort($userList);
foreach ((array) $userList as $user) {
  $user = explode('$$', $user);
  $userOptions .= '<option value=\'' . $user[2] . '\'>' . $user[0] . ' (' . $user[1] . ')</option>';
}

// Generate select template
$selectTemplate = '' .
  '<select name=\'selStWpUsers[]\' id=\'selStWpUsers\'>' .
    '<option value=\'0\'>Benutzer wählen</option>' .
    $userOptions .
  '</select>';

?>
<input type="hidden" name="supertext_select_user" id="supertext_select_user" value="<?php echo $selectTemplate; ?>">
<input type="hidden" name="supertext_file_path" id="supertext_file_path" value="<?php echo SUPERTEXT_POLYLANG_RESOURCE_URL; ?>">

<div class="postbox postbox_admin">
  <div id="frmSupertext" class="inside frmServiceSupertextConfig">
    <h3><?php _e('Supertext Accounts', 'polylang-supertext'); ?></h3>
    <table id="tblStFields">
      <thead>
        <tr>
          <td colspan="5">
            <?php _e('Bitte geben Sie für jeden Benutzer den API-Key von Supertext ein.', 'polylang-supertext'); ?><br>
            <?php _e('Nur hier erfasste Benutzer können Artikel übersetzen lassen.', 'polylang-supertext'); ?><br><br>
          </td>
        </tr>
        <tr>
          <td><strong>Wordpress <?php _e('Benutzer', 'polylang-supertext'); ?></strong></td>
          <td><strong>Supertext <?php _e('Benutzer', 'polylang-supertext'); ?></strong></td>
          <td colspan="3"><strong>API Key</strong></td>
        </tr>
      </thead>
      <tbody>
<?php

// Add five empty ones, if there are no settings yet
if (count($userMap) == 0) {
  for ($i = 0; $i < 5; $i++) {
    $userMap[] = array(
      'wpUser' => '',
      'stUser' => '',
      'stApi' => ''
    );
  }
}

// Select correct dropdown value for WP User
$index = 0;
$selectedWpUsers = '';
$deleteUser = __('Benutzer entfernen', 'polylang-supertext');
$addUser = __('Benutzer hinzufügen', 'polylang-supertext');


foreach ($userMap as $userConfig) {
  $index++;
  $sStyle_Display = 'display: none;';
  $selectedWpUsers .= intval($userConfig['wpUser']) . ', ';

  echo '
    <tr id="trSupertext_' . $index . '">
      <td>
        ' . $selectTemplate . '
      </td>
      <td>
        <input type="text" name="fieldStUser[]" id="field_st_user_' . $index . '" value="' . $userConfig['stUser'] . '" style="width: 200px">
      </td>
      <td>
        <input type="text" name="fieldStApi[]" id="field_st_api_' . $index . '" value="' . $userConfig['stApi'] . '" style="width: 200px">
      </td>
      <td>
        <img src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/delete.png" alt="" title="' . $deleteUser . '" onclick="javascript: Remove_StField(' . $index . ');">
      </td>
      <td>
        <input class="button button-highlighted" style="' . $sStyle_Display . '" type="button" name="buAddField_' . $index . '" id="buAddField_' . $index . '" value="' . $addUser . '" onclick="javascript: Add_StField(' . $i . ');">
      </td>
    </tr>
  ';
}

echo '
      </tbody>
      </table>
    </div>
  </div>';

if (strlen($selectedWpUsers) > 0) {
  $selectedWpUsers = substr($selectedWpUsers, 0, -2);
}

?>
<script type="text/javascript">
  jQuery(document).ready(function() {
    set_selects_indexes([<?php echo $selectedWpUsers; ?>]);
  });
</script>