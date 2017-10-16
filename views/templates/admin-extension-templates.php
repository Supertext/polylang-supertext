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
      <div class="sttr-modal-header">
        <div class="logo"><img src="<?php echo SUPERTEXT_POLYLANG_RESOURCE_URL . '/images/logo_supertext.png'; ?>" width="32" height="32" alt="Supertext" title="Supertext" /></div>
        <h1>{{data.title}}</h1>
        <div class="clearfix"></div>
      </div>
      <div class="sttr-modal-body">
        <div id="sttr-modal-body-content" class="sttr-modal-body-content">
        </div>
      </div>
      <div id="sttr-modal-footer" class="sttr-modal-footer">
        <div class="clearfix"></div>
      </div>
    </div>
    <div class="sttr-modal-background"> </div>
    <div id="sttr-modal-full-content" class="sttr-modal-full-content" style="display: none">
      <button class="sttr-modal-icon-close" type="button"><span class="dashicons dashicons-no"></span></button>
      <div class="sttr-modal-full-body">
        <div id="sttr-modal-full-body-content" class="sttr-modal-full-body-content">
        </div>
      </div>
    </div>
  </div>
</script>

<script type="text/html" id="tmpl-sttr-modal-error">
  <div id="sttr-modal-error-{{data.token}}" class="notice notice-error">
    <button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>
    <h2>{{data.error.title}}</h2>
    <p class="error-message">
      <# if(_.isArray(data.error.message)) { #>
        {{{data.error.message.join('<br><br>')}}}
      <# }else{ #>
        {{data.error.message}}
      <# } #>
      <# if(data.error.details){ #>
        <br/>({{data.error.details}})
      <# } #>
    </p>
  </div>
</script>

<script type="text/html" id="tmpl-sttr-modal-button">
  <button type="button" id="sttr-modal-button-{{data.token}}" class="button button-{{data.type}}">{{{data.innerHtml}}}</button>
</script>

<script type="text/html" id="tmpl-sttr-step-loader">
  <div class="loader">
    <div class="spin"></div>
    <?php _e('Loading', 'polylang-supertext'); ?>...
  </div>
</script>

<script type="text/html" id="tmpl-sttr-order-progress-bar">
  <div id="sttr-order-progress-bar" class="sttr-order-progress-bar">
    <ul>
      <li><?php _e('Select content and language', 'polylang-supertext'); ?></li>
      <li><?php _e('Select service and deadline', 'polylang-supertext'); ?></li>
      <li><?php _e('Confirmation', 'polylang-supertext'); ?></li>
    </ul>
    <div class="clearfix"></div>
    <hr />
  </div>
  <div id="sttr-order-step" class="sttr-order-step"></div>
</script>

<script type="text/html" id="tmpl-sttr-content-step">
  <form id="sttr-content-step-form">
    <h2><?php _e('Content to be translated', 'polylang-supertext'); ?></h2>
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
            <li class="{{post.meta.inTranslation ? 'hasError' : ''}}">
              <a href="#sttr-order-content-selection-{{post.id}}" data-post-id="{{post.id}}">
                <span class="dashicons dashicons-no-alt"></span>
                {{post.title}} ({{post.languageCode}}{{post.meta.inTranslation ? ', <?php _e('blocked', 'polylang-supertext'); ?>' : ''}}{{post.isDraft ? ', <?php _e('draft', 'polylang-supertext'); ?>' : ''}})
              </a>
            </li>
          <# }); #>
        </ul>
      </div>
      <div class="sttr-order-item-details">
        <# _.each(data.posts, function(post) { #>
          <div id="sttr-order-content-selection-{{post.id}}" style="display: none;">
            <h3>{{post.title}}</h3>
            <# if(post.meta.inTranslation){ #>
            <p class="notice notice-error">
              <span class="error-message"><?php _e('The post cannot be translated. It is blocked by a translation order in progress.', 'polylang-supertext');?></span>
            </p>
            <# }else if(post.isDraft){ #>
            <p class="notice notice-warning">
              <span><?php _e('The post status is <b>draft</b>. Are you sure you want to order a translation for this article?', 'polylang-supertext');?></span>
            </p>
            <# } #>
            <p>
              <?php _e('Please select the content to be translated.', 'polylang-supertext');?>
            </p>
            <# _.each(post.translatableFieldGroups, function(translatableFieldGroup, groupId) { #>
              <h2>{{translatableFieldGroup.name}}</h2>

                <# if(translatableFieldGroup.fields.length){ #>

                  <# _.each(translatableFieldGroup.fields, function(field, index) { #>
                  <div class="column">
                    <label>
                      <# if(field.checkedPerDefault){ #>
                        <input type="checkbox" id="sttr-{{post.id}}-{{groupId}}-{{field.name}}" name="translatableContents[{{post.id}}][{{groupId}}][fields][{{field.name}}]" checked="checked">
                      <# } else { #>
                        <input type="checkbox" id="sttr-{{post.id}}-{{groupId}}-{{field.name}}" name="translatableContents[{{post.id}}][{{groupId}}][fields][{{field.name}}]">
                      <# } #>
                      {{field.title}}
                    </label>
                  </div>
                  <# }); #>

                <# } else { #>
                  - <?php _e('Not present in this post', 'polylang-supertext');?>
                <# } #>
                <div class="clearfix"></div>
            <# }); #>
          </div>
        <# }); #>
      </div>
      <div class="clearfix"></div>
      <button id="sttr-order-remove-item" class="button button-secondary button-remove remove-item"><span class="dashicons dashicons-no-alt"></span> <?php _e('Remove this post', 'polylang-supertext');?></button>
      <button id="sttr-order-show-item-content" class="button button-secondary"><?php _e('Show content', 'polylang-supertext');?></button>
      <div class="clearfix"></div>
    </div>
    <h2><?php _e('Language', 'polylang-supertext');?></h2>
    <p>
      <?php _e('Translate from <b id="sttr-order-source-language-label">-</b> into ', 'polylang-supertext');?>
      <input type="hidden" name="orderSourceLanguage" id="sttr-order-source-language" />
      <select name="orderTargetLanguage" id="sttr-order-target-language">
        <option value=""><?php _e('Please select', 'polylang-supertext');?>...</option>
        <# _.each(data.languages, function(language, code) { #>
          <# if(code == data.targetLanguageCode){ #>
            <option value="{{code}}" style="display: none" selected="selected">{{language}}</option>
          <#} else {#>
            <option value="{{code}}" style="display: none">{{language}}</option>
          <# } #>
        <# }); #>
      </select>
    </p>
  </form>
</script>

<script type="text/html" id="tmpl-sttr-item-content">
  <div class="sttr-item-content">
    <# _.each(data.contentData, function(group) { #>
      <b>{{group.name}}</b><br/>
      <# _.each(group.elements, function(element) { #>
        <i>{{element.path}}</i><br/>
        {{{element.value}}}<br/><br/>
      <# }); #>
      <br/>
    <# }); #>

    <pre>{{data.rawData}}</pre>
  </div>

</script>

<script type="text/html" id="tmpl-sttr-quote-step">
  <form id="sttr-quote-step-form">
    <h2><?php _e('Service and deadline', 'polylang-supertext'); ?></h2>
    <p><?php _e('Select the translation service and deadline for translating <b>{{data.wordCount}} words</b> into <b>{{data.language}}</b>:', 'polylang-supertext'); ?></p>
    <div class="sttr-order-item-quote">
      <table cellspacing="0" cellpadding="2" border="0">
        <tbody>
        <# _.each(data.options, function(option) { #>
          <tr class="first-group-row">
            <td class="quality-group-cell" rowspan="{{option.items.length+1}}">
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
                <input type="radio" value="{{option.id}}:{{item.id}}" id="sttr-rad-translation-type-{{option.id}}-{{item.id}}" name="translationType">
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

<script type="text/html" id="tmpl-sttr-confirmation-step">
  <div id="sttr-confirmation-step">
    <h2><?php _e('Confirmation', 'polylang-supertext'); ?></h2>
    {{{data.message}}}
  </div>
</script>