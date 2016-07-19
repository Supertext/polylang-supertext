var Supertext = Supertext || {};

Supertext.Template = (function (win, doc, $, wp) {
  'use strict';

  var
    /**
     * All template ids
     */
    templateIds = {
      /**
       * The order link row template id
       */
      orderLinkRow: 'sttr-order-link-row',
      /**
       * The order progress bar  template id
       */
      orderProgressBar: 'sttr-order-progress-bar',
      /**
       * The modal template
       */
      modal: 'sttr-modal',
      /**
       * The error template id
       */
      modalError: 'sttr-modal-error',
      /**
       * The button template id
       */
      modalButton: 'sttr-modal-button',
      /**
       * The loader template
       */
      stepLoader: 'sttr-step-loader',
      /**
       * The content step id
       */
      contentStep: 'sttr-content-step',
      /**
       * The quote step id
       */
      quoteStep: 'sttr-quote-step',
      /**
       * The confirmation step id
       */
      confirmationStep: 'sttr-confirmation-step'
    },
    /**
     * All templates
     */
    templates = {},
    /**
     * return object
     */
    returnObject = {
      initialize: function () {
        $.each(templateIds, function (key, name) {
          returnObject[key] = function (data) {
            return getHtml(name, data);
          };
        });
      }
    };

  function getHtml(templateId, data) {
    if (!templates.hasOwnProperty(templateId)) {
      templates[templateId] = wp.template(templateId);
    }

    return templates[templateId](data);
  }

  return returnObject;
})(window, document, jQuery, wp);

Supertext.Modal = (function (win, doc, $) {
  'use strict';

  var
    /**
     * The selectors of different html elements
     */
    selectors = {
      modal: '#sttr-modal',
      modalBodyContent: '#sttr-modal-body-content',
      modalNotice: '#sttr-modal-notice',
      modalCloseIcon: '.sttr-modal-icon-close',
      modalBackground: '.sttr-modal-background',
      modalFooter: '.sttr-modal-footer',
      modalNoticeDismissIcon: '.notice-dismiss',
      modalErrorNotice: function (token) {
        return '#sttr-modal-error-' + token
      },
      modalButton: function (token) {
        return '#sttr-modal-button-' + token
      }
    },
    /**
     * Template module
     */
    template,
    /**
     * State
     */
    state = {
      /**
       * Notice counter
       * @type {number}
       */
      noticeCounter: 0,
      /**
       * Button counter
       * @type {number}
       */
      buttonCounter: 0
    };


  /**
   * Opens the modal
   * @param data
   */
  function open(data) {
    var modalHtml = template.modal(data);
    $(doc.body).append(modalHtml);

    state.$modal = $(selectors.modal);
    state.$modal.find(selectors.modalCloseIcon).click(close);
    state.$modal.find(selectors.modalBackground).click(close);
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
    var errorHtml = template.modalError({
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

  /**
   * Adds a button
   * @param innerHtml
   * @param type
   * @param onClickEventHandler
   */
  function addButton(innerHtml, type, onClickEventHandler) {
    var token = ++state.buttonCounter;

    $(selectors.modalFooter).prepend(template.modalButton({
      token: token,
      innerHtml: innerHtml,
      type: type
    }));

    $(selectors.modalButton(token)).click(onClickEventHandler);

    return token;
  }

  /**
   * Removes a button
   * @param token
   */
  function removeButton(token){
    $(selectors.modalButton(token)).remove();
  }

  /**
   * Disables a button
   * @param token
   */
  function disableButton(token) {
    $(selectors.modalButton(token)).addClass('button-disabled');
  }

  /**
   * Enables a button
   * @param token
   */
  function enableButton(token) {
    $(selectors.modalButton(token)).removeClass('button-disabled');
  }

  return {
    initialize: function (externals) {
      template = externals.template;
    },
    open: open,
    close: close,
    showContent: showContent,
    showError: showError,
    hideError: hideError,
    addButton: addButton,
    removeButton: removeButton
  }
})(window, document, jQuery);

Supertext.Validation = (function (win, doc, $) {
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
})(window, document, jQuery);

/**
 * Polylang translation plugin to inject translation options
 */
Supertext.Polylang = (function (win, doc, $) {
  'use strict';

  var
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
      orderStep: '#sttr-order-step',
      contentStepForm: '#sttr-content-step-form',
      orderProgressBarSteps: '#sttr-order-progress-bar li',
      orderSourceLanguageInput: '#sttr-order-source-language',
      orderSourceLanguageLabel: '#sttr-order-source-language-label',
      orderTargetLanguageSelect: '#sttr-order-target-language',
      checkedQuote: 'input[name="translationType"]:checked',
      quoteStepForm: '#sttr-quote-step-form'
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
     * Template module
     */
    template,
    /**
     * Validation module
     */
    validation,
    /**
     * The validation rules
     */
    validationRules = {
      contentStep:{
        posts: function (pass, fail) {
          var validationKey = 'posts';

          var languageCode = null;
          var isEachPostInSameLanguage = true;
          var isAPostInTranslation = false;
          $.each(state.posts, function (index, post) {
            if (index === 0) {
              languageCode = post.languageCode;
            } else {
              isEachPostInSameLanguage = isEachPostInSameLanguage && post.languageCode == languageCode;
            }

            isAPostInTranslation = isAPostInTranslation || post.isInTranslation;
          });

          if(isAPostInTranslation) {
            fail(validationKey, supertextTranslationL10n.errorValidationSomePostInTranslation);
            return;
          }

          if (!isEachPostInSameLanguage) {
            fail(validationKey, supertextTranslationL10n.errorValidationNotAllPostInSameLanguage);
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
      quoteStep: {
        quote: function(pass, fail) {
          var validationKey = 'quote';

          if($(selectors.checkedQuote).length === 0){
            fail(validationKey, supertextTranslationL10n.errorValidationSelectQuote);
            return;
          }

          pass(validationKey);
        }
      }
    },
    /**
     * May be overridden to true on load
     */
    inTranslation = false,
    /**
     * Container for current state data
     */
    state = {};

  /**
   * Initialize on post screen
   */
  function initializePostScreen() {
    if ($('#post-translations').length == 1) {
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

      languageRow.after(template.orderLinkRow({targetLanguageCode: languageCode}));
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
      addOrderProgressBar();
      addCancelButton();
      addStepLoader();
      loadContentStep(posts);
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
   * Adds the order progress bar
   */
  function addOrderProgressBar() {
    modal.showContent(template.orderProgressBar({}));
  }

  /**
   * Adds the buttons
   */
  function addCancelButton() {
    modal.addButton(
      supertextTranslationL10n.cancel,
      'secondary',
      function () {
        modal.close();
      }
    );
  }

  /**
   * Adds the step loader
   */
  function addStepLoader(){
    $(selectors.orderStep).html(template.stepLoader({}));
  }

  /**
   * Loads post data for order step one
   * @param posts
   */
  function loadContentStep(posts) {
    updateOrderProgress(1);
    addStepLoader();

    $.get(
      context.ajaxUrl,
      {
        action: 'sttr_getPostTranslationData',
        posts: posts
      }
    ).done(
      function (data) {
        if (data.responseType != 'success') {
          modal.showError({
            title: supertextTranslationL10n.generalError,
            message: data.body
          });
          return;
        }

        addContentStep(data);
      }
    ).fail(
      function (jqXHR, textStatus, errorThrown) {
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
  function addContentStep(data) {
    state.posts = data.body;

    $(selectors.orderStep).html(template.contentStep({
      posts: state.posts
    }));

    state.nextButtonToken = modal.addButton(
      supertextTranslationL10n.next,
      'secondary',
      function () {
        validation
          .checkAll(validationRules.contentStep)
          .fail(showValidationErrors)
          .pass(hideValidationError)
          .pass(loadQuoteStep);
      }
    );

    initializeOrderItemList();

    checkOrderItems();
  }

  function updateOrderProgress(stepNumber){
    $(selectors.orderProgressBarSteps).each(function(index, step){
      if(index == stepNumber-1){
        $(step).addClass('active');
        return;
      }
      $(step).removeClass('active');
    });
  }


  /**
   * Loads post data for order step two form
   */
  function loadQuoteStep() {
    state.contentFormData = $(selectors.contentStepForm).serializeArray();
    modal.removeButton(state.nextButtonToken);

    updateOrderProgress(2);
    addStepLoader();

    $.post(
      context.ajaxUrl + '?action=sttr_getOffer',
      state.contentFormData
    ).done(
      function (data) {
        if (data.responseType != 'success') {
          modal.showError({
            title: supertextTranslationL10n.generalError,
            message: data.body
          });
          return;
        }

        addQuoteStep(data);
      }
    ).fail(
      function (jqXHR, textStatus, errorThrown) {
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
  function addQuoteStep(data) {
    $(selectors.orderStep).html(template.quoteStep({
      options: data.body.options
    }));

    state.nextButtonToken = modal.addButton(
      supertextTranslationL10n.orderTranslation,
      'primary',
      function () {
        validation
          .checkAll(validationRules.quoteStep)
          .fail(showValidationErrors)
          .pass(hideValidationError)
          .pass(loadConfirmationStep);
      }
    );
  }

  function loadConfirmationStep(){
    state.quoteFormData = $(selectors.quoteStepForm).serializeArray();

    modal.removeButton(state.nextButtonToken);

    updateOrderProgress(3);
    addStepLoader();

    $.post(
      context.ajaxUrl + '?action=sttr_createOrder',
      state.contentFormData.concat(state.quoteFormData)
    ).done(
      function (data) {
        if (data.responseType != 'success') {
          modal.showError({
            title: supertextTranslationL10n.generalError,
            message: data.body
          });
          return;
        }

        addConfirmationStep(data);
      }
    ).fail(
      function (jqXHR, textStatus, errorThrown) {
        modal.showError({
          title: supertextTranslationL10n.generalError,
          message: jqXHR.status + ' ' + textStatus,
          details: errorThrown
        });
      }
    );
  }

  function addConfirmationStep(data){
    $(selectors.orderStep).html(template.confirmationStep({
      message: data.body.message
    }));
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
      .check(validationRules.contentStep.posts)
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

    $.each(supertextTranslationL10n.languages, function(code, language){
      if (code === sourceLanguageCode) {
        return;
      }

      $(selectors.orderTargetLanguageSelect).append($('<option>', {
        value: code,
        text: language
      }));
    });
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

    var postIdToRemove = $itemAnchor.data('post-id');
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
      template = externals.template;
      validation = externals.validation;

      if (!context.isPluginWorking) {
        return;
      }

      if (context.screen == 'post') {
        initializePostScreen();
      } else if (context.screen == 'edit') {
        initializeEditScreen();
      }
    },
    openOrderForm: openOrderForm
  }

})(window, document, jQuery);

// Load on load. yass.
jQuery(document).ready(function () {
  Supertext.Template.initialize();

  Supertext.Modal.initialize({
    template: Supertext.Template
  });

  Supertext.Polylang.initialize({
    context: Supertext.Context || {
      isPluginWorking: false
    },
    modal: Supertext.Modal,
    template: Supertext.Template,
    validation: Supertext.Validation
  });
});
