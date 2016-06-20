<?php
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

$options = $context->getSettingOption();
$workflowSettings = isset($options[Constant::SETTING_WORKFLOW]) ? ArrayManipulation::forceArray($options[Constant::SETTING_WORKFLOW]) : array();

$publishOnCallbackChecked = $workflowSettings['publishOnCallback'] ? 'checked="checked"' : '';
$overridePublishedPostsChecked = $workflowSettings['overridePublishedPosts'] ? 'checked="checked"' : '';
?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Workflow', 'polylang-supertext'); ?></h3>
    <table id="tblStFields">
      <tbody>
        <tr>
          <td><input type="checkbox" id="chbxPublishOnCallback" name="publishOnCallback" <?php echo $publishOnCallbackChecked; ?> /></td>
          <td><label for="chbxPublishOnCallback"><?php _e('Automatically publish translations', 'polylang-supertext'); ?></label></td>
        </tr>
        <tr>
          <td><input type="checkbox" id="chbxOverridePublishedPosts" name="overridePublishedPosts"  <?php echo $overridePublishedPostsChecked; ?>/></td>
          <td><label for="chbxOverridePublishedPosts"><?php _e('Allow Supertext to overwrite published posts', 'polylang-supertext'); ?></label></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

