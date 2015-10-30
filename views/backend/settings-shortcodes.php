<?php
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

global $shortcode_tags;

$library = $context->getCore()->getLibrary();
$options = $library->getSettingOption();
$savedShortcodes = isset($options[Constant::SETTING_SHORTCODES]) ? ArrayManipulation::forceArray($options[Constant::SETTING_SHORTCODES]) : array();

function getAttributeInput($key, $value){
  return '<div class="shortcode-attribute-input">
           <input type="text" name="shortcodes['.$key.'][attributes][]" value="'.$value.'" /><input type="button" value="-" class="button button-highlighted shortcode-attribute-remove-input" />
         </div>';
}

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Shortcodes', 'polylang-supertext'); ?></h3>
    <table id="tblShortcodes">
      <thead>
      <tr>
        <th><?php _e('Shortcode', 'polylang-supertext'); ?></th>
        <th><?php _e('Enclosed content encoding', 'polylang-supertext'); ?></th>
        <th><?php _e('Translatable attributes', 'polylang-supertext'); ?></th>
      </tr>
      </thead>
      <tbody>
      <?php
      foreach ($shortcode_tags as $key => $function) {
        $checkboxId = 'chkbx'.$key;
        $contentEncoding = '';
        $savedShortcodeAttributes = array();
        $checked = '';

        if(isset($savedShortcodes[$key])){
          $savedShortcodeAttributes = $savedShortcodes[$key]['attributes'];
          $contentEncoding = $savedShortcodes[$key]['content_encoding'];
          $checked = 'checked="checked"';
        }

        $attributeInputs = getAttributeInput($key, '');

        foreach ($savedShortcodeAttributes as $savedShortcodeAttribute) {
          $attributeInputs .= getAttributeInput($key, $savedShortcodeAttribute);
        }

        echo '
        <tr>
          <td><label for="'.$checkboxId.'">'.$key.'</label></td>
          <td><input type="text" name="shortcodes['.$key.'][content_encoding]" value="'.$contentEncoding.'" /></td>
          <td>'.$attributeInputs.'<input type="button" value="+" class="button button-highlighted shortcode-attribute-add-input" /></td>
        </tr>';
      }
      ?>
      </tbody>
    </table>
  </div>
</div>