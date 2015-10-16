<?php
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

global $shortcode_tags;

$library = $context->getCore()->getLibrary();
$options = $library->getSettingOption();
$savedShortcodes = isset($options[Constant::SETTING_SHORTCODES]) ? ArrayManipulation::forceArray($options[Constant::SETTING_SHORTCODES]) : array();

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Translatable Shortcodes', 'polylang-supertext'); ?></h3>
    <table id="tblShortcodes">
      <thead>
      <tr>
        <th colspan="2"><?php _e('Shortcode', 'polylang-supertext'); ?></th>
        <th><?php _e('Attributes', 'polylang-supertext'); ?></th>
      </tr>
      </thead>
      <tbody>
      <?php
      foreach ($shortcode_tags as $key => $function) {
        $checkboxId = 'chkbx'.$key;
        $savedShortcodeAttributes = array();
        $checked = '';

        if(isset($savedShortcodes[$key])){
          $savedShortcodeAttributes = $savedShortcodes[$key];
          $checked = 'checked="checked"';
        }

        $inputs = count($savedShortcodeAttributes) > 0 ? '' : '<input type="text" class="shortcode-attribute-input" name="shortcodes['.$key.'][attributes][]" value="" />';

        foreach ($savedShortcodeAttributes as $savedShortcodeAttribute) {
          $inputs .= '<input type="text" class="shortcode-attribute-input" name="shortcodes['.$key.'][attributes][]" value="'.$savedShortcodeAttribute.'" />';
        }

        echo '
        <tr>
          <td><input type="checkbox" id="'.$checkboxId.'" name="shortcodes['.$key.'][selected]" value="1" '.$checked.' /></td>
          <td><label for="'.$checkboxId.'">'.$key.'</label></td>
          <td>'.$inputs.'</td>
        </tr>';
      }
      ?>
      </tbody>
    </table>
  </div>
</div>