var Supertext = Supertext || {};

Supertext.Settings = {};

//Users tab module
Supertext.Settings.Users = (function ($) {
  'use strict';

  var $tableBody,
    $rowTemplate;

  function addUserField() {
    var $newRow = $rowTemplate.clone();
    $newRow.find('.remove-user-button').click(removeUserField);
    $tableBody.append($newRow);
  }

  function removeUserField() {
    $(this).parent('td').parent('tr').remove();

    if ($tableBody.find('tr').length === 0) {
      addUserField();
    }
  }

  return {
    initialize: function (options) {
      options = options || {};

      $tableBody = $("#tblStFields tbody");
      $tableBody.find('tr .saved-user-id-hidden');
      $tableBody.find('tr .remove-user-button').click(removeUserField);
      //select users in wp dropdown
      $tableBody.find('tr .saved-user-id-hidden').each(function () {
        var $this = $(this);
        $this.prev().val($this.val());
      });

      $rowTemplate = $('#tblStFields tr:last').clone();
      $rowTemplate.find('input').val('');

      $('#btnAddUser').click(addUserField);
    }
  };
})(jQuery);

//Custom Fields tab module
Supertext.Settings.TranslatableFields = (function ($) {
  'use strict';
  var customFieldsSettings = (function () {
    var $customFieldInputCopy;

    function addCustomFieldInput() {
      var $this = $(this);
      var $newCustomFieldInput = $customFieldInputCopy.clone();

      $newCustomFieldInput.children('.custom-field-remove-input')
        .click(removeCustomFieldInput);

      $newCustomFieldInput.insertBefore($this).show();
    }

    function removeCustomFieldInput() {
      $(this).parent().remove();
    }

    return {
      initialize: function (options) {

        $customFieldInputCopy = $('#translatablefieldsSettingsForm .custom-field-input').last().clone();

        $('#translatablefieldsSettingsForm .custom-field-remove-input')
          .click(removeCustomFieldInput);

        $('#translatablefieldsSettingsForm .custom-field-add-input')
          .click(addCustomFieldInput);
      }
    };

  }());

  var pluginCustomFieldsSettings = (function () {
    var
      fieldDefinitionsTrees = {},
      checkedFieldsInputs = {};

    function setCheckedFields() {
      $.each(fieldDefinitionsTrees, function(pluginId, tree){
        var checkedNodes = tree.jstree("get_checked", false);
        checkedFieldsInputs[pluginId].val(checkedNodes.join(','));
      });
    }

    return {
      initialize: function (options) {
        options = options || {};

        for(var pluginId in savedFieldDefinitionIds){
          if (!savedFieldDefinitionIds.hasOwnProperty(pluginId)) {
            continue;
          }

          fieldDefinitionsTrees[pluginId] = $('#fieldDefinitionsTree'+pluginId);
          checkedFieldsInputs[pluginId] = $('#checkedFieldsInput'+pluginId);

          fieldDefinitionsTrees[pluginId].jstree({
            'core': {
              'themes': {
                'name': 'wordpress-dark'
              }
            },
            'plugins': ['checkbox'],
            'checkbox': {
              'keep_selected_style': false
            }
          });

          fieldDefinitionsTrees[pluginId].jstree('select_node', savedFieldDefinitionIds[pluginId]);
        }

        $('#translatablefieldsSettingsForm').submit(setCheckedFields);
      }
    };
  }());

  return {
    initialize: function (options) {
      options = options || {};

      customFieldsSettings.initialize(options);
      pluginCustomFieldsSettings.initialize(options);
    }
  };
})(jQuery);

//Shortcodes tab module
Supertext.Settings.Shortcodes = (function ($) {
  'use strict';

  var shortcodeSettingTemplate,
      shortcodeAttributeTemplate;

  function addShortcodeSetting($container, name, contentEncoding){
    var lastIndex = $container.data('lastIndex');
    var newIndex =  lastIndex === undefined ? 0 : lastIndex + 1;

    var html = shortcodeSettingTemplate({
      shortcodeIndex: newIndex,
      name: name,
      contentEncoding: contentEncoding
    });

    var $shortcodeSetting = $(html);
    $container.append($shortcodeSetting);
    $container.data('lastIndex', newIndex);

    $shortcodeSetting.children('.shortcode-remove-setting')
      .click(function(){
        $shortcodeSetting.remove();
      });

    $shortcodeSetting.find('.shortcode-attribute-add-input')
      .click(function(){
        addAttributeInput($shortcodeSetting.find('.shortcode-setting-attributes'), newIndex);
      });

    initEncodingAutoComplete($shortcodeSetting);
    initShortcodeAutoComplete($shortcodeSetting);
  }

  function addAttributeInput($container, shortcodeIndex, name, encoding) {
    var lastIndex = $container.data('lastIndex');
    var newIndex =  lastIndex === undefined ? 0 : lastIndex + 1;

    var $shortcodeAttribute = $(shortcodeAttributeTemplate({
      shortcodeIndex: shortcodeIndex,
      attributeIndex: newIndex,
      name: name,
      encoding: encoding
    }));

    $container.append($shortcodeAttribute);
    $container.data('lastIndex', newIndex);

    $shortcodeAttribute.children('.shortcode-attribute-remove-input')
      .click(function(){
        $shortcodeAttribute.remove();
      });

    initEncodingAutoComplete($shortcodeAttribute);
  }

  function split(val) {
    return val.split(/,\s*/);
  }

  function extractLast(term) {
    return split(term).pop();
  }

  function initShortcodeAutoComplete($element){
    $element.find('.shortcode-input-name')
      .bind("keydown", function (event) {
        if (event.keyCode === $.ui.keyCode.TAB &&
          $(this).autocomplete("instance").menu.active) {
          event.preventDefault();
        }
      })
      .autocomplete({
        minLength: 0,
        source: function (request, response) {
          // delegate back to autocomplete, but extract the last term
          response($.ui.autocomplete.filter(registeredShortcodes, request.term));
        }
      }
    );
  }

  function initEncodingAutoComplete($element){
    $element.find('.shortcode-input-encoding')
      .bind("keydown", function (event) {
        if (event.keyCode === $.ui.keyCode.TAB &&
          $(this).autocomplete("instance").menu.active) {
          event.preventDefault();
        }
      })
      .autocomplete({
        minLength: 0,
        source: function (request, response) {
          // delegate back to autocomplete, but extract the last term
          response($.ui.autocomplete.filter(availableEncodingFunctions, extractLast(request.term)));
        },
        focus: function () {
          // prevent value inserted on focus
          return false;
        },
        select: function (event, ui) {
          var terms = split(this.value);
          // remove the current input
          terms.pop();
          // add the selected item
          terms.push(ui.item.value);
          // add placeholder to get the comma-and-space at the end
          terms.push("");
          this.value = terms.join(", ");
          return false;
        }
      }
    );
  }

  return {
    initialize: function (options) {
      options = options || {};

      shortcodeSettingTemplate = options.template("sttr-shortcode-setting");
      shortcodeAttributeTemplate = options.template("sttr-shortcode-attribute");

      var $shortcodeSettings = $("#shortcode-settings");

      $.each(savedShortcodes, function(name, shortcode){
        addShortcodeSetting($shortcodeSettings, name, shortcode.content_encoding);
        var $container = $shortcodeSettings.find('.shortcode-setting-container:last .shortcode-setting-attributes');
        var shortcodeIndex = $shortcodeSettings.data('lastIndex');

        $.each(shortcode.attributes, function(index, attribute){
          addAttributeInput($container, shortcodeIndex, attribute.name, attribute.encoding);
        });
      });
      
      $('#shortcodesSettingsForm .shortcode-add-setting')
        .click(function(){
          addShortcodeSetting($shortcodeSettings);
        });
    }
  };
})(jQuery);

Supertext.Settings.Workflow = (function ($) {
  'use strict';

  var
    $apiUrl,
    $apiSelection;

  function updateAlternativeApiUrlInput(){
    $apiUrl.val($apiSelection.val());

    if($apiSelection.children('option:last').is(':selected')){
      $apiUrl.removeProp('readonly');
    }else{
      $apiUrl.prop('readonly', true);
    }
  }

  return {
    initialize: function (options) {
      options = options || {};

      $apiUrl = $('#sttr-api-url');
      $apiSelection = $('#sttr-api-selection');

      $apiSelection.change(updateAlternativeApiUrlInput);

      updateAlternativeApiUrlInput();
    }
  };
})(jQuery);

jQuery(document).ready(function () {
  //get active tab
  var queryString = window.location.search;
  var tab = /tab=(.*?)(&|$|\s)/.exec(queryString);
  var tabName = tab === null ? 'users' : tab[1];

  //initialize tab module
  switch (tabName) {
    case 'users':
      Supertext.Settings.Users.initialize();
      break;

    case 'translatablefields':
      Supertext.Settings.TranslatableFields.initialize();
      break;

    case 'shortcodes':
      Supertext.Settings.Shortcodes.initialize({ template: wp.template});
      break;

    case 'workflow':
    Supertext.Settings.Workflow.initialize();
    break;
  }
});