<?php

$htmlListTree = $this->convertToHtmlListTree($fieldDefinitions);

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php echo $pluginName; ?></h3>
    <p>
      <?php _e('Please select the custom fields that can be used for translations.', 'polylang-supertext'); ?>
    </p>
    <div id="fieldDefinitionsTree<?php echo $pluginId; ?>">
      <?php echo $htmlListTree; ?>
    </div>
    <input name="pluginCustomFields[<?php echo $pluginId ?>][checkedFields]" id="checkedFieldsInput<?php echo $pluginId; ?>" type="hidden" value="" />
  </div>
</div>

<script type="text/javascript">
  var savedFieldDefinitionIds = savedFieldDefinitionIds || {};
  savedFieldDefinitionIds['<?php echo $pluginId; ?>'] = <?php echo json_encode($savedFieldDefinitionIds); ?>;
</script>