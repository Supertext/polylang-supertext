<?php
function getPcfTree($nodes){
  $nodeHtml = '<ul>';

  foreach($nodes as $id => $node){
    $icon = $node['type'] === 'field' ? 'jstree-file' : 'jstree-folder';

    $nodeHtml .= '<li id="'.$id.'" data-jstree=\'{"icon":"'.$icon.'"}\'>';
    $nodeHtml .= $node['label'];

    if(!empty($node['sub_field_definitions']) && count($node['sub_field_definitions']) > 0){
      $nodeHtml .= getPcfTree($node['sub_field_definitions']);
    }

    $nodeHtml .= '</li>';
  }

  $nodeHtml .= '</ul>';

  return $nodeHtml;
}

$htmlTree = getPcfTree($pcfFieldDefinitions);

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Plugin fields', 'polylang-supertext'); ?></h3>
    <p>
      <?php _e('Please select the fields that can be used for translations.', 'polylang-supertext'); ?>
    </p>
    <div id="pcfFieldsTree">
      <?php echo $htmlTree; ?>
    </div>
    <input name="pcf[checkedPcfFields]" id="checkedPcfFieldsInput" type="hidden" value="" />
  </div>
</div>

<script type="text/javascript">
  var savedPcfFields = <?php echo json_encode($savedPcfFields); ?>;
</script>