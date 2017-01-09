<?php
use Supertext\Polylang\Helper\Constant;

/** @var \Supertext\Polylang\Helper\Library $library */
$shortcodeTags = $library->getShortcodeTags();
$savedShortcodes = $library->getSettingOption(Constant::SETTING_SHORTCODES);

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Shortcodes', 'polylang-supertext'); ?></h3>
    <p>
      <?php _e('Please define any shortcode attributes you want to have translated.', 'polylang-supertext'); ?>
    </p>
    <p>
      <?php _e('Please define the encoding process for all encoded and enclosed content. Available encoding functions are: rawurl, url and base64', 'polylang-supertext'); ?>
    </p>
    <div id="shortcode-settings"></div>
    <button type="button" class="button button-highlighted button-add shortcode-add-setting"><?php _e('Add setting', 'polylang-supertext'); ?></button>
  </div>
</div>

<script type="text/javascript">
  var availableEncodingFunctions = <?php echo json_encode(array( "rawurl", "url", "base64")); ?>;
  var registeredShortcodes = <?php echo json_encode(array_keys($shortcodeTags)); ?>;
  var savedShortcodes = <?php echo json_encode($savedShortcodes); ?>;
</script>
