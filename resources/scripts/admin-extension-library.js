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
  function removeButton(token) {
    $(selectors.modalButton(token)).remove();
  }

  /**
   * Enables a button
   * @param token
   */
  function enableButton(token) {
    $(selectors.modalButton(token))
      .removeClass('button-disabled')
      .prop('disabled', false);
  }

  /**
   * Disables a button
   * @param token
   */
  function disableButton(token) {
    $(selectors.modalButton(token))
      .addClass('button-disabled')
      .prop('disabled', true);
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
    removeButton: removeButton,
    enableButton: enableButton,
    disableButton: disableButton
  }
})(window, document, jQuery);

Supertext.Validation = (function ($) {
  'use strict';

  function check(rule) {
    return checkAll([rule]);
  }

  function checkAll(rules) {
    return $.Deferred(function (defer) {
      var errors = [];

      $.each(rules, function (index, rule) {
        rule(function (error) {
          errors.push(error);
        });
      });

      if (errors.length > 0) {
        defer.reject(errors);
        return;
      }

      defer.resolve();
    }).promise();
  }

  return {
    check: check,
    checkAll: checkAll
  }
})(jQuery);

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
     * May be overridden to true on load
     */
    inTranslation = false,
    /**
     * Order process steps
     * @type {Array}
     */
    steps = [],
    /**
     * Container for current state data
     */
    state = {
      currentStepNumber: 0
    };

  function Step() {
  }

  Step.prototype = {
    validationRules: {},
    load: function () {
    },
    validate: function () {
      return validation.checkAll(this.validationRules);
    },
    save: function () {
    }
  };

  var contentStep = $.extend(new Step(), new function () {
    var self = this;

    self.validationRules = {
      posts: function (fail) {
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

        if (isAPostInTranslation) {
          fail(supertextTranslationL10n.errorValidationSomePostInTranslation);
        }

        if (!isEachPostInSameLanguage) {
          fail(supertextTranslationL10n.errorValidationNotAllPostInSameLanguage);
        }
      },
      targetLanguage: function (fail) {
        if ($(selectors.orderTargetLanguageSelect).val() == '') {
          fail(supertextTranslationL10n.errorValidationSelectTargetLanguage);
        }
      }
    };

    /**
     * Loads the content step
     */
    self.load = function () {
      doGetRequest(
        context.ajaxUrl, {
          action: 'sttr_getPostTranslationData',
          postIds: state.postIds
        }
      ).done(addContentStep);
    };

    /**
     * Saves content step data
     */
    self.save = function () {
      state.contentFormData = $(selectors.contentStepForm).serializeArray();
    };

    /**
     * Add the first order step to the modal content
     * @param data
     */
    function addContentStep(data) {
      state.posts = data.body;

      $(selectors.orderStep).html(template.contentStep({
        posts: state.posts
      }));

      initializeOrderItemList();

      checkOrderItems();
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
        .check(self.validationRules.posts)
        .fail(showValidationErrors)
        .done(hideValidationError)
        .done(setLanguages);

      if (state.posts.length == 1) {
        $(selectors.orderItemRemoveButton).hide();
      }
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
     * Order item anchor click event handler
     * @param e
     */
    function onOrderItemAnchorClick(e) {
      e.preventDefault();
      activateOrderItem.call(this);
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
     * Sets the languages to the form
     */
    function setLanguages() {
      var sourceLanguageCode = state.posts[0].languageCode;
      $(selectors.orderSourceLanguageLabel).html(supertextTranslationL10n.languages[sourceLanguageCode]);
      $(selectors.orderSourceLanguageInput).val(sourceLanguageCode);

      $.each(supertextTranslationL10n.languages, function (code, language) {
        if (code === sourceLanguageCode) {
          return;
        }

        $(selectors.orderTargetLanguageSelect).append($('<option>', {
          value: code,
          text: language
        }));
      });
    }
  });

  var quoteStep = $.extend(new Step(), new function () {
    var self = this;

    self.validationRules = {
      quote: function (fail) {
        if ($(selectors.checkedQuote).length === 0) {
          fail(supertextTranslationL10n.errorValidationSelectQuote);
        }
      }
    };

    /**
     * Loads post data for order step two form
     */
    self.load = function () {
      doPostRequest(
        context.ajaxUrl + '?action=sttr_getOffer',
        state.contentFormData
      ).done(addQuoteStep);
    };

    self.save = function () {
      state.quoteFormData = $(selectors.quoteStepForm).serializeArray();
    };

    /**
     * Add the first order step to the modal content
     * @param data
     */
    function addQuoteStep(data) {
      $(selectors.orderStep).html(template.quoteStep({
        options: data.body.options
      }));
    }
  });

  var confirmationStep = $.extend(new Step(), new function () {
    var self = this;

    self.load = function () {
      doPostRequest(
        context.ajaxUrl + '?action=sttr_createOrder',
        state.contentFormData.concat(state.quoteFormData)
      ).done(addConfirmationStep);
    };

    function addConfirmationStep(data) {
      $(selectors.orderStep).html(template.confirmationStep({
        message: data.body.message
      }));
    }
  });

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

    var postIds = [];
    $('input[name="post[]"]:checked').each(function () {
      postIds.push($(this).val());
    });

    if (postIds.length > 0) {
      state.postIds = postIds;
      openModal();
      addOrderProgressBar();
      addCancelButton();
      addStepButtons();
      loadStep(1);
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
   * Adds the cancel button
   */
  function addCancelButton() {
    state.cancelButtonToken = modal.addButton(
      supertextTranslationL10n.cancel,
      'secondary',
      function () {
        modal.close();
      }
    );
  }

  /**
   * Adds the close button
   */
  function addCloseButton() {
    modal.addButton(
      supertextTranslationL10n.close,
      'secondary',
      function () {
        modal.close();
      }
    );
  }

  /**
   * Adds the buttons
   */
  function addStepButtons() {
    state.backButtonToken = modal.addButton(
      supertextTranslationL10n.back,
      'secondary',
      moveToPreviousStep
    );

    state.nextButtonToken = modal.addButton(
      supertextTranslationL10n.next,
      'primary',
      moveToNextStep
    );
  }

  /**
   * Moves to next step
   */
  function moveToNextStep() {
    steps[state.currentStepNumber - 1].validate()
      .fail(showValidationErrors)
      .done(hideValidationError)
      .done(function () {
        steps[state.currentStepNumber - 1].save();
        loadStep(state.currentStepNumber + 1)
      });
  }

  /**
   * Moves to previous step
   */
  function moveToPreviousStep() {
  }

  /**
   * Loads a step
   * @param stepNumber
   * @param data
   */
  function loadStep(stepNumber) {
    updateButtonStates(stepNumber);
    updateOrderProgressBar(stepNumber);
    addStepLoader();

    steps[stepNumber - 1].load();

    state.currentStepNumber = stepNumber;
  }

  /**
   * Updates the step button states according to step number and amount steps
   * @param stepNumber
   */
  function updateButtonStates(stepNumber) {
    if (stepNumber < steps.length) {
      modal.enableButton(state.nextButtonToken);
    } else {
      modal.removeButton(state.nextButtonToken);
      modal.removeButton(state.backButtonToken);
      modal.removeButton(state.cancelButtonToken);
      addCloseButton();
      return;
    }

    if (stepNumber > 1) {
      modal.enableButton(state.backButtonToken);
    } else {
      modal.disableButton(state.backButtonToken);
    }
  }

  /**
   * Adds the step loader
   */
  function addStepLoader() {
    $(selectors.orderStep).html(template.stepLoader({}));
  }

  /**
   * Updates the order progress bar
   * @param stepNumber
   */
  function updateOrderProgressBar(stepNumber) {
    $(selectors.orderProgressBarSteps).each(function (index, step) {
      if (index == stepNumber - 1) {
        $(step).addClass('active');
        return;
      }
      $(step).removeClass('active');
    });
  }

  /**
   * Shows validation errors
   * @param errors string[]
   */
  function showValidationErrors(errors) {
    if (state.validationErrorToken) {
      hideValidationError();
    }

    state.validationErrorToken = modal.showError({
      title: supertextTranslationL10n.validationError,
      message: errors
    });
  }

  /**
   * Hides the validation errors
   */
  function hideValidationError() {
    if (!state.validationErrorToken) {
      return;
    }

    modal.hideError(state.validationErrorToken);
    state.validationErrorToken = null;
  }

  /**
   * Does post request
   * @param url
   * @param data
   * @returns {*}
   */
  function doGetRequest(url, data) {
    return $.Deferred(function (defer) {
      $.get(
        url,
        data
      ).done(
        function (responseData) {
          if (responseData.responseType != 'success') {
            modal.showError({
              title: supertextTranslationL10n.generalError,
              message: responseData.body
            });
            return;
          }
          defer.resolve(responseData);
        }
      ).fail(
        showAjaxError
      ).fail(
        defer.reject
      );
    }).promise();
  }

  /**
   * Does post request
   * @param url
   * @param data
   * @returns {*}
   */
  function doPostRequest(url, data) {
    return $.Deferred(function (defer) {
      $.post(
        url,
        data
      ).done(
        function (responseData) {
          if (responseData.responseType != 'success') {
            modal.showError({
              title: supertextTranslationL10n.generalError,
              message: responseData.body
            });
            return;
          }
          defer.resolve(responseData);
        }
      ).fail(
        showAjaxError
      ).fail(
        defer.reject
      );
    }).promise();
  }

  /**
   * Shows an ajax error
   * @param jqXHR
   * @param textStatus
   * @param errorThrown
   */
  function showAjaxError(jqXHR, textStatus, errorThrown) {
    modal.showError({
      title: supertextTranslationL10n.networkError,
      message: jqXHR.status + ' ' + textStatus,
      details: errorThrown
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

      steps = [contentStep, quoteStep, confirmationStep];

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
