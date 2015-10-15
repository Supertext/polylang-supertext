<?php
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

/** @var Page $context */
$library = $context->getCore()->getLibrary();
$options = $library->getSettingOption();
$userMap = isset($options[Constant::SETTING_USER_MAP]) ? ArrayManipulation::forceArray($options[Constant::SETTING_USER_MAP]) : array();

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
  '<select name=\'selStWpUsers[]\'>' .
    '<option value=\'\'>'.__('Select user', 'polylang-supertext').'...</option>' .
    $userOptions .
  '</select>';

?>

<div class="postbox postbox_admin">
  <div id="frmSupertext" class="inside frmServiceSupertextConfig">
    <h3><?php _e('Supertext Accounts', 'polylang-supertext'); ?></h3>
    <p>
      <?php
      $url = 'https://www.supertext.ch/de/signup';
      echo sprintf( wp_kses( __("You need at least one Supertext Account. You can sign up <a href=\"%s\">here</a>.", 'polylang-supertext'), array(  'a' => array( 'href' => array() ) ) ), esc_url( $url ) );
      ?>
    </p>
    <p>
      <?php _e('Please fill in a Supertext API key for every WordPress user.', 'polylang-supertext'); ?><br>
      <?php _e('Only configured users can make use of the translation features.', 'polylang-supertext'); ?>
    </p>

    <table id="tblStFields">
      <thead>
        <tr>
          <th>Wordpress <?php _e('User', 'polylang-supertext'); ?></th>
          <th>Supertext <?php _e('User', 'polylang-supertext'); ?></th>
          <th colspan="2"><?php _e('API-Key', 'polylang-supertext'); ?></th>
        </tr>
      </thead>
      <tbody>
<?php
// Select correct dropdown value for WP User

$selectedWpUsers = '';
$deleteUser = __('Delete user', 'polylang-supertext');
$addUser = __('Add user', 'polylang-supertext');
$additionalEmptyUserRows = 1;

// Add five empty user rows, if there are no settings yet
if (count($userMap) == 0) {
  $additionalEmptyUserRows = 5;
}

for ($i = 0; $i < $additionalEmptyUserRows; $i++) {
  $userMap[] = array(
    'wpUser' => '',
    'stUser' => '',
    'stApi' => ''
  );
}

foreach ($userMap as $userConfig) {
  echo '<tr>
      <td>
        ' . $selectTemplate . '
        <input type="hidden" class="saved-user-id-hidden" value="'. $userConfig['wpUser'].'" />
      </td>
      <td>
        <input type="text" name="fieldStUser[]" value="' . $userConfig['stUser'] . '" style="width: 200px">
      </td>
      <td>
        <input type="text" name="fieldStApi[]" value="' . $userConfig['stApi'] . '" style="width: 200px">
      </td>
      <td>
        <img class="remove-user-button" src="' . SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/delete.png" alt="" title="' . $deleteUser . '">
      </td>
    </tr>';
}

echo '
      </tbody>
      </table>
      <br />
      <input class="button button-highlighted" type="button" id="btnAddUser" value="' . $addUser . '" >
    </div>
  </div>';
?>