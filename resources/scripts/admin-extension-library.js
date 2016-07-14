var Supertext = Supertext || {};

Supertext.Modal = (function (win, doc, $, wp) {
  'use strict';

  var
    /**
     * The modal html element id
     * @type {string}
     */
    modalTemplateId = 'sttr-modal',
    /**
     * The error id
     * @type {string}
     */
    errorTemplateId = 'sttr-modal-error',
    /**
     * The selectors of different html elements
     */
    selectors = {
      modal: '#' + modalTemplateId,
      modalBodyContent: '#sttr-modal-body-content',
      modalNotice: '#sttr-modal-notice',
      modalCloseIcon: '.sttr-modal-icon-close',
      modalBackground: '.sttr-modal-background',
      modalNextStepButton: '#sttr-modal-next-step',
      modalPreviousStepButton: '#sttr-modal-previous-step',
      modalCloseButton: '#sttr-modal-close',
      modalNoticeDismissIcon: '.notice-dismiss',
      modalErrorNotice: function (token) {
        return '#' + errorTemplateId + '-' + token
      }
    },
    /**
     * The modal template
     * @type {string}
     */
    modalTemplate = null,
    /**
     * The error template
     * @type {string}
     */
    errorTemplate = null,
    /**
     * State
     */
    state = {
      /**
       * the modal jquery element
       * @type {null}
       */
      $modal: null,
      /**
       * Notice counter
       * @type {number}
       */
      noticeCounter: 0,
      /**
       * The callback to call on click on next button
       */
      nextCallback: function (e) {
      }
    };


  /**
   * Opens the modal
   * @param data
   */
  function open(data) {
    var modalHtml = modalTemplate(data);
    $(doc.body).append(modalHtml);

    state.$modal = $(selectors.modal);
    state.$modal.find(selectors.modalCloseIcon).click(close);
    state.$modal.find(selectors.modalBackground).click(close);
    state.$modal.find(selectors.modalNextStepButton).click(onNextStepClick);
    state.$modal.show();
  }

  /**
   * Closes the modal
   */
  function close() {
    state.$modal.hide();
    state.$modal.remove();
  }

  /**
   * Call the next step callback;
   * @param e
   */
  function onNextStepClick(e) {
    state.nextCallback(e);
  }

  /**
   * Shows some content within the modal
   * @param html
   */
  function showContent(html) {
    $(selectors.modalBodyContent).html(html);
  }

  /**
   * Shows an error
   * @param error
   * @returns {number} the error token
   */
  function showError(error) {
    var token = ++state.noticeCounter;
    var errorHtml = errorTemplate({
      token: token,
      error: error
    });
    state.$modal.find(selectors.modalNotice).append(errorHtml);
    state.$modal.find(selectors.modalNoticeDismissIcon).click(function (e) {
      e.preventDefault();
      hideError(token);
    });
    return token;
  }

  /**
   * Hides an error
   * @param token the error token
   */
  function hideError(token) {
    if (isNaN(token)) {
      $(selectors.modalNotice).empty();
    } else {
      $(selectors.modalErrorNotice(token)).remove();
    }
  }

  return {
    initialize: function () {
      modalTemplate = wp.template(modalTemplateId);
      errorTemplate = wp.template(errorTemplateId);
    },
    open: open,
    close: close,
    showContent: showContent,
    showError: showError,
    hideError: hideError,
    onNextCall: function (callback) {
      state.nextCallback = callback;
    }
  }
})(window, document, jQuery, wp);

/**
 * Polylang translation plugin to inject translation options
 */
Supertext.Polylang = (function (win, doc, $, wp) {
  'use strict';

  var
    /**
     * The order step 1 template id
     * @type {string}
     */
    orderStepOneTemplateId = 'sttr-order-step-1',
    /**
     * Order translation bulk action option value
     * @type {string}
     */
    orderTranslationBulkActionValue = 'orderTranslation',
    /**
     * The selectors of different html elements
     */
    selectors = {
      orderItemList: '.sttr-order-list',
      orderItemRemoveIcon: '.dashicons-no-alt',
      orderItemRemoveButton: '#sttr-order-remove-item',
      firstOrderStepForm: '#sttr-order-step-1-form',
      orderSourceLanguageInput: '#sttr-order-source-language',
      orderSourceLanguageLabel: '#sttr-order-source-language-label',
      orderTargetLanguageSelect: '#sttr-order-target-language',
      orderTargetLanguageSelectOptions: '#sttr-order-target-language option'
    },
    /**
     * Context data containing information about plugin and environment
     */
    context,
    /**
     * Modal module
     */
    modal,
    /**
     * The order step 1 template
     * @type {string}
     */
    orderStepOneTemplate = null,
    /**
     * The validation rules
     */
    validationRules = {
      posts: function (pass, fail) {
        var validationKey = 'posts';

        if (!isEachPostInSameLanguage(state.posts)) {
          fail(validationKey, supertextTranslationL10n.errorMessageNotAllPostInSameLanguage);
          return;
        }

        pass(validationKey);
      },
      targetLanguage: function (pass, fail) {
        var validationKey = 'targetLanguage';

        if ($(selectors.orderTargetLanguageSelect).val() == '') {
          fail(validationKey, supertextTranslationL10n.errorValidationSelectTargetLanguage);
          return;
        }

        pass(validationKey);
      }
    },
    /**
     * Nested validation module
     */
    validation = (function () {
      var
        errors = {},
        lastCheckedRuleKey = '';

      function pass(key) {
        lastCheckedRuleKey = key;

        if (!errors.hasOwnProperty(key)) {
          return;
        }

        delete errors[key];
      }

      function fail(key, message) {
        lastCheckedRuleKey = key;

        if (errors.hasOwnProperty(key)) {
          return;
        }

        errors[key] = message;
      }

      function check(rule) {
        rule(pass, fail);
        return result();
      }

      function result() {
        return {
          fail: function (onFail) {
            if (!errors.hasOwnProperty(lastCheckedRuleKey)) {
              return this;
            }

            onFail([errors[lastCheckedRuleKey]]);
            return this;
          },
          pass: function (onPass) {
            if (errors.hasOwnProperty(lastCheckedRuleKey)) {
              return this;
            }

            onPass();
            return this;
          }
        }
      }

      function checkAll(rules) {
        $.each(rules, function (key, rule) {
          if (!rules.hasOwnProperty(key)) {
            return;
          }

          rule(pass, fail);
        });

        return results();
      }

      function results() {
        return {
          fail: function (onFail) {
            if ($.isEmptyObject(errors)) {
              return this;
            }

            var errorMessages = [];

            $.each(errors, function (key, error) {
              if (!errors.hasOwnProperty(key)) {
                return;
              }

              errorMessages.push(error);
            });

            onFail(errorMessages);
            return this;
          },
          pass: function (onPass) {
            if (!$.isEmptyObject(errors)) {
              return this;
            }

            onPass();
            return this;
          }
        }
      }

      return {
        check: check,
        checkAll: checkAll,
        results: results
      }
    })(),
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
     * Container for current state data
     */
    state = {
      posts: [],
      languageMismatchErrorToken: null,
      validationErrorToken: null
    };

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
    var selectName = $(this).attr('id').substr(2);

    if ($('select[name="' + selectName + '"]').val() !== orderTranslationBulkActionValue) {
      return true;
    }

    e.preventDefault();

    var posts = [];
    $('input[name="post[]"]:checked').each(function () {
      posts.push($(this).val());
    });

    if (posts.length > 0) {
      openModal();
      loadFirstOrderStep(posts);
    } else {
      alert(supertextTranslationL10n.alertPleaseSelect);
    }

    return false;
  }

  /**
   * Opens the modal
   */
  function openModal() {
    modal.open({
      title: supertextTranslationL10n.modalTitle
    });
  }

  /**
   * Loads post data for order step one
   * @param posts
   */
  function loadFirstOrderStep(posts) {
    $.get(
      context.ajaxUrl,
      {
        action: 'sttr_getPostTranslationData',
        posts: posts
      }
    ).done(
      function (data) {
        if (data.head.status != 'success') {
          modal.showContent('');
          modal.showError({
            title: supertextTranslationL10n.generalError,
            message: data.body.reason
          });
          return;
        }

        addFirstOrderStep(data);
      }
    ).fail(
      function (jqXHR, textStatus, errorThrown) {
        modal.showContent('');
        modal.showError({
          title: supertextTranslationL10n.generalError,
          message: jqXHR.status + ' ' + textStatus,
          details: errorThrown
        });
      }
    );
  }

  /**
   * Add the first order step to the modal content
   * @param data
   */
  function addFirstOrderStep(data) {
    state.posts = data.body;

    modal.showContent(orderStepOneTemplate({
      sourceLanguage: isEachPostInSameLanguage(state.posts) ? supertextTranslationL10n.languages[state.posts[0].languageCode] : '-',
      languages: supertextTranslationL10n.languages,
      posts: state.posts
    }));

    modal.onNextCall(function () {
      validation
        .checkAll(validationRules)
        .fail(showValidationErrors)
        .pass(loadSecondOrderStep);
    });

    initializeOrderItemList();

    checkOrderItems();
  }

  /**
   * Loads post data for order step two form
   */
  function loadSecondOrderStep() {
    var formData = $(selectors.firstOrderStepForm).serializeArray();
    $.post(
      context.ajaxUrl + '?action=sttr_getOffer',
      formData
    ).done(
      function (data) {
        if (data.head.status != 'success') {
          modal.showContent('');
          modal.showError({
            title: supertextTranslationL10n.generalError,
            message: data.body.reason
          });
          return;
        }


      }
    ).fail(
      function (jqXHR, textStatus, errorThrown) {
        modal.showContent('');
        modal.showError({
          title: supertextTranslationL10n.generalError,
          message: jqXHR.status + ' ' + textStatus,
          details: errorThrown
        });
      }
    );
  }

  /**
   * Initializes the order item list
   */
  function initializeOrderItemList() {
    var $itemAnchors = $(selectors.orderItemList + ' li a');
    $itemAnchors.click(onOrderItemAnchorClick);
    $itemAnchors.find(selectors.orderItemRemoveIcon).click(onOrderItemRemoveIconClick);
    $(selectors.orderItemRemoveButton).click(onOrderItemRemoveButtonClick);

    activateOrderItem.call($(selectors.orderItemList + ' li:first a'));
  }

  /**
   * Check order items
   */
  function checkOrderItems() {
    validation
      .check(validationRules.posts)
      .fail(showValidationErrors)
      .pass(hideValidationError)
      .pass(setLanguages);

    if (state.posts.length == 1) {
      $(selectors.orderItemRemoveButton).hide();
    }
  }

  /**
   * Sets the languages to the form
   */
  function setLanguages() {
    var sourceLanguageCode = state.posts[0].languageCode;
    $(selectors.orderSourceLanguageLabel).html(supertextTranslationL10n.languages[sourceLanguageCode]);
    $(selectors.orderSourceLanguageInput).val(sourceLanguageCode);

    $(selectors.orderTargetLanguageSelectOptions).each(function(){
      if($(this).val() === sourceLanguageCode){
        $(this).hide();
      }
    });
  }

  /**
   * Check whether all posts are in same language
   * @returns {boolean}
   */
  function isEachPostInSameLanguage(posts) {
    var languageCode = null;
    var isEachPostInSameLanguage = true;
    $.each(posts, function (index, post) {
      if (index === 0) {
        languageCode = post.languageCode;
      } else {
        isEachPostInSameLanguage = isEachPostInSameLanguage && post.languageCode == languageCode;
      }
    });
    return isEachPostInSameLanguage;
  }

  /**
   * Order item anchor click event handler
   * @param e
   */
  function onOrderItemAnchorClick(e) {
    e.preventDefault();
    activateOrderItem.call(this);
  }

  /**
   * Activate order item
   */
  function activateOrderItem() {
    var $clickedItemAnchor = $(this);
    var $activeItemAnchor = $(selectors.orderItemList + ' li.active a');

    if ($activeItemAnchor.length > 0) {
      $($activeItemAnchor.attr('href')).hide();
      $activeItemAnchor.parent().removeClass('active');
    }

    $($clickedItemAnchor.attr('href')).show();
    $clickedItemAnchor.parent().addClass('active');
  }

  /**
   * Order item remove icon click event handler
   * @param e
   */
  function onOrderItemRemoveIconClick(e) {
    e.preventDefault();
    removeOrderItem.call($(this).parent());
  }

  /**
   * Order item remove button click event handler
   * @param e
   */
  function onOrderItemRemoveButtonClick(e) {
    e.preventDefault();
    removeOrderItem.call($(selectors.orderItemList + ' li.active a'));
    activateOrderItem.call($(selectors.orderItemList + ' li:first a'));
  }

  /**
   * Removes an order item
   */
  function removeOrderItem() {
    if (state.posts.length == 1) {
      return;
    }

    var $itemAnchor = $(this);
    $($itemAnchor.attr('href')).remove();
    $itemAnchor.parent().remove();

    var postIdToRemove = $itemAnchor.data('id');
    state.posts = state.posts.filter(function (post) {
      return post.id != postIdToRemove;
    });

    checkOrderItems();
  }

  /**
   *
   * @param errors string[]
   */
  function showValidationErrors(errors) {
    if (state.validationErrorToken !== null) {
      hideValidationError();
    }

    state.validationErrorToken = modal.showError({
      title: supertextTranslationL10n.validationError,
      message: errors
    });
  }

  /**
   *
   */
  function hideValidationError() {
    modal.hideError(state.validationErrorToken);
    state.validationErrorToken = null;
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

  return {
    /**
     * Loading the module
     */
    initialize: function (externals) {
      context = externals.context;
      modal = externals.modal;

      if (!context.isPluginWorking) {
        //TODO show error
        return;
      }

      orderStepOneTemplate = wp.template(orderStepOneTemplateId);

      if (context.screen == 'post') {
        initializePostScreen();
      } else if (context.screen == 'edit') {
        initializeEditScreen();
      }
    },
    openOrderForm: openOrderForm
  }

})(window, document, jQuery, wp);

// Load on load. yass.
jQuery(document).ready(function () {
  Supertext.Modal.initialize();

  Supertext.Polylang.initialize({
    context: Supertext.Context || {
      isPluginWorking: false
    },
    modal: Supertext.Modal
  });
});
