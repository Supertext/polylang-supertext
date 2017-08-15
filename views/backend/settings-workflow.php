<?php
use Supertext\Polylang\Helper\Constant;

/** @var \Supertext\Polylang\Helper\Library $library */
$workflowSettings = $library->getSettingOption(Constant::SETTING_WORKFLOW);

$publishOnCallbackChecked = isset($workflowSettings['publishOnCallback']) && $workflowSettings['publishOnCallback'] ? 'checked="checked"' : '';
$overridePublishedPostsChecked = isset($workflowSettings['overridePublishedPosts']) && $workflowSettings['overridePublishedPosts'] ? 'checked="checked"' : '';
$syncTranslationChangesChecked = isset($workflowSettings['syncTranslationChanges']) && $workflowSettings['syncTranslationChanges'] ? 'checked="checked"' : '';

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
      <input type="checkbox" id="sttr-sync-translation-changes" name="syncTranslationChanges"  <?php echo $syncTranslationChangesChecked; ?>/>
      <label for="sttr-sync-translation-changes"><?php _e('Synchronize translation changes', 'polylang-supertext'); ?></label>
    </p>
  </div>
</div>
