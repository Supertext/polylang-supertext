<?php
use Supertext\Helper\Constant;

/** @var \Supertext\Helper\Library $library */
$userMappings = $library->getSettingOption(Constant::SETTING_USER_MAP);

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
    '<option value=\'\'>'.__('Select user', 'supertext').'...</option>' .
    $userOptions .
  '</select>';

?>

<div class="postbox postbox_admin">
  <div id="frmSupertext" class="inside frmServiceSupertextConfig">
    <h3><?php _e('Supertext Accounts', 'supertext'); ?></h3>
    <p>
      <?php
        $url = 'https://www.supertext.ch/de/signup';
        printf( wp_kses( __('You need at least one Supertext Account. You can sign up <a href="%s">here</a>.', 'supertext'), array(  'a' => array( 'href' => array() ) ) ), esc_url( $url ) );
      ?>
    </p>
    <p>
      <?php
      _e('Please fill in a Supertext API key for every user.', 'supertext');
      ?>
      <?php
        $url = 'https://www.supertext.ch/customer/accountsettings';
        printf( wp_kses( __('You will find the API key on the <a href="%s">Supertext settings page</a>.', 'supertext'), array(  'a' => array( 'href' => array() ) ) ), esc_url( $url ) );
      ?><br>
      <?php _e('Only configured users can use the translation features.', 'supertext'); ?>
    </p>

    <table id="tblStFields">
      <thead>
        <tr>
          <th><?php _e('WordPress User', 'supertext'); ?></th>
          <th><?php _e('Supertext User', 'supertext'); ?></th>
          <th colspan="2"><?php _e('API Key', 'supertext'); ?></th>
        </tr>
      </thead>
      <tbody>
<?php
// Select correct dropdown value for WP User

$selectedWpUsers = '';
$deleteUser = __('Delete user', 'supertext');
$addUser = __('Add user', 'supertext');
$additionalEmptyUserRows = 1;

// Add five empty user rows, if there are no settings yet
if (count($userMappings) == 0) {
  $additionalEmptyUserRows = 5;
}

for ($i = 0; $i < $additionalEmptyUserRows; $i++) {
  $userMappings[] = array(
    'wpUser' => '',
    'stUser' => '',
    'stApi' => ''
  );
}

foreach ($userMappings as $userConfig) {
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
        <button type="button" class="button button-highlighted button-remove remove-user-button"><span class="dashicons dashicons-trash"></span></button>
      </td>
    </tr>';
}

echo '
      </tbody>
      </table>
      <input class="button button-highlighted button-add" type="button" id="btnAddUser" value="' . $addUser . '" >
    </div>
  </div>';
?>
