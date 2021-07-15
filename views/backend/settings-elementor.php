<?php

function getTextKeyInput($value = '')
{
  return '<div class="custom-field-input">
        <input type="text" name="elementor-text-keys[]" placeholder="' . __('Text property name...', 'supertext') . '" value="' . $value . '"/>
        <button type="button" class="button button-highlighted button-remove custom-field-remove-input"><span class="dashicons dashicons-trash"></span></button>
      </div>';
}

$savedTextKeyInputs = '';

foreach ($savedTextKeys as $savedTextKey) {
  $savedTextKeyInputs .= getTextKeyInput($savedTextKey);
}

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Elementor (Plugin)', 'supertext'); ?></h3>
    <p>
      <?php _e('Please add the elementor settings properties that contain text to translate.', 'supertext'); ?>
    </p>
    <?php echo $savedTextKeyInputs; ?>
    <?php echo getTextKeyInput(); ?>
    <button type="button" class="button button-highlighted button-add custom-field-add-input"><?php _e('Add text property', 'supertext'); ?></button>
  </div>
</div>