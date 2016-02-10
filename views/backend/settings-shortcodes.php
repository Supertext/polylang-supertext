<?php
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

global $shortcode_tags;

$library = $context->getCore()->getLibrary();
$options = $library->getSettingOption();
$savedShortcodes = isset($options[Constant::SETTING_SHORTCODES]) ? ArrayManipulation::forceArray($options[Constant::SETTING_SHORTCODES]) : array();

function getAttributeInput($index, $key, $value){
  return '<div class="shortcode-attribute-input" data-index="'.$index.'">
           <input type="text" name="shortcodes['.$key.'][attributes]['.$index.'][name]" value="'.$value['name'].'" placeholder="'. __('Attribute name...', 'polylang-supertext') .'"/>
           <input type="text" name="shortcodes['.$key.'][attributes]['.$index.'][encoding]" value="'.$value['encoding'].'" placeholder="'. __('Functions...', 'polylang-supertext') .'"/>
           <input type="button" value="-" class="button button-highlighted shortcode-attribute-remove-input" />
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
    foreach ($shortcode_tags as $key => $function) {
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
                  <input type="text" name="shortcodes['.$key.'][content_encoding]" value="'.$contentEncoding.'" placeholder="'. __('Functions...', 'polylang-supertext') .'"/>
                </div>
              </div>
              <div class="shortcode-setting-container shortcode-setting-attributes">
                <div class="shortcode-setting-title">
                  ' . __('Translatable attributes', 'polylang-supertext') . '
                </div>
                <div>'.$attributeInputs.'<input type="button" value="+" class="button button-highlighted shortcode-attribute-add-input" /></div>
              </div>
              <div class="clear"></div>
            </div>';
    } ?>
  </div>
</div>

