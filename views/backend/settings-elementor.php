<?php

function getElementorTextPropertyInput($value = '')
{
  return '<div class="custom-field-input">
        <input type="text" name="elementor-text-properties[]" placeholder="' . __('Property name...', 'supertext') . '" value="' . $value . '"/>
        <button type="button" class="button button-highlighted button-remove custom-field-remove-input"><span class="dashicons dashicons-trash"></span></button>
      </div>';
}

$elementorTextPropertyInputs = '';

foreach ($elementorTextProperties as $elementorTextProperty) {
  $elementorTextPropertyInputs .= getElementorTextPropertyInput($elementorTextProperty);
}

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Elementor (Plugin)', 'supertext'); ?></h3>
    <p>
      <?php _e('Please add the text properties of the Elementor elements.', 'supertext'); ?>
    </p>
    <?php echo $elementorTextPropertyInputs; ?>
    <?php echo getElementorTextPropertyInput(); ?>
    <button type="button" class="button button-highlighted button-add custom-field-add-input"><?php _e('Add text property', 'supertext'); ?></button>
  </div>
</div>