<?php
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

function getTree($nodes){
  $nodeHtml = '<ul>';

  foreach($nodes as $node){
    $key = $node['key'];

    $nodeHtml .= '<li id="'.$key.'">';
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


$savedCustomFieldKeys = array();

foreach ($savedCustomFields as $translatableCustomField) {
  $savedCustomFieldKeys[] = $translatableCustomField['key'];
}

?>

<div id="customFieldsTree">
  <?php echo $htmlTree; ?>
</div>
<input name="checkedCustomFieldKeysInput" id="checkedCustomFieldKeysInput" type="hidden" value="" />

<script type="text/javascript">
  var savedCustomFieldKeys = <?php echo json_encode($savedCustomFieldKeys); ?>
</script>