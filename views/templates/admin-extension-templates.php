<script type="text/html" id="tmpl-sttr-order-link-row">
  <tr class="sttr-order-link-row">
    <td>&nbsp;</td>
    <td><img src="<?php echo SUPERTEXT_POLYLANG_RESOURCE_URL .'/images/arrow-right.png'; ?>" width="16" height="16"/></td>
    <td colspan="2"><a href="#" onclick="Supertext.Polylang.openOrderForm('{{data.targetLanguageCode}}')">&nbsp;<?php _e('Order translation', 'polylang-supertext'); ?></a></td>
  </tr>
</script>

<script type="text/html" id="tmpl-sttr-modal">
  <div id="sttr-modal" class="sttr-modal">
    <div id="sttr-modal-notice" class="sttr-modal-notice"></div>
    <div class="sttr-modal-content wp-core-ui">
      <button class="sttr-modal-icon-close" type="button"><span class="dashicons dashicons-no"></span></button>
      <div id="sttr-modal-header" class="sttr-modal-header">
        <div class="logo"><img src="<?php echo SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/logo_supertext.png'; ?>" width="32" height="32" alt="Supertext" title="Supertext" /></div>
        <h1>{{data.title}}</h1>
        <div class="clearfix"></div>
      </div>
      <div class="sttr-modal-body">
        <div id="sttr-modal-body-content" class="sttr-modal-body-content">
          <div class="loader">
            <img src="<?php echo SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/loader.gif'; ?>" title="<?php _e('Loading', 'polylang-supertext'); ?>">
            <?php _e('Loading', 'polylang-supertext'); ?>
          </div>
        </div>
      </div>
      <div id="sttr-modal-footer" class="sttr-modal-footer">
        <div class="clearfix"></div>
      </div>
    </div>
    <div class="sttr-modal-background"> </div>
  </div>
</script>

<script type="text/html" id="tmpl-sttr-modal-error">
  <div id="sttr-modal-error-{{data.token}}" class="notice notice-error">
    <button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>
    <h2>{{data.error.title}}</h2>
    <p class="error-message">
      <# if(_.isArray(data.error.message)) { #>
        {{{data.error.message.join('<br>')}}}
        <# }else{ #>
          {{data.error.message}}
          <# } if(data.details){ #><br/>({{data.error.details}})<# } #>
    </p>
  </div>
</script>

<script type="text/html" id="tmpl-sttr-modal-button">
  <button type="button" id="sttr-modal-button-{{data.token}}" class="button button-{{data.type}}">{{{data.innerHtml}}}</button>
</script>

<script type="text/html" id="tmpl-sttr-order-progress-bar">
  <div class="sttr-order-progress-bar">
    <ul>
      <li class="active"><?php _e('Select content', 'polylang-supertext'); ?></li>
      <li><?php _e('Choose target language', 'polylang-supertext'); ?></li>
      <li><?php _e('Get offer and order', 'polylang-supertext'); ?></li>
      <li><?php _e('Confirmation', 'polylang-supertext'); ?></li>
    </ul>
  </div>
</script>

<script type="text/html" id="tmpl-sttr-order-step-1">
  <form id="sttr-order-step-1-form">
  <h2><?php _e('Content to be translated', 'polylang-supertext');?></h2>
  <p>
    <?php
    $adminUrl = get_admin_url(null, 'options-general.php?page=supertext-polylang-settings&tab=translatablefields');
    printf(wp_kses(
      __('Translatable custom fields can be defined in the <a target="_parent" href="%s">settings</a>.',
        'polylang-supertext'),
      array('a' => array('href' => array(), 'target' => array()))),
      esc_url($adminUrl));
    ?>
  </p>
  <div class="sttr-order-list">
    <div class="sttr-order-items">
      <ul>
        <# _.each(data.posts, function(post) { #>
          <li>
            <a href="#sttr-order-translatable-content-{{post.id}}" data-id="{{post.id}}">{{post.title}} ({{post.languageCode}})<span class="dashicons dashicons-no-alt"></span></a>
          </li>
        <# }); #>
      </ul>
    </div>
    <div class="sttr-order-item-details">
      <# _.each(data.posts, function(post) { #>
        <div id="sttr-order-translatable-content-{{post.id}}" style="display: none;">
           <span>
              <?php _e('Please select the content to be translated of <b>{{post.title}}</b>.', 'polylang-supertext');?>
           </span>
          <p>
          <# _.each(post.translatableFields, function(translatableField, sourceId) { #>
            <table class="translatable-content-table">
              <thead><tr><th colspan="8">{{translatableField.sourceName}}</th></tr></thead>
              <tbody>
              <# if(translatableField.fields.length){ #>
              <tr>
                <# _.each(translatableField.fields, function(field) { #>
                  <td>
                    <# if(field.default){ #>
                      <input type="checkbox" id="sttr-{{post.id}}-{{sourceId}}-{{field.name}}" name="translatableContents[{{post.id}}][{{sourceId}}][{{field.name}}]" checked="checked">
                    <#} else {#>
                      <input type="checkbox" id="sttr-{{post.id}}-{{sourceId}}-{{field.name}}" name="translatableContents[{{post.id}}][{{sourceId}}][{{field.name}}]">
                    <# } #>
                  </td>
                  <td>
                    <label for="sttr-{{post.id}}-{{sourceId}}-{{field.name}}">{{field.title}}</label>
                  </td>
                  <# }); #>
              </tr>
              <# } else { #>
              <tr>
                <td>- <?php _e('Not present in this post/page', 'polylang-supertext');?></td>
              </tr>
              <# } #>
              </tbody>
            </table>
          <# }); #>
          </p>
        </div>
      <# }); #>
    </div>
    <div class="clearfix"></div>
    <button id="sttr-order-remove-item" class="button button-secondary button-remove remove-item"><span class="dashicons dashicons-no-alt"></span> <?php _e('Remove this post/page', 'polylang-supertext');?></button>
    <div class="clearfix"></div>
  </div>
  <h2><?php _e('Language', 'polylang-supertext');?></h2>
  <p>
    <?php _e('Translate from <b id="sttr-order-source-language-label">{{data.sourceLanguage}}</b> into ', 'polylang-supertext');?>
    <input type="hidden" name="orderSourceLanguage" id="sttr-order-source-language" />
    <select name="orderTargetLanguage" id="sttr-order-target-language">
      <option value=""><?php _e('Please select', 'polylang-supertext');?>...</option>
      <# _.each(data.languages, function(language, code) { #>
        <option value="{{code}}">{{language}}</option>
      <# }); #>
    </select>
  </p>
  </form>
</script>

<script type="text/html" id="tmpl-sttr-order-step-2">
  <form id="sttr-order-step-2-form">
    <h2><?php _e('Service and deadline', 'polylang-supertext'); ?></h2>
    <p><?php _e('Select the translation service and deadline:', 'polylang-supertext'); ?></p>
    <div class="sttr-order-item-quote">
      <table cellspacing="0" cellpadding="2" border="0">
        <tbody>
        <# _.each(data.options, function(option) { #>
          <tr class="first-group-row">
            <td class="quality-group-cell" rowspan="6">
              <b>{{option.name}}</b>
            </td>
            <td class="selection-cell">&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
          <# _.each(option.items, function(item) { #>
            <tr>
              <td class="selection-cell">
                <input type="radio" value="{{option.id}}:{{item.id}}" id="sttr-rad-translation-type-{{option.id}}-{{item.id}}" name="radTranslationType">
              </td>
              <td>
                <label for="sttr-rad-translation-type-{{option.id}}-{{item.id}}">{{item.name}}</label>
              </td>
              <td align="right" class="ti-deadline">
                <label for="sttr-rad-translation-type-{{option.id}}-{{item.id}}">{{item.date}}</label>
              </td>
              <td align="right" class="ti-deadline">
                <label for="sttr-rad-translation-type-{{option.id}}-{{item.id}}">{{item.price}}</label>
              </td>
            </tr>
            <# }); #>
              <tr class="last-group-row"></tr>
              <# }); #>
        </tbody>
      </table>
    </div>
    <h2><?php _e('Your comment to Supertext', 'polylang-supertext'); ?></h2>
    <p><textarea name="orderComment" id="sttr-order-comment"></textarea></p>
  </form>
</script>