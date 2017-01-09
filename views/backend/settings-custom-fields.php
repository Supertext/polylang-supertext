<?php

function getCustomFieldInput($value=''){
  return '<div class="custom-field-input">
      <input type="text" name="custom-fields[]" placeholder="'.__('Custom field name...', 'polylang-supertext').'" value="'.$value.'"/>
      <button type="button" class="button button-highlighted button-remove custom-field-remove-input"><span class="dashicons dashicons-trash"></span></button>
    </div>';
}

$savedCustomFieldsInputs = '';

foreach($savedCustomFields as $savedCustomField){
  $savedCustomFieldsInputs .= getCustomFieldInput($savedCustomField);
}

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('General custom fields', 'polylang-supertext'); ?></h3>
    <p>
      <?php _e('Please add the custom fields that can be used for translations.', 'polylang-supertext'); ?>
    </p>
    <?php echo $savedCustomFieldsInputs; ?>
    <?php echo getCustomFieldInput(); ?>
    <button type="button" class="button button-highlighted button-add custom-field-add-input"><?php _e('Add field', 'polylang-supertext'); ?></button>
  </div>
</div>

