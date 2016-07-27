<?php
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

$shortcodeTags = $context->getShortcodeTags();
$savedShortcodes = $context->getSettingOption(Constant::SETTING_SHORTCODES);

function getAttributeInput($index, $key, $value){
  return '<div class="shortcode-attribute-input" data-index="'.$index.'">
           <input type="text" name="shortcodes['.$key.'][attributes]['.$index.'][name]" value="'.$value['name'].'" placeholder="'. __('Attribute name...', 'polylang-supertext') .'"/>
           <input class="shortcode-input-encoding" type="text" name="shortcodes['.$key.'][attributes]['.$index.'][encoding]" value="'.$value['encoding'].'" placeholder="'. __('Functions...', 'polylang-supertext') .'"/>
           <button type="button" class="button button-highlighted button-remove shortcode-attribute-remove-input"><span class="dashicons dashicons-trash"></span></button>
         </div>';
}

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Shortcodes', 'polylang-supertext'); ?></h3>
    <p>
      <?php _e('Please define any shortcode attributes you want to have translated.', 'polylang-supertext'); ?>
    </p>
    <p>
      <?php _e('Please define the encoding process for all encoded and enclosed content. Available encoding functions are: rawurl, url and base64', 'polylang-supertext'); ?>
    </p>
    <?php
    foreach ($shortcodeTags as $key => $function) {
      $contentEncoding = '';
      $savedShortcodeAttributes = array();

      if(isset($savedShortcodes[$key])){
        $savedShortcodeAttributes = $savedShortcodes[$key]['attributes'];
        $contentEncoding = $savedShortcodes[$key]['content_encoding'];
      }

      $attributeIndex = 0;
      $attributeInputs = getAttributeInput($attributeIndex, $key, array('name'=>'', 'encoding'=>''));
      foreach ($savedShortcodeAttributes as $savedShortcodeAttribute) {
        $attributeInputs .= getAttributeInput(++$attributeIndex, $key, $savedShortcodeAttribute);
      }

      echo '<div>
              <h4>' . $key . '</h4>
              <div class="shortcode-setting-container">
                <div class="shortcode-setting-title">
                  ' . __('Encoding for enclosed content', 'polylang-supertext') . '
                </div>
                <div class="shortcode-content-encoding">
                  <input class="shortcode-input-encoding" type="text" name="shortcodes['.$key.'][content_encoding]" value="'.$contentEncoding.'" placeholder="'. __('Functions...', 'polylang-supertext') .'"/>
                </div>
              </div>
              <div class="shortcode-setting-container shortcode-setting-attributes">
                <div class="shortcode-setting-title">
                  ' . __('Translatable attributes', 'polylang-supertext') . '
                </div>
                <div>'.$attributeInputs.'<button type="button" class="button button-highlighted button-add shortcode-attribute-add-input">'.__('Add attribute', 'polylang-supertext').'</button></div>
              </div>
              <div class="clear"></div>
            </div>';
    } ?>
  </div>
</div>

