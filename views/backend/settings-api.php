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

$selectedServiceType = isset($apiSettings['serviceType']) ? $apiSettings['serviceType'] : Constant::DEFAULT_SERVICE_TYPE;

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
    <p>
      <label for="sttr-service-type"><?php _e('Service type', 'polylang-supertext'); ?></label>
      <input type="number" min="1" max="5" id="sttr-service-type" name="serviceType" value="<?php echo $selectedServiceType; ?>">
    </p>
  </div>
</div>
