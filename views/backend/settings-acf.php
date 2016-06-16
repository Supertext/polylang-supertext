<?php

function getAcfTree($nodes){
  $nodeHtml = '<ul>';

  foreach($nodes as $id => $node){
    $icon = $node['type'] === 'field' ? 'jstree-file' : 'jstree-folder';

    $nodeHtml .= '<li id="'.$id.'" data-jstree=\'{"icon":"'.$icon.'"}\'>';
    $nodeHtml .= $node['label'];

    if(!empty($node['sub_field_definitions']) && count($node['sub_field_definitions']) > 0){
      $nodeHtml .= getAcfTree($node['sub_field_definitions']);
    }

    $nodeHtml .= '</li>';
  }

  $nodeHtml .= '</ul>';

  return $nodeHtml;
}

$htmlTree = getAcfTree($context['acfFieldDefinitions']);

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Advanced Custom Fields (Plugin)', 'polylang-supertext'); ?></h3>
    <p>
      <?php _e('Please select the custom fields that can be used for translations.', 'polylang-supertext'); ?>
    </p>
    <div id="acfFieldsTree">
      <?php echo $htmlTree; ?>
    </div>
    <input name="acf[checkedAcfFields]" id="checkedAcfFieldsInput" type="hidden" value="" />
  </div>
</div>

<script type="text/javascript">
  var savedAcfFields = <?php echo json_encode($context['savedAcfFields']); ?>;
</script>