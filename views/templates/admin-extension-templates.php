<script type="text/html" id="tmpl-sttr-offer-box">
  <div id="sttr-offer-box">
    <div id="div_tb_wrap_translation" class="div_tb_wrap_translation">
      <div id="div_translation_order_head">
        <div id="div_translation_title">
          <span><img src="<?php echo SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/logo_supertext.png'; ?>" width="48" height="48" alt="Supertext" title="Supertext" /></span>
          <h2><?php _e('Your Supertext translation order', 'polylang-supertext');?></h2>
          <div class="clear"></div>
        </div>
      </div>
      <div id="div_waiting_while_loading" style="display:none;">
        <p>
          <i>
            <?php _e('One moment please. The translation order is being sent to Supertext.', 'polylang-supertext'); ?><br>
            <?php _e('Please do not close this window.', 'polylang-supertext'); ?>
          </i>
          <img src="<?php echo SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/loader.gif'; ?>" title="<?php _e('Loading', 'polylang-supertext'); ?>">
        </p>
      </div>
      <div id="div_translation_order_content">
        <form
          name="frm_Translation_Options"
          id="frm_Translation_Options"
          method="post">
          <input type="hidden" name="post_id" value="{{data.postId}}">
          <input type="hidden" name="source_lang" value="{{data.sourceLanguageCode}}">
          <input type="hidden" name="target_lang" value="{{data.targetLanguageCode}}">
          <h3><?php _e('Translation of post', 'polylang-supertext'); ?>: {{data.postTitle}}</h3>
          <?php _e('The article will be translated from <b>{{data.sourceLanguage}}</b> into <b>{{data.targetLanguage}}</b>.', 'polylang-supertext'); ?>
          <h3><?php _e('Content to be translated', 'polylang-supertext'); ?></h3>
          <# _.each(data.translatableFields, function(translatableField, sourceId) { #>
            <# if(translatableField.fields.length){ #>
              <table class="translatableContentTable" border="0">
                <thead><tr><th colspan="8">{{translatableField.sourceName}}</th></tr></thead>
                <tbody>
                <tr>
                  <# _.each(translatableField.fields, function(field) { #>
                    <td>
                      <# if(field.default){ #>
                        <input type="checkbox" class="chkTranslationOptions" name="translatable_fields[{{sourceId}}][{{field.name}}]" id="sttr-{{sourceId}}-{{field.name}}"  checked="checked" >
                        <#} else {#>
                          <input type="checkbox" class="chkTranslationOptions" name="translatable_fields[{{sourceId}}][{{field.name}}]" id="sttr-{{sourceId}}-{{field.name}}" >
                          <# } #>
                    </td>
                    <td>
                      <label for="sttr-{{sourceId}}-{{field.name}}">{{field.title}}</label>
                    </td>
                    <# }); #>
                </tr>
                </tbody>
              </table>
              <# } #>
                <# }); #>
                  <p><?php printf(wp_kses(__('Translatable custom fields can be defined in the <a target="_parent" href="%s">settings</a>.', 'polylang-supertext'), array('a' => array('href' => array(), 'target' => array()))), esc_url(get_admin_url(null, 'options-general.php?page=supertext-polylang-settings&tab=translatablefields'))); ?></p>
                  <h3><?php _e('Service and deadline', 'polylang-supertext'); ?></h3>
                  <p><?php _e('Select the translation service and deadline:', 'polylang-supertext'); ?></p>
                  <div class="div_translation_price_loading" id="div_translation_price_loading">
                    <?php _e('The price is being calculated, one moment please.', 'polylang-supertext'); ?>
                    <img src="<?php echo SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/loader.gif'; ?>" title="<?php _e('Loading', 'polylang-supertext') ?>" width="16" height="16">
                  </div>
                  <div id="div_translation_price" style="display:none;"></div>
                  <h3><?php _e('Your comment to Supertext', 'polylang-supertext'); ?></h3>
                  <p><textarea name="txt_comment" id="txt_comment"></textarea></p>
                  <div class="div_translation_order_buttons">
                    <button type="button" class="button" onclick="Supertext.Polylang.sendOrderForm()"><?php _e('Order translation', 'polylang-supertext'); ?></button>
                  </div>
        </form>
      </div>
    </div>

    <div id="div_close_translation_order_window" class="div_translation_order_buttons" style="display:none">
      <input type="button" id="btn_close_translation_order_window" class="button" value="<?php _e('Close window', 'polylang-supertext'); ?>" />
    </div>

  </div>
</script>

<script type="text/html" id="tmpl-sttr-order-link-row">
  <tr class="sttr-order-link-row">
    <td>&nbsp;</td>
    <td><img src="<?php echo SUPERTEXT_POLYLANG_RESOURCE_URL .'/images/arrow-right.png'; ?>" width="16" height="16"/></td>
    <td colspan="2"><a href="#" onclick="Supertext.Polylang.openOrderForm('{{data.targetLanguageCode}}')">&nbsp;<?php _e('Order translation', 'polylang-supertext'); ?></a></td>
  </tr>
</script>

<script type="text/html" id="tmpl-sttr-error">
  <div id="error_missing_function" class="notice notice-error">
    <p>
      <h2 class="error-title">{{data.title}}</h2><p class="error-message">{{data.message}}<br/>({{data.details}})</p>
    </p>
  </div>
</script>