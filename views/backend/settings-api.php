<?php
use Supertext\Helper\Constant;

/** @var \Supertext\Helper\Library $library */
$apiSettings = $library->getSettingOption(Constant::SETTING_API);

$selectedApiServer = isset($apiSettings['apiServerUrl']) ? $apiSettings['apiServerUrl'] : Constant::LIVE_API;

$options = array(
  Constant::LIVE_API => __('Live', 'supertext'),
  Constant::DEV_API => __('Development', 'supertext')
);

if(!isset($options[$selectedApiServer])){
  $options[$selectedApiServer] = __('Other', 'supertext').'...';
}else{
  $options[''] = __('Other', 'supertext').'...';
}

$selectedServiceType = isset($apiSettings['serviceType']) ? $apiSettings['serviceType'] : Constant::DEFAULT_SERVICE_TYPE;
$selectedServiceTypePr = isset($apiSettings['serviceTypePr']) ? $apiSettings['serviceTypePr'] : Constant::DEFAULT_SERVICE_TYPE_PR;

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('API', 'supertext'); ?></h3>
    <p>
      <label for="sttr-api-selection"><?php _e('API Server', 'supertext'); ?></label>
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
      <label for="sttr-service-type"><?php _e('Service type for translations', 'supertext'); ?></label>
      <input type="number" min="1" max="5" id="sttr-service-type" name="serviceType" value="<?php echo $selectedServiceType; ?>">
    </p>
    <p>
      <label for="sttr-service-type-pr"><?php _e('Service type for proofreading', 'supertext'); ?></label>
      <input type="number" id="sttr-service-type-pr" name="serviceTypePr" value="<?php echo $selectedServiceTypePr; ?>">
    </p>
  </div>
</div>
