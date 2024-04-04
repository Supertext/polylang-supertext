<?php
use Supertext\Helper\Constant;

/** @var \Supertext\Helper\Library $library */
$workflowSettings = $library->getSettingOption(Constant::SETTING_WORKFLOW);

$publishOnCallbackChecked = isset($workflowSettings['publishOnCallback']) && $workflowSettings['publishOnCallback'] ? 'checked="checked"' : '';
$overridePublishedPostsChecked = isset($workflowSettings['overridePublishedPosts']) && $workflowSettings['overridePublishedPosts'] ? 'checked="checked"' : '';

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Workflow', 'supertext'); ?></h3>
    <p>
      <input type="checkbox" id="sttr-publish-on-callback" name="publishOnCallback" <?php echo $publishOnCallbackChecked; ?> />
      <label for="sttr-publish-on-callback"><?php _e('Automatically publish translations', 'supertext'); ?></label>
    </p>
    <p>
      <input type="checkbox" id="sttr-override-published-posts" name="overridePublishedPosts"  <?php echo $overridePublishedPostsChecked; ?>/>
      <label for="sttr-override-published-posts"><?php _e('Allow Supertext to overwrite published posts', 'supertext'); ?></label>
    </p>
  </div>
</div>
