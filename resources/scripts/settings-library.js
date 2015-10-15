/**
 * Sorry for this mess :-( we reused this from blogwerk
 * @author Michael Hadorn <michael.hadorn@blogwerk.com> (initial library)
 * @author Michael Sebel <michael@comotive.ch> (refactoring)
 */

Supertext = Supertext || {};

Supertext.Settings = {};

//Users tab module
Supertext.Settings.Users = (function ($) {
  var $tableBody,
      $rowTemplate;

  function addUserField() {
    var $newRow = $rowTemplate.clone();
    $newRow.find('.remove-user-button').click(removeUserField);
    $tableBody.append($newRow);
  }

  function removeUserField() {
    $(this).parent('td').parent('tr').remove();

    if($tableBody.find('tr').length === 0){
      addUserField();
    }
  }

  return {
    initialize: function (options) {
      options = options || {};

      $tableBody = $("#tblStFields tbody")
      $tableBody.find('tr .saved-user-id-hidden')
      $tableBody.find('tr .remove-user-button').click(removeUserField);
      //select users in wp dropdown
      $tableBody.find('tr .saved-user-id-hidden').each(function(){
        var $this = $(this);
        $this.prev().val($this.val());
      });

      $rowTemplate = $('#tblStFields tr:last').clone();
      $rowTemplate.find('input').val('');

      $('#btnAddUser').click(addUserField);
    }
  }
})(jQuery);

//Custom Fields tab module
Supertext.Settings.CustomFields = (function ($) {
  var $customFieldsTree,
    $checkedCustomFieldIdsInput;

  function setCheckedCustomFields() {
    var checkedNodes = $customFieldsTree.jstree("get_checked", false);
    $checkedCustomFieldIdsInput.val(checkedNodes.join(','));
  }

  return {
    initialize: function (options) {
      options = options || {};

      var preselectedNodeIds = options.preselectedNodeIds || [];

      $customFieldsTree = $('#customFieldsTree');
      $checkedCustomFieldIdsInput = $('#checkedCustomFieldIdsInput');

      $customFieldsTree
        .jstree({
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

      $customFieldsTree.jstree('select_node', preselectedNodeIds);

      $('#customfieldsSettingsForm').submit(setCheckedCustomFields);
    }
  }
})(jQuery);

//Shortcodes tab module
Supertext.Settings.Shortcodes = (function ($) {

  function onCheckboxChanged(){
    $(this).parent('td').next().next().children('input').css('visibility', this.checked ? 'visible' : 'hidden');
  }

  return {
    initialize: function (options) {
      options = options || {};

      $('#tblShortcodes tbody input[type=checkbox]').on('change', onCheckboxChanged).each(function(){
        onCheckboxChanged.call(this);
      });

    }
  }
})(jQuery);


jQuery(document).ready(function () {
  //get active tab
  var queryString = window.location.search;
  var tab = /tab=(.*?)(&|$|\s)/.exec(queryString);
  var tabName = tab === null ? 'users' : tab[1];

  //initialize tab module
  switch(tabName){
    case 'users':
      Supertext.Settings.Users.initialize();
      break;

    case 'customfields':
      Supertext.Settings.CustomFields.initialize(
        {
          preselectedNodeIds: savedCustomFieldIds
        }
      );
      break;

    case 'shortcodes':
      Supertext.Settings.Shortcodes.initialize();
      break;
  }
});