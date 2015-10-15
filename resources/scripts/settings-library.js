/**
 * Sorry for this mess :-( we reused this from blogwerk
 * @author Michael Hadorn <michael.hadorn@blogwerk.com> (initial library)
 * @author Michael Sebel <michael@comotive.ch> (refactoring)
 */

Supertext = Supertext || {};

Supertext.Settings = {};

Supertext.Settings.Users = (function ($) {
  var $tableBody,
      $rowTemplate;

  function addUserField() {
    var $newRow = $rowTemplate.clone();
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
      $rowTemplate.find('.remove-user-button').click(removeUserField);

      $('#btnAddUser').click(addUserField);
    }
  }
})(jQuery);

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

Supertext.Settings.Shortcodes = (function ($) {

  return {
    initialize: function (options) {
      options = options || {};

    }
  }
})(jQuery);


jQuery(document).ready(function () {
  var queryString = window.location.search;
  var tab = /tab=(.*?)(&|$|\s)/.exec(queryString);
  var tabName = tab === null ? 'users' : tab[1];

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