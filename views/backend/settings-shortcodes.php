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
        $savedShortcodeAttributes = '';
        $checked = '';

        if(isset($savedShortcodes[$key])){
          $savedShortcodeAttributes = $savedShortcodes[$key];
          $checked = 'checked="checked"';
        }

        ?>
        <tr>
          <td><input type="checkbox" id="<?php echo $checkboxId; ?>" name="shortcodes[<?php echo $key; ?>][selected]" value="1" <?php echo $checked; ?> /></td>
          <td><label for="<?php echo $checkboxId; ?>"><?php echo $key; ?></label></td>
          <td><input type="text" class="shortcode-attribute-input" name="shortcodes[<?php echo $key; ?>][attributes]" value="<?php echo $savedShortcodeAttributes; ?>" /></td>
        </tr>
      <?php
      }
      ?>
      </tbody>
    </table>
  </div>
</div>
