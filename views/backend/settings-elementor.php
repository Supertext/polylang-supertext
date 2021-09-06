<?php

function getElementorTextPropertyInput($value = '')
{
  return '<div class="settings-input-element">
      <input type="text" name="elementor-text-properties[]" placeholder="' . __('Property name...', 'supertext') . '" value="' . $value . '"/>
      <button type="button" class="button button-highlighted button-remove field-remove-input"><span class="dashicons dashicons-trash"></span></button>
    </div>';
}

$elementorTextPropertyInputs = '';

foreach ($elementorTextProperties as $elementorTextProperty) {
  $elementorTextPropertyInputs .= getElementorTextPropertyInput($elementorTextProperty);
}

?>
<div class="postbox postbox_admin">
  <div class="inside settings-input-list">
    <h3><?php _e('Elementor (Plugin)', 'supertext'); ?></h3>
    <p>
      <?php _e('Please add the text properties of the Elementor elements.', 'supertext'); ?>
    </p>
    <?php echo $elementorTextPropertyInputs; ?>
    <?php echo getElementorTextPropertyInput(); ?>
    <button type="button" class="button button-highlighted button-add field-add-input" data-input-name="elementor-field"><?php _e('Add text property', 'supertext'); ?></button>
  </div>
</div>