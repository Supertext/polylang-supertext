<script type="text/html" id="tmpl-sttr-shortcode-setting">
  <div class="shortcode-setting-container">
    <div class="shortcode-setting-part">
      <div class="shortcode-setting-title">
        <?php _e('Shortcode', 'polylang-supertext'); ?>
      </div>
      <input class="shortcode-input-name" type="text" value="{{data.name}}" name="shortcodes[{{data.shortcodeIndex}}][name]" placeholder="<?php _e('Shortcode...', 'polylang-supertext'); ?>"></div>
    <div class="shortcode-setting-part">
      <div class="shortcode-setting-title">
        <?php _e('Encoding for enclosed content', 'polylang-supertext'); ?>
      </div>
      <div class="shortcode-content-encoding">
        <input class="shortcode-input-encoding" type="text" name="shortcodes[{{data.shortcodeIndex}}][content_encoding]" value="{{data.contentEncoding}}" placeholder="<?php _e('Functions...', 'polylang-supertext'); ?>"/>
      </div>
    </div>
    <div class="shortcode-setting-part shortcode-setting-part-attributes">
      <div class="shortcode-setting-title">
        <?php _e('Translatable attributes', 'polylang-supertext'); ?>
      </div>
      <div class="shortcode-setting-attributes">

      </div>
      <button type="button" class="button button-highlighted button-add shortcode-attribute-add-input"><?php _e('Add attribute', 'polylang-supertext'); ?></button>
    </div>
    <div class="clear"></div>
    <button type="button" class="button button-highlighted button-remove shortcode-remove-setting"><span class="dashicons dashicons-trash"></span> <?php _e('remove setting', 'polylang-supertext'); ?></button>
  </div>
</script>

<script type="text/html" id="tmpl-sttr-shortcode-attribute">
  <div class="shortcode-attribute-input">
    <input type="text" name="shortcodes[{{data.shortcodeIndex}}][attributes][{{data.attributeIndex}}][name]" value="{{data.name}}" placeholder="<?php _e('Attribute name...', 'polylang-supertext'); ?>"/>
    <input class="shortcode-input-encoding" type="text" name="shortcodes[{{data.shortcodeIndex}}][attributes][{{data.attributeIndex}}][encoding]" value="{{data.encoding}}" placeholder="<?php _e('Functions...', 'polylang-supertext'); ?>"/>
    <button type="button" class="button button-highlighted button-remove shortcode-attribute-remove-input"><span class="dashicons dashicons-trash"></span></button>
  </div>
</script>