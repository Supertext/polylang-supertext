<?php
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

function getTree($nodes){
  $nodeHtml = '<ul>';

  foreach($nodes as $node){
    $id = $node['id'];
    $icon = $node['type'] === 'field' ? 'jstree-file' : 'jstree-folder';

    $nodeHtml .= '<li id="'.$id.'" data-jstree=\'{"icon":"'.$icon.'"}\'>';
    $nodeHtml .= $node['label'];

    if(count($node['field_definitions']) > 0){
      $nodeHtml .= getTree($node['field_definitions']);
    }

    $nodeHtml .= '</li>';
  }

  $nodeHtml .= '</ul>';

  return $nodeHtml;
}

/** @var Page $context */
$library = $context->getCore()->getLibrary();
$options = $library->getSettingOption();
$savedCustomFieldDefinitions = isset($options[Constant::SETTING_CUSTOM_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_CUSTOM_FIELDS]) : array();
$customFieldDefinitions = $context->getCustomFieldDefinitions();
$htmlTree = getTree($customFieldDefinitions);

$savedCustomFieldIds = array();
foreach ($savedCustomFieldDefinitions as $savedCustomFieldDefinition) {
  $savedCustomFieldIds[] = $savedCustomFieldDefinition['id'];
}

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Translatable Custom Fields', 'polylang-supertext'); ?></h3>
    <p>
      <?php _e('Please select the custom fields that can be used for translations.', 'polylang-supertext'); ?>
    </p>
    <div id="customFieldsTree">
      <?php echo $htmlTree; ?>
    </div>
    <input name="checkedCustomFieldIdsInput" id="checkedCustomFieldIdsInput" type="hidden" value="" />
  </div>
</div>

<script type="text/javascript">
  var savedCustomFieldIds = <?php echo json_encode($savedCustomFieldIds); ?>;
</script>