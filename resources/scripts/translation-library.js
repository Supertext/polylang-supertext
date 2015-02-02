/**
 * Polylang translation plugin to inject translation options
 * @author Michael Sebel <michael@comotive.ch>
 */
Supertext.Polylang = {

	/**
	 * Tells if there is an ajax order being placed
	 */
	createOrderRunning : false,
	/**
	 * Ajax request counter
	 */
  requestCounter : 0,
	/**
	 * May be overridden to true on load
	 */
  inTranslation : false,
	/**
	 * The offer url to be loaded in thickbox
	 */
	offerUrl : '/wp-content/plugins/polylang-supertext/views/backend/offer.php',
	/**
	 * The very own ajax url (well, yes. legacy.)
	 */
	ajaxUrl : '/wp-content/plugins/polylang-supertext/resources/scripts/api/ajax.php',
	/**
	 * Set by the offerbox window
	 */
	translatedPostId : 0,

	/**
	 * Template for rows between translations
	 */
	rowTemplate : '\
		<tr>\
		  <td>&nbsp;</td>\
		  <td><img src="/wp-content/plugins/polylang-supertext/resources/images/arrow-right.png" style="width:16px; padding-left:5px;"/></td>\
		  <td colspan="2"><a href="{translationLink}">&nbsp;' + Supertext.i18n.offerTranslation + '</a></td>\
		</tr>\
  ',

	/**
	 * Loading the module on post or page
	 */
	initialize : function()
	{
		if (jQuery('#post-translations').length == 1) {
			Supertext.Polylang.injectOfferLinks();
		}

		// Lock the post if it is in translation
		window.setTimeout(function() {
			Supertext.Polylang.disableTranslatingPost();
		}, 2000);
	},

	/**
	 * Register events for the offer box. Also it fires the inital offer
	 */
	addOfferEvents : function()
	{
		// On load directly get an offer for default config
		Supertext.Polylang.getOffer();

		// Register to reload offer on checkbox click
		jQuery('.chkTranslationOptions').change(function() {
			Supertext.Polylang.getOffer();
		})
	},

	/**
	 * Injects an offer link for every not yet made translation
	 */
	injectOfferLinks : function()
	{
		jQuery('.pll-translation-column').each(function() {
			var translationCell = jQuery(this);
			var langInput = translationCell.find('input').first();
			var languageRow = translationCell.parent();

			// Provide link of not yet translated
			if (langInput.val() == 0) {
				var template = Supertext.Polylang.rowTemplate;
				var link = Supertext.Polylang.getTranslationLink(languageRow);
				template = template.replace('{translationLink}', link);
				languageRow.after(template);
			}
		});
	},

	/**
	 * Creates the translation link for the current post
	 * @param languageRow the language row, contains vital information
	 * @return string the link to translation (actually a JS call)
	 */
	getTranslationLink : function(languageRow)
	{
		var postId = jQuery('#post_ID').val();
		var languageId = languageRow.find('.tr_lang').attr('id');
		languageId = languageId.replace('tr_lang_', '');
		// Create params, link and call with a check function
		var params = '?postId=' + postId + '&targetLang=' + languageId + '&height=800&width=630&TB_iframe=true'
		var tbLink = Supertext.Polylang.offerUrl + params;
		return 'javascript:Supertext.Polylang.checkBeforeTranslating(\'' + tbLink + '\');';
	},

	/**
	 * Disable a post that is in translation
	 */
	disableTranslatingPost : function()
	{
		// Set all default fields to readonly
		jQuery('#post input, #post select, #post textarea').each(function() {
			// If the value contains "[in Translation...]", lock fields
			if (jQuery(this).val() == '[in Translation...]') {
				jQuery(this).attr('readonly', 'readonly');
				jQuery(this).addClass('input-disabled');
				post_is_in_translation = true;
			}
		});

		// Kings discipline: disable the editor
		if (Supertext.Polylang.translationEnabledOnElement(jQuery('#content'))) {
			// Hide the editor
			jQuery('#post-status-info').hide();
			tinyMCE.execCommand("mceRemoveControl", true, 'content');
			jQuery('#post-status-info').hide();
			jQuery('#wp-content-editor-container').addClass('input-disabled')
			jQuery('#content').hide();
			// Alos, hide editor toggle
			jQuery('#content-html, #content-tmce').hide();
			// Print informational text
			jQuery('#wp-content-editor-container').html(
				'<div style="margin:10px;">[in Translation...]</div>' +
				jQuery('#wp-content-editor-container').html()
			);

			// TODO remove other forms, if needed
		}
	},

	/**
	 * Checks if an elements value is currently in translation
	 * @param element the element to look at
	 * @returns {boolean} true, if the element contains a translation status
	 */
	translationEnabledOnElement : function(element)
	{
		if (element.length > 0) {
			if (element.val().trim() == '[in Translation...]' || element.val().trim() == '<p>[in Translation...]</p>') {
				Supertext.Polylang.inTranslation = true;
				return true;
			}
		}
		return false;
	},

	/**
	 * Disable gallery inputs as well, only if needed (called by php)
	 */
	disableGalleryInputs : function()
	{
		// var arr_ele_name, att_id, post_type;
		jQuery("#media-items input, #media-items select, #media-items textarea").each(function() {
			// Steht im Value "[in Translation...]" -> Feld Sperren
			if (jQuery(this).val() == "[in Translation...]") {
				jQuery(this).attr("readonly", "readonly");
				jQuery(this).addClass("input-disabled");
				post_is_in_translation = true;
			}
		});

		if (Supertext.Polylang.translationEnabledOnElement(self.parent.jQuery('#content'))) {
			jQuery("#insert-gallery, table.slidetoggle tbody tr td input[type=submit].button").each(function() {
				jQuery(this).attr('disabled', 'disabled');
				jQuery(this).addClass("input-disabled");
			});
		}
	},

	/**
	 * Checks translatability before calling the offerbox
	 * @param tbLink the offer box link that will be fired if everything is ok
	 */
	checkBeforeTranslating : function(tbLink)
	{
		var canTranslate = false;
		// Are there changes in fields or editor?
		if (!Supertext.Polylang.hasUnsavedChanges()) {
			canTranslate = true;
		} else {
			canTranslate = confirm(Supertext.i18n.confirmUnsavedArticle);
		}
		if (canTranslate) {
			// Can't translate a post in translation
			if (Supertext.Polylang.inTranslation) {
				alert(Supertext.i18n.alertUntranslatable)
			} else {
				// Open the offer box
				tb_show(Supertext.i18n.offerTranslation, tbLink, false);
			}
		}
	},

	/**
	 * Get an offer for a certian post
	 */
	getOffer : function()
	{
		Supertext.Polylang.handleElementVisibility(true, true);
		Supertext.Polylang.requestCounter++;
		var postId = Supertext.Polylang.translatedPostId;
		var postData = jQuery('#frm_Translation_Options').serialize()
			+ '&post_id=' + postId
			+ '&req_count=' + Supertext.Polylang.requestCounter;

		jQuery.post(
			Supertext.Polylang.ajaxUrl + '?action=getOffer',
			postData,
			function(data) {
				// handle only newest request (req_count)
				if (data.body.optional.req_count == Supertext.Polylang.requestCounter) {
					switch (data.head.status) {
						case 'success':
							jQuery('#div_translation_price').html(data.body.html);
							Supertext.Polylang.handleElementVisibility(false, true);
							break;
						case 'no_data':
							jQuery('#div_translation_price').html(data.body.html);
							Supertext.Polylang.handleElementVisibility(false, false);
							break;
						default: // error
							jQuery('#div_translation_price').html(Supertext.i18n.generalError);
							break;
					}
				}
			}
		);
	},

	/**
	 * Handles visibility of elements depending on state
	 * @param loading if somethings loading. oh crap i hate refactoring.
	 * @param orderAllowed if ordering is allowed (everything is filled out)
	 */
	handleElementVisibility : function (loading, orderAllowed)
	{
		if (loading) {
			jQuery('#div_translation_price_loading').show();
			jQuery('#div_translation_price').hide();
			jQuery('#btn_order').hide();
		} else {
			jQuery('#div_translation_price_loading').hide();
			jQuery('#div_translation_price').show();
			if (orderAllowed) {
				jQuery('#btn_order').show();
			}
		}
	},

	/**
	 * Checks if tinymce or title have unsaved changes
	 * @returns {boolean} true, if there are changes
	 */
	hasUnsavedChanges : function()
	{
		var bUnsaved = false;
		var mce = typeof(tinyMCE) != 'undefined' ? tinyMCE.activeEditor : false, title, content;

		if (mce && !mce.isHidden()) {
			if (mce.isDirty())
				bUnsaved = true;
		} else {
			if (fullscreen && fullscreen.settings.visible) {
				title = jQuery('#wp-fullscreen-title').val();
				content = jQuery("#wp_mce_fullscreen").val();
			} else {
				title = jQuery('#post #title').val();
				content = jQuery('#post #content').val();
			}

			if (( title || content ) && title + content != autosaveLast) {
				bUnsaved = true;
			}
		}
		return bUnsaved;
	}
};

// Load on load. yass.
jQuery(function() {
	Supertext.Polylang.initialize();
});













/**
 * TODO
 * TO BE REFACTORED
 */



function make_order(post_id, success_url) {
   // wird nur einmal ausgelöst
  if (!is_running_make_order) {
    is_running_make_order = true;

    /*
    deadline_date = parent.jQuery('#TB_iframeContent').contents().find('input:radio:checked[name=rad_translation_type]').parent().next().next().html().trim();
    price_val = parent.jQuery('#TB_iframeContent').contents().find('input:radio:checked[name=rad_translation_type]').parent().next().next().next().html().trim();
    */
    deadline_date = jQuery('input:radio:checked[name=rad_translation_type]').parent().next().next().html().trim();
    price_val = jQuery('input:radio:checked[name=rad_translation_type]').parent().next().next().next().html().trim();
    Check = confirm('\
Sie bestellen eine Übersetzung bis zum ' + deadline_date + ' Uhr zum Preis von CHF ' + price_val + '.\n\
Diese Übersetzungsbeauftragung ist verbindlich.\n\
\n\
Sie werden per E-Mail informiert, sobald die Übersetzung abgeschlossen wurde.\n\
\n\
Bitte bestätigen Sie die Bestellung mit "OK".'
  );
    if (Check) {
      jQuery('#frm_Translation_Options').hide();
      // falls review noch angezeigt wird -> ausblenden
      if (!jQuery('#warning_not_review_state').length == 0) {
        jQuery('#warning_not_review_state').hide();
      }
      jQuery('#div_waiting_while_loading').show();

      obj_form = jQuery('#frm_Translation_Options');
      post_data = obj_form.serialize()+'&post_id='+post_id;

      jQuery.post(
        '/wp-content/plugins/blogwerk/services/Supertext/ajax_handler.php?action=make_order',
        post_data,
        function(data) {
          jQuery('#div_waiting_while_loading').hide();
          switch (data.head.status) {
            case 'success':
              is_running_make_order = false;

              // das Formular in das parent kopieren
              parent.jQuery('body').append(obj_form.clone());
              obj_form = parent.jQuery('#frm_Translation_Options');

              // statt die Seite weiter zuleiten, daten mit posten -> um options zu behalten und neue Seite korrekt zu erstellen
              obj_form.attr('action', success_url);
              // submit entfernen
              obj_form.attr('onsubmit', '');
              // form endlich posten
              obj_form.submit();

              // Fenster schliessen
              self.parent.tb_remove();

              break;
            default: // error
              jQuery('#div_tb_wrap_translation').html('<h2>Tut uns Leid</h2>Es ist ein Fehler aufgetreten. Bitte kontaktieren Sie uns via <a href="mailto:support@blogwerk.com">support@blogwerk.com</a>.');
              break;
          }
        },
        'json'
      );
    }
    else {
      is_running_make_order = false;
    }
  }
  else {
    // alert('instance is already running');
  }
  // disable form submit
  return false;
}