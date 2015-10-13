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

    if(count($node['fields']) > 0){
      $nodeHtml .= getTree($node['fields']);
    }

    $nodeHtml .= '</li>';
  }

  $nodeHtml .= '</ul>';

  return $nodeHtml;
}

/** @var Page $context */
$library = $context->getCore()->getLibrary();
$options = $library->getSettingOption();
$savedCustomFields = isset($options[Constant::SETTING_CUSTOM_FIELDS]) ? ArrayManipulation::forceArray($options[Constant::SETTING_CUSTOM_FIELDS]) : array();
$customFields = $context->getCustomFields();
$htmlTree = getTree($customFields);

$savedCustomFieldIds = array();
foreach ($savedCustomFields as $savedCustomField) {
  $savedCustomFieldIds[] = $savedCustomField['id'];
}

?>
<p>
  <?php _e('Please select the custom fields that can be used for translations.', 'polylang-supertext'); ?>
</p>
<div id="customFieldsTree">
  <?php echo $htmlTree; ?>
</div>
<input name="checkedCustomFieldIdsInput" id="checkedCustomFieldIdsInput" type="hidden" value="" />

<script type="text/javascript">
  var savedCustomFieldIds = <?php echo json_encode($savedCustomFieldIds); ?>
</script>