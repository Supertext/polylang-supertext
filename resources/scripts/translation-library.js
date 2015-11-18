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
	 * This can be set to true in order to see a confirmation before purchasing
	 */
	NEED_CONFIRMATION : false,

	/**
	 * Template for rows between translations
	 */
	rowTemplate : '\
		<tr>\
		  <td>&nbsp;</td>\
		  <td><img src="' + Supertext.i18n.resourceUrl + '/wp-content/plugins/polylang-supertext/resources/images/arrow-right.png" style="width:16px; padding-left:5px;"/></td>\
		  <td colspan="2"><a href="{translationLink}">&nbsp;' + Supertext.i18n.offerTranslation + '</a></td>\
		</tr>\
  ',
	errorTemplate: '<h2 class="error-title">{title}</h2><p class="error-message">{message}<br/>({details})</p>',

	/**
	 * Loading the module on post or page
	 */
	initialize : function()
	{
		if (jQuery('#post-translations').length == 1 && Supertext.Polylang.isWorking()) {
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
		});

		// Create order on form submit
		jQuery('#frm_Translation_Options').submit(function() {
			return Supertext.Polylang.createOrder();
		});
	},

	/**
	 * Tells if the plugin is working (Configuration and if user has rights)
	 * @returns true, if the plugin is working by config/userconfig
	 */
	isWorking : function()
	{
		return (jQuery('#supertextPolylangWorking').val() == 1);
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

			// Provide link in any case now
			if (true/*langInput.val() == 0*/) {
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
		var tbLink = Supertext.i18n.resourceUrl + Supertext.Polylang.offerUrl + params;
		return 'javascript:Supertext.Polylang.checkBeforeTranslating(\'' + tbLink + '\');';
	},

	/**
	 * Disable a post that is in translation
	 */
	disableTranslatingPost : function()
	{
		// Set all default fields to readonly
		jQuery('#post input, #post select, #post textarea').each(function() {
			// If the value contains the in translation text, lock fields
			if (jQuery(this).val().indexOf(Supertext.i18n.inTranslationText) > -1) {
				jQuery(this).attr('readonly', 'readonly');
				jQuery(this).addClass('input-disabled');
				post_is_in_translation = true;
			}
		});

		// Kings discipline: disable the editor
		if (Supertext.Polylang.translationEnabledOnElement(jQuery('#content'))) {
			// Hide the editor
			jQuery('#wp-content-editor-container').addClass('input-disabled')
			jQuery('#content').hide();
			// Also, hide editor toggle
			jQuery('#post-status-info, #content-html, #content-tmce, .mce-tinymce').hide();
			// Print informational text
			jQuery('#wp-content-editor-container').html(
				'<div style="margin:10px;">' + Supertext.i18n.inTranslationText + '</div>' +
				jQuery('#wp-content-editor-container').html()
			);
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
			if (element.val().indexOf(Supertext.i18n.inTranslationText) > -1) {
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
			// if the value is the translation text, lock field
			if (jQuery(this).val().indexOf(Supertext.i18n.inTranslationText) > -1) {
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
		var postData = jQuery('#frm_Translation_Options').serialize()
			+ '&requestCounter=' + Supertext.Polylang.requestCounter;

		jQuery.post(
			Supertext.i18n.resourceUrl + Supertext.Polylang.ajaxUrl + '?action=getOffer',
			postData
		).done(
			function(data) {
				// handle only newest request
				if (data.body.optional.requestCounter == Supertext.Polylang.requestCounter) {
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
							jQuery('#div_translation_price').html(Supertext.i18n.generalError).addClass("error-message");
							Supertext.Polylang.handleElementVisibility(false, false);
							break;
					}
				}
			}
		).fail(
			function() {
				jQuery('#div_translation_price').html(Supertext.i18n.generalError).addClass("error-message");
				Supertext.Polylang.handleElementVisibility(false, false);
			}
		);
	},

	/**
	 * Create an actual translation order for supertext
	 * @returns bool false (always, to prevent native submit)
	 */
	createOrder : function ()
	{
  	// wird nur einmal ausgelöst
		if (!Supertext.Polylang.createOrderRunning) {
			Supertext.Polylang.createOrderRunning = true;

			// Oppan hadorn style
			var radio = jQuery('input:radio:checked[name=rad_translation_type]');
			var deadline = radio.parent().next().next().html().trim();
			var price = radio.parent().next().next().next().html().trim();

			// is there a need to confirm?
			var hasConfirmed = true;
			if (Supertext.Polylang.NEED_CONFIRMATION) {
				hasConfirmed = confirm(Supertext.Polylang.getOfferConfirmMessage(deadline, price))
			}

			// If the user confirmed, actually create the order
			if (hasConfirmed) {
				jQuery('#frm_Translation_Options').hide();
				jQuery('#warning_draft_state').hide();
				jQuery('#warning_already_translated').hide();

				jQuery('#div_waiting_while_loading').show();

				var offerForm = jQuery('#frm_Translation_Options');
				var postData = offerForm.serialize();

				// Post to API Endpoint and create order
				jQuery.post(
					Supertext.i18n.resourceUrl + Supertext.Polylang.ajaxUrl + '?action=createOrder',
					postData
				).done(
					function(data) {
						jQuery('#div_waiting_while_loading').hide();
						switch (data.head.status) {
							case 'success':
								jQuery('#div_translation_order_content').html(data.body.html);
								break;
							default: // error
								jQuery('#div_translation_order_head').hide();
								jQuery('#div_translation_order_content').html(
									Supertext.Polylang.errorTemplate
										.replace('{title}', Supertext.i18n.generalError)
										.replace('{message}', Supertext.i18n.translationOrderError)
										.replace('{details}', data.body.reason)
								);
								break;
						}

						//set window close button, top right 'x'
						jQuery(self.parent.document).find('#TB_closeWindowButton').click(function(){
							self.parent.location.reload();
						});

						jQuery('#btn_close_translation_order_window').click(function(){
							self.parent.location.reload();
						});
						jQuery('#div_close_translation_order_window').show();

						Supertext.Polylang.createOrderRunning = false;
					}
				).fail(
					function(jqXHR, textStatus, errorThrown){
						jQuery('#div_waiting_while_loading').hide();
						jQuery('#div_translation_order_head').hide();
						jQuery('#div_translation_order_content').html(
							Supertext.Polylang.errorTemplate
								.replace('{title}', Supertext.i18n.generalError)
								.replace('{message}', Supertext.i18n.translationOrderError)
								.replace('{details}', errorThrown + ": " + jqXHR.responseText)
						);
					}
				);

			} else {
				Supertext.Polylang.createOrderRunning = false;
			}
		}

		// disable native form submit
		return false;
	},

	/**
	 * Create the internationalized and parametrized confirm message for ordering
	 * @param deadline deadline date / time
	 * @param price the price
	 * @param currency the currency
	 */
	getOfferConfirmMessage : function(deadline, price)
	{
		// First, create the templated message
		var message = '' +
			Supertext.i18n.offerConfirm_Price + '\n' +
      Supertext.i18n.offerConfirm_Binding + '\n\n' +
      Supertext.i18n.offerConfirm_EmailInfo + '\n\n' +
      Supertext.i18n.offerConfirm_Confirm;

		// Replace all vars
		message = message.replace('{deadline}', deadline);
		message = message.replace('{price}', price);

		return message;
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
			if (typeof(fullscreen) !== 'undefined' && fullscreen.settings.visible) {
				title = jQuery('#wp-fullscreen-title').val();
				content = jQuery("#wp_mce_fullscreen").val();
			} else {
				title = jQuery('#post #title').val();
				content = jQuery('#post #content').val();
			}

			if (typeof(autosaveLast) !== 'undefined') {
				if ((title || content) && title + content != autosaveLast) {
					bUnsaved = true;
				}
			}
		}
		return bUnsaved;
	}
};

// Load on load. yass.
jQuery(function() {
	Supertext.Polylang.initialize();
});
