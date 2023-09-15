<?php

use Supertext\Helper\Constant;

/** @var \Supertext\Helper\Library $library */
$shortcodeTags = $library->getShortcodeTags();
$shortcodeSettings = $library->getSettingOption(Constant::SETTING_SHORTCODES);

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Shortcodes', 'supertext'); ?></h3>
    <p>
      <?php _e('Please define any shortcode attributes you want to have translated.', 'supertext'); ?>
    </p>
    <p>
      <?php _e('Please define the encoding process for all encoded and enclosed content. Available encoding functions are: rawurl, url and base64', 'supertext'); ?>
    </p>
    <div>
      <label title="<?php _e('When deactivated, shortcodes will be sent as is (not rendered) to Supertext.', 'supertext'); ?>">
        <input type="checkbox" id="disable-shortcode-replacement" name="disable-shortcode-replacement" <?php echo $shortcodeSettings['isShortcodeReplacementDisabled'] ?  "checked" : "" ?> />
        <?php _e('Deactivate shortcode processing', 'supertext'); ?>
      </label>
    </div>
    <div id="shortcode-settings"></div>
    <button type="button" class="button button-highlighted button-add shortcode-add-setting"><?php _e('Add setting', 'supertext'); ?></button>
  </div>
</div>

<script type="text/javascript">
  var availableEncodingFunctions = <?php echo json_encode(array("rawurl", "url", "base64")); ?>;
  var registeredShortcodes = <?php echo json_encode(array_keys($shortcodeTags)); ?>;
  var savedShortcodes = <?php echo json_encode($shortcodeSettings["shortcodes"]); ?>;
</script>
