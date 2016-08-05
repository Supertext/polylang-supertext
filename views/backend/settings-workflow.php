<?php
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

$workflowSettings = $context->getSettingOption(Constant::SETTING_WORKFLOW);

$publishOnCallbackChecked = isset($workflowSettings['publishOnCallback']) && $workflowSettings['publishOnCallback'] ? 'checked="checked"' : '';
$overridePublishedPostsChecked = isset($workflowSettings['overridePublishedPosts']) && $workflowSettings['overridePublishedPosts'] ? 'checked="checked"' : '';
$selectedApiServer = isset($workflowSettings['apiServerUrl']) ? $workflowSettings['apiServerUrl'] : Constant::LIVE_API;

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
    <h3><?php _e('Workflow', 'polylang-supertext'); ?></h3>
    <p>
      <input type="checkbox" id="sttr-publish-on-callback" name="publishOnCallback" <?php echo $publishOnCallbackChecked; ?> />
      <label for="sttr-publish-on-callback"><?php _e('Automatically publish translations', 'polylang-supertext'); ?></label>
    </p>
    <p>
      <input type="checkbox" id="sttr-override-published-posts" name="overridePublishedPosts"  <?php echo $overridePublishedPostsChecked; ?>/>
      <label for="sttr-override-published-posts"><?php _e('Allow Supertext to overwrite published posts', 'polylang-supertext'); ?></label>
    </p>
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