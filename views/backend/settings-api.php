<?php
use Supertext\Polylang\Helper\Constant;

/** @var \Supertext\Polylang\Helper\Library $library */
$apiSettings = $library->getSettingOption(Constant::SETTING_API);

$selectedApiServer = isset($apiSettings['apiServerUrl']) ? $apiSettings['apiServerUrl'] : Constant::LIVE_API;

$options = array(
  Constant::LIVE_API => __('Live', 'polylang-supertext'),
  Constant::DEV_API => __('Development', 'polylang-supertext')
);

if(!isset($options[$selectedApiServer])){
  $options[$selectedApiServer] = __('Other', 'polylang-supertext').'...';
}else{
  $options[''] = __('Other', 'polylang-supertext').'...';
}

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('API', 'polylang-supertext'); ?></h3>
    <p>
      <label for="sttr-api-selection"><?php _e('API Server', 'polylang-supertext'); ?></label>
      <select id="sttr-api-selection">
        <?php
          foreach($options as $value => $text){
            $selected = $value === $selectedApiServer ? 'selected' : '';
            echo "<option value=\"$value\" $selected>$text</option>";
          }
        ?>
      </select>
      <input type="text" class="sttr-api-url" id="sttr-api-url" name="apiServerUrl" value="<?php echo $selectedApiServer; ?>">
    </p>
  </div>
</div>
