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
     * The error template
     * @type {null}|error template
     */
    errorTemplate = null,
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
    offerUrl = '/wp-content/plugins/polylang-supertext/views/backend/offer.php';

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

    if(posts.length > 0){
      checkPostSelection(posts);
    }else{
      //TODO show error
      alert('please select');
    }

    return false;
  }

  /**
   * Checks that all selected post are valid
   * @param posts
   */
  function checkPostSelection(posts)
  {
    $.get(
      context.ajaxUrl,
      {
        action: 'sttr_getPostTranslationData',
        posts: posts
      }
    ).done(
      function (data) {
        if (data.head.status != 'success') {
          //TODO show error
          return;
        }

        var languageCode = null;
        var isEachPostInSameLanguage = true;
        $.each(data.body, function(index, post) {
          if(index === 0){
            languageCode = post.languageCode;
          }else{
            isEachPostInSameLanguage = isEachPostInSameLanguage && post.languageCode == languageCode;
          }
        });

        if(isEachPostInSameLanguage){
          openTargetLanguageSelectionForm();
        }else{
          //TODO show error
          alert(supertextTranslationL10n.alertNotAllSelectedPostInSameLanguage);
        }
      }
    ).fail(
      function(jqXHR, textStatus, errorThrown){
        //TODO show error

      }
    );
  }

  /**
   *
   */
  function openTargetLanguageSelectionForm() {

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
   * Opens the order form in a thickbox
   * @param targetLanguageCode
   */
  function openOrderForm(targetLanguageCode) {
    if (hasUnsavedChanges() && !confirm(supertextTranslationL10n.confirmUnsavedArticle)) {
      return;
    }

    // Can't translate a post in translation
    if (inTranslation) {
      alert(supertextTranslationL10n.alertUntranslatable);
      return;
    }

    tb_show(supertextTranslationL10n.offerTranslation, '#?TB_inline&width=100%&height=100%&inlineId=sttr-offer-thickbox', false);


  }

  /**
   * Creates the translation link for the current post
   * @param languageRow the language row, contains vital information
   * @return string the link to translation (actually a JS call)
   */
  function getTranslationLink(languageCode) {
    var postId = $('#post_ID').val();
    // Create params, link and call with a check function
    var params = '?postId=' + postId + '&targetLang=' + languageCode + '&height=800&width=630'
    var tbLink = context.resourceUrl + offerUrl + params;
    return tbLink;
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
      context.ajaxUrl + '?action=getOffer',
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


      $('#frm_Translation_Options').hide();
      $('#warning_draft_state').hide();
      $('#warning_already_translated').hide();

      $('#div_waiting_while_loading').show();

      var offerForm = $('#frm_Translation_Options');
      var postData = offerForm.serialize();

      // Post to API Endpoint and create order
      $.post(
        context.ajaxUrl + 's?action=createOrder',
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
                getErrorHtml(
                  supertextTranslationL10n.generalError,
                  supertextTranslationL10n.translationOrderError,
                  data.body.reason)
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
            getErrorHtml(
              supertextTranslationL10n.generalError,
              supertextTranslationL10n.translationOrderError,
              errorThrown + ": " + jqXHR.responseText)
          );
        }
      );

    }

    // disable native form submit
    return false;
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
    var bHasUnsavedChanges = false;
    var mce = typeof(tinyMCE) != 'undefined' ? tinyMCE.activeEditor : false, title, content;

    if (mce && !mce.isHidden()) {
      if (mce.isDirty())
        bHasUnsavedChanges = true;
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
          bHasUnsavedChanges = true;
        }
      }
    }
    return bHasUnsavedChanges;
  }

  function getErrorHtml(title, message, details){
    if(errorTemplate == null){
      errorTemplate = wp.template('sttr-error');
    }

    return errorTemplate({
      title: title,
      message: message,
      details: details
    });
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
    },
    openOrderForm: openOrderForm
  }

})(window, jQuery);

// Load on load. yass.
jQuery(document).ready(function () {
  Supertext.Polylang.initialize();
});
