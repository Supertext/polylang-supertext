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

	/**
	 * Loading the module on post or page
	 */
	initialize : function()
	{
		if (jQuery('#post-translations').length == 1 && Supertext.Polylang.isWorking()) {
			Supertext.Polylang.injectOfferLinks();
		}

		// Auto save a newly done translation by "pressing save"
		var translationParam = location.search.split('translation-service=')[1];
		if (typeof(translationParam) != 'undefined' && translationParam == 1) {
			Supertext.Polylang.autoSavePost();
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
			var form = jQuery(this);
			var postId = form.data('post-id');
			var successUrl = jQuery('#successUrlMakeOrder').val();
			return Supertext.Polylang.createOrder(postId, successUrl);
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
	 * Automatically saves a post on load. Used for the newly created translated page
	 */
	autoSavePost : function()
	{
		// Tell the user something happens
		jQuery('body').css('cursor', 'progress');
		// Show an info to the user
		var element = jQuery('#poststuff');
		element.css('display', 'none');
		element.after('<div class="updated"><p>' + Supertext.i18n.translationCreation + '</p></div>');

		// After a second, auto save to permanently save the post to be translated
		setTimeout(function() {
			jQuery('#save-action input[type=submit]').trigger('click');
			jQuery('body').css('cursor', 'default');
		}, 1000);
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
			if (jQuery(this).val() == Supertext.i18n.inTranslationText) {
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
			if (element.val().trim() == Supertext.i18n.inTranslationText || element.val().trim() == '<p>' + Supertext.i18n.inTranslationText + '</p>') {
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
			if (jQuery(this).val() == Supertext.i18n.inTranslationText) {
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
			+ '&requestCounter=' + Supertext.Polylang.requestCounter;
		jQuery.post(
			Supertext.i18n.resourceUrl + Supertext.Polylang.ajaxUrl + '?action=getOffer',
			postData,
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
							jQuery('#div_translation_price').html(Supertext.i18n.generalError);
							break;
					}
				}
			}
		);
	},

	/**
	 * Create an actual translation order for supertext
	 * @param postId the post id (original)
	 * @param successUrl the success url to post to
	 * @returns bool false (always, to prevent native submit)
	 */
	createOrder : function (postId, successUrl)
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
				// Hide review state, if available
				if (!jQuery('#warning_not_review_state').length == 0) {
					jQuery('#warning_not_review_state').hide();
				}
				jQuery('#div_waiting_while_loading').show();

				var offerForm = jQuery('#frm_Translation_Options');
				var postData = offerForm.serialize() + '&post_id=' + postId;

				// Post to API Endpoint and create order
				jQuery.post(
					Supertext.i18n.resourceUrl + Supertext.Polylang.ajaxUrl + '?action=createOrder',
					postData,
					function(data) {
						jQuery('#div_waiting_while_loading').hide();
						switch (data.head.status) {
							case 'success':
								// Show info to user
								jQuery('#div_tb_wrap_translation').append(data.body.html);
								Supertext.Polylang.createOrderRunning = false;

								// do the rest in a few seconds
								setTimeout(function() {
									// Copy form to parent
									parent.jQuery('body').append(offerForm.clone());
									offerForm = parent.jQuery('#frm_Translation_Options');

									// POST to success page that will create the empty post and connect it
									offerForm.attr('action', successUrl);
									// Remove submit validation and send
									offerForm.attr('onsubmit', '');
									offerForm.submit();

									// Also, close thickbox (but the form response will anyway)
									self.parent.tb_remove();
								}, 3000);

								break;
							default: // error
								jQuery('#div_tb_wrap_translation').html(
									'<h2>' + Supertext.i18n.generalError + '</h2>' +
									Supertext.i18n.translationOrderError
								);
								break;
						}
					},
					'json'
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
