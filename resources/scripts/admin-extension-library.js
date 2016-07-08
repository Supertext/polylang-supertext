var Supertext = Supertext || {};

/**
 * Polylang translation plugin to inject translation options
 * @author Michael Sebel <michael@comotive.ch>
 */
Supertext.Polylang = (function (win, $) {

  /**
   * Context data containing information about plugin and environment
   */
  var context,

    /**
     * Order translation bulk action option value
     * @type {string}
     */
    orderTranslationBulkActionValue = 'orderTranslation',
    /**
     * Tells if there is an ajax order being placed
     */
    createOrderRunning = false,
    /**
     * Ajax request counter
     */
    requestCounter = 0,
    /**
     * May be overridden to true on load
     */
    inTranslation = false,
    /**
     * The offer url to be loaded in thickbox
     */
    offerUrl = '/wp-content/plugins/polylang-supertext/views/backend/offer.php',
    /**
     * The very own ajax url (well, yes. legacy.)
     */
    ajaxUrl = '/wp-content/plugins/polylang-supertext/resources/scripts/api/ajax.php',

    /**
     * This can be set to true in order to see a confirmation before purchasing
     */
    NEED_CONFIRMATION = false,

    /**
     * Template for rows between translations
     */
    rowTemplate = '\
		<tr>\
		  <td>&nbsp;</td>\
		  <td><img src="{resourceUrl}/wp-content/plugins/polylang-supertext/resources/images/arrow-right.png" style="width:16px; padding-left:5px;"/></td>\
		  <td colspan="2"><a href="{translationLink}">&nbsp;' + supertextTranslationL10n.offerTranslation + '</a></td>\
		</tr>\
  ',
    errorTemplate = '<h2 class="error-title">{title}</h2><p class="error-message">{message}<br/>({details})</p>';

  /**
   * Initialize on post screen
   */
  function initializePostScreen() {
    if ($('#post-translations').length == 1 && context.isPluginWorking) {
      injectOrderLinks();
    }

    if ($('#title').length == 1 && $('#title').val().indexOf(supertextTranslationL10n.inTranslationText) > -1) {
      inTranslation = true;
      disableTranslatingPost();
    }
  }

  /**
   * Initialize on edit screen
   */
  function initializeEditScreen() {
    $('<option>').val(orderTranslationBulkActionValue).text(supertextTranslationL10n.offerTranslation).appendTo("select[name='action']");
    $('<option>').val(orderTranslationBulkActionValue).text(supertextTranslationL10n.offerTranslation).appendTo("select[name='action2']");

    $('#doaction, #doaction2').click(onBulkActionApply);
  }

  /**
   * Handles click on apply button of bulk actions
   * @param e
   * @returns {boolean}
   */
  function onBulkActionApply(e) {
    var bulkButtonId = $(this).attr('id');
    var selectName = bulkButtonId.substr(2);

    if ($('select[name="' + selectName + '"]').val() !== orderTranslationBulkActionValue) {
      return true;
    }

    e.preventDefault();

    var posts = [];

    $('input[name="post[]"]:checked').each(function () {
      posts.push($(this).val());
    });

    checkPostSelection(posts);

    return false;
  }

  /**
   *
   * @param posts
   */
  function checkPostSelection(posts) {

  }

  /**
   * Register events for the offer box. Also it fires the inital offer
   */
  function addOfferEvents() {
    // On load directly get an offer for default config
    getOffer();

    // Register to reload offer on checkbox click
    $('.chkTranslationOptions').change(function () {
      getOffer();
    });

    // Create order on form submit
    $('#frm_Translation_Options').submit(function () {
      return createOrder();
    });
  }

  /**
   * Injects an order link for every not yet made translation
   */
  function injectOrderLinks() {
    $('.pll-translation-column').each(function () {
      var languageRow = $(this).parent();
      var languageCode = languageRow
        .find('.pll-translation-column input')
        .first()
        .attr('id')
        .replace('htr_lang_', '');

      var rowTemplate = wp.template('sttr-order-link-row');
      languageRow.after(rowTemplate({targetLanguageCode: languageCode}));
    });
  }

  /**
   * Creates the translation link for the current post
   * @param languageRow the language row, contains vital information
   * @return string the link to translation (actually a JS call)
   */
  function getTranslationLink(languageCode) {
    var postId = $('#post_ID').val();
    // Create params, link and call with a check function
    var params = '?postId=' + postId + '&targetLang=' + languageCode + '&height=800&width=630&TB_iframe=true'
    var tbLink = context.resourceUrl + offerUrl + params;
    return tbLink;
  }

  /**
   * Disable a post that is in translation
   */
  function disableTranslatingPost() {
    // Set all default fields to readonly
    $('#post input, #post select, #post textarea').each(function () {
      // If the value contains the in translation text, lock fields
      $(this).attr('readonly', 'readonly');
      $(this).addClass('input-disabled');
      post_is_in_translation = true;
    });

    $('#wp-content-wrap').hide();
  }

  /**
   * Checks translatability before calling the offerbox
   * @param tbLink the offer box link that will be fired if everything is ok
   */
  function checkBeforeTranslating(languageCode) {
    var tbLink = getTranslationLink(languageCode);
    var canTranslate = false;
    // Are there changes in fields or editor?
    if (!hasUnsavedChanges()) {
      canTranslate = true;
    } else {
      canTranslate = confirm(supertextTranslationL10n.confirmUnsavedArticle);
    }
    if (canTranslate) {
      // Can't translate a post in translation
      if (inTranslation) {
        alert(supertextTranslationL10n.alertUntranslatable)
      } else {
        // Open the offer box
        tb_show(supertextTranslationL10n.offerTranslation, tbLink, false);
      }
    }
  }

  /**
   * Get an offer for a certian post
   */
  function getOffer() {
    handleElementVisibility(true, true);
    requestCounter++;
    var postData = $('#frm_Translation_Options').serialize()
      + '&requestCounter=' + requestCounter;

    $.post(
      context.resourceUrl + ajaxUrl + '?action=getOffer',
      postData
    ).done(
      function (data) {
        // handle only newest request
        if (data.body.optional.requestCounter == requestCounter) {
          switch (data.head.status) {
            case 'success':
              $('#div_translation_price').html(data.body.html);
              handleElementVisibility(false, true);
              break;
            case 'no_data':
              $('#div_translation_price').html(data.body.html);
              handleElementVisibility(false, false);
              break;
            default: // error
              $('#div_translation_price').html(supertextTranslationL10n.generalError + '<br/>' + data.body.reason).addClass("error-message");
              handleElementVisibility(false, false);
              break;
          }
        }
      }
    ).fail(
      function () {
        $('#div_translation_price').html(supertextTranslationL10n.generalError).addClass("error-message");
        handleElementVisibility(false, false);
      }
    );
  }

  /**
   * Create an actual translation order for supertext
   * @returns bool false (always, to prevent native submit)
   */
  function createOrder() {
    // wird nur einmal ausgelöst
    if (!createOrderRunning) {
      createOrderRunning = true;

      // Oppan hadorn style
      var radio = $('input:radio:checked[name=rad_translation_type]');
      var deadline = radio.parent().next().next().html().trim();
      var price = radio.parent().next().next().next().html().trim();

      // is there a need to confirm?
      var hasConfirmed = true;
      if (NEED_CONFIRMATION) {
        hasConfirmed = confirm(getOfferConfirmMessage(deadline, price))
      }

      // If the user confirmed, actually create the order
      if (hasConfirmed) {
        $('#frm_Translation_Options').hide();
        $('#warning_draft_state').hide();
        $('#warning_already_translated').hide();

        $('#div_waiting_while_loading').show();

        var offerForm = $('#frm_Translation_Options');
        var postData = offerForm.serialize();

        // Post to API Endpoint and create order
        $.post(
          context.resourceUrl + ajaxUrl + '?action=createOrder',
          postData
        ).done(
          function (data) {
            $('#div_waiting_while_loading').hide();
            switch (data.head.status) {
              case 'success':
                $('#div_translation_order_content').html(data.body.html);
                break;
              default: // error
                $('#div_translation_order_head').hide();
                $('#div_translation_order_content').html(
                  errorTemplate
                    .replace('{title}', supertextTranslationL10n.generalError)
                    .replace('{message}', supertextTranslationL10n.translationOrderError)
                    .replace('{details}', data.body.reason)
                );
                break;
            }

            //set window close button, top right 'x'
            $(self.parent.document).find('#TB_closeWindowButton').click(function () {
              self.parent.location.reload();
            });

            $('#btn_close_translation_order_window').click(function () {
              self.parent.location.reload();
            });
            $('#div_close_translation_order_window').show();

            createOrderRunning = false;
          }
        ).fail(
          function (jqXHR, textStatus, errorThrown) {
            $('#div_waiting_while_loading').hide();
            $('#div_translation_order_head').hide();
            $('#div_translation_order_content').html(
              errorTemplate
                .replace('{title}', supertextTranslationL10n.generalError)
                .replace('{message}', supertextTranslationL10n.translationOrderError)
                .replace('{details}', errorThrown + ": " + jqXHR.responseText)
            );
          }
        );

      } else {
        createOrderRunning = false;
      }
    }

    // disable native form submit
    return false;
  }

  /**
   * Create the internationalized and parametrized confirm message for ordering
   * @param deadline deadline date / time
   * @param price the price
   * @param currency the currency
   */
  function getOfferConfirmMessage(deadline, price) {
    // First, create the templated message
    var message = '' +
      supertextTranslationL10n.offerConfirm_Price + '\n' +
      supertextTranslationL10n.offerConfirm_Binding + '\n\n' +
      supertextTranslationL10n.offerConfirm_EmailInfo + '\n\n' +
      supertextTranslationL10n.offerConfirm_Confirm;

    // Replace all vars
    message = message.replace('{deadline}', deadline);
    message = message.replace('{price}', price);

    return message;
  }

  /**
   * Handles visibility of elements depending on state
   * @param loading if somethings loading. oh crap i hate refactoring.
   * @param orderAllowed if ordering is allowed (everything is filled out)
   */
  function handleElementVisibility(loading, orderAllowed) {
    if (loading) {
      $('#div_translation_price_loading').show();
      $('#div_translation_price').hide();
      $('#btn_order').hide();
    } else {
      $('#div_translation_price_loading').hide();
      $('#div_translation_price').show();
      if (orderAllowed) {
        $('#btn_order').show();
      }
    }
  }

  /**
   * Checks if tinymce or title have unsaved changes
   * @returns {boolean} true, if there are changes
   */
  function hasUnsavedChanges() {
    var bUnsaved = false;
    var mce = typeof(tinyMCE) != 'undefined' ? tinyMCE.activeEditor : false, title, content;

    if (mce && !mce.isHidden()) {
      if (mce.isDirty())
        bUnsaved = true;
    } else {
      if (typeof(fullscreen) !== 'undefined' && fullscreen.settings.visible) {
        title = $('#wp-fullscreen-title').val();
        content = $("#wp_mce_fullscreen").val();
      } else {
        title = $('#post #title').val();
        content = $('#post #content').val();
      }

      if (typeof(autosaveLast) !== 'undefined') {
        if ((title || content) && title + content != autosaveLast) {
          bUnsaved = true;
        }
      }
    }
    return bUnsaved;
  }

  return {
    /**
     * Loading the module
     */
    initialize: function () {
      context = Supertext.Context || {
          isPluginWorking: false,
          screen: ''
        };

      if (context.screen == 'post') {
        initializePostScreen();
      } else if (context.screen == 'edit') {
        initializeEditScreen();
      }

      addOfferEvents();
    },
    openOrderForm: checkBeforeTranslating
  }

})(window, jQuery);

// Load on load. yass.
jQuery(document).ready(function () {
  Supertext.Polylang.initialize();
});
