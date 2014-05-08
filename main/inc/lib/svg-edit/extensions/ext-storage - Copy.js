/*globals svgEditor, Editor, svgCanvas, $, widget*/
/*jslint vars: true, eqeq: true, regexp: true, continue: true*/
/*
 * ext-storage.js
 *
 * Licensed under the MIT License
 *
 * Copyright(c) 2010 Brett Zamir
 *
 */
/**
* This extension allows automatic saving of the SVG canvas contents upon
*  page unload and and automatic retrieval of the contents upon editor load.
*  The functionality was originally part of the SVG Editor, but moved to a
*  separate extension to make the behavior optional, and adapted to inform
*  the user of its setting of local data.
*/

/*
TODOS
1. Revisit on whether to use $.pref over directly setting curConfig in all
	extensions for a more public API (not only for extPath and imagePath,
	but other currently used config in the extensions)
*/
svgEditor.addExtension('storage', function() { 'use strict';
	// We could empty any already-set data for them, but it
	// would be a risk for users who wanted to store but accidentally
	// said "no"; instead, we'll let those who already set it, delete it themselves;
	// to change, change the following line to true.
	var emptyStorageOnDecline = false;

	function replaceStoragePrompt (val) {
		val = val ? 'storagePrompt=' + val : '';
		if (window.location.href.indexOf('storagePrompt=') > -1) {
			window.location.href = window.location.href.replace(/([&?])storagePrompt=[^&]*/, (val ? '$1' : '') + val);
		}
		else {
			window.location.href += (window.location.href.indexOf('?') > -1 ? '&' : '?') + val;
		}
	}
	function setSVGContentStorage (val) {
		if (window.localStorage) {
			var name = 'svgedit-' + svgEditor.curConfig.canvasName;
			window.localStorage.setItem(name, val);
		}
	}
	function emptyLocalStorage() {
		document.cookie = 'store=';
		setSVGContentStorage('');
	}
	
	//emptyLocalStorage();

	/**
	* Listen for unloading: If and only if opted in by the user, set the content
	*   document and preferences into storage:
	* 1. Prevent save warnings (since we're automatically saving unsaved
	*       content into storage)
	* 2. Use localStorage to set SVG contents (potentially too large to allow in cookies)
	* 3. Use localStorage (where available) or cookies to set preferences.
	*/
	function setupBeforeUnloadListener () {
		window.addEventListener('beforeunload', function(e) {
			// Don't save anything unless the user opted in to storage
			if (!document.cookie.match(/store=(?:prefsAndContent|prefsOnly)/)) {
				return;
			}
			var key;
			setSVGContentStorage(svgCanvas.getSvgString());

			svgEditor.showSaveWarning = false;

			var curConfig = svgEditor.curConfig;
			for (key in curConfig) {
				if (curConfig.hasOwnProperty(key)) { // It's our own config, so we don't need to iterate up the prototype chain
					var d,
						storage = svgEditor.storage,
						val = curConfig[key],
						store = (val != undefined);
					key = 'svg-edit-' + key;
					
					if (!store) {
						continue;
					}
					if (storage) {
						storage.setItem(key, val);
					}
					else if (window.widget) {
						widget.setPreferenceForKey(val, key);
					}
					else {
						d = new Date();
						d.setTime(d.getTime() + 31536000000);
						val = encodeURIComponent(val);
						document.cookie = key + '=' + val + '; expires=' + d.toUTCString();
					}
				}
			}
		}, false);
	}

	var userStoragePref = 'noPrefsOrContent';

		/*
		// We could add locales here instead (and also thereby avoid the need
		// to keep our content within "langReady"), but this would be less
		// convenient for translators.
		$.extend(uiStrings, {confirmSetStorage: {
			message: "By default and where supported, SVG-Edit can store your editor "+
			"preferences and SVG content locally on your machine so you do not "+
			"need to add these back each time you load SVG-Edit. If, for privacy "+
			"reasons, you do not wish to store this information on your machine, "+
			"you can change away from the default option below.",
			storagePrefsAndContent: "Store preferences and SVG content locally",
			storagePrefsOnly: "Only store preferences locally",
			storagePrefs: "Store preferences locally",
			storageNoPrefsOrContent: "Do not store my preferences or SVG content locally",
			storageNoPrefs: "Do not store my preferences locally",
			rememberLabel: "Remember this choice?",
			rememberTooltip: "If you choose to opt out of storage while remembering this choice, the URL will change so as to avoid asking again."
		}});
		*/
	
	return {
		name: 'storage',
		langReady: function (lang, uiStrings) {
			var storagePrompt = $.deparam.querystring(true).storagePrompt;
			if (
				// If explicitly set to always prompt (e.g., so a user can alter
				//   their settings)...
				storagePrompt === true ||
				// ...or...the user hasn't visited page preventing storage prompt (for
				// users who don't want to set cookies at all but don't want
				// continual prompts)...
				(storagePrompt !== false &&
					// ...and user hasn't already indicated a desire for storage
					!document.cookie.match(/store=(?:prefsAndContent|prefsOnly)/)
				)
				// ...then show the storage prompt.
			) {
				var options = [];
				if (window.localStorage) {
					options.unshift(
						{value: 'prefsAndContent', text: uiStrings.confirmSetStorage.storagePrefsAndContent},
						{value: 'prefsOnly', text: uiStrings.confirmSetStorage.storagePrefsOnly},
						{value: 'noPrefsOrContent', text: uiStrings.confirmSetStorage.storageNoPrefsOrContent}
					);
				}
				else {
					options.unshift(
						{value: 'prefsOnly', text: uiStrings.confirmSetStorage.storagePrefs},
						{value: 'noPrefsOrContent', text: uiStrings.confirmSetStorage.storageNoPrefs}
					);
				}

				// Hack to temporarily provide a wide and high enough dialog
				var oldContainerWidth = $('#dialog_container')[0].style.width,
					oldContainerMarginLeft = $('#dialog_container')[0].style.marginLeft,
					oldContentHeight = $('#dialog_content')[0].style.height,
					oldContainerHeight = $('#dialog_container')[0].style.height;
				$('#dialog_content')[0].style.height = '120px';
				$('#dialog_container')[0].style.height = '170px';
				$('#dialog_container')[0].style.width = '800px';
				$('#dialog_container')[0].style.marginLeft = '-400px';

				// Open select-with-checkbox dialog
				$.select(
					uiStrings.confirmSetStorage.message,
					options,
					function (pref, checked) {
						// If the URL was configured to always insist on a prompt, if
						//    the user does indicate a wish to store their info, we
						//    don't want ask them again upon page refresh so move
						//    them instead to a URL which does not always prompt
						if (storagePrompt === true && pref && pref !== 'noPrefsOrContent') {
							replaceStoragePrompt();
							return;
						}
						if (pref && pref !== 'noPrefsOrContent') {
							// Regardless of whether the user opted
							// to remember the choice (and move to a URL which won't
							// ask them again), we have to assume the user
							// doesn't even want to remember their not wanting
							// storage, so we don't set the cookie or continue on with
							//  setting storage on beforeunload
							document.cookie = 'store=' + pref; // 'prefsAndContent' | 'prefsOnly'
						}
						else {
							if (emptyStorageOnDecline) {
								emptyLocalStorage();
							}
							if (checked) {
								// Open a URL which won't set storage and won't prompt user about storage
								replaceStoragePrompt('false');
							}
						}
						
						// Store the preference in memory (not currently in use)
						userStoragePref = pref;
						// Reset width/height of dialog (e.g., for use by Export)
						$('#dialog_container')[0].style.width = oldContainerWidth;
						$('#dialog_container')[0].style.marginLeft = oldContainerMarginLeft;				
						$('#dialog_content')[0].style.height = oldContentHeight;
						$('#dialog_container')[0].style.height = oldContainerHeight;
						
						// It should be enough to (conditionally) add to storage on
						//   beforeunload, but if we wished to update immediately,
						//   we might wish to try setting:
						//       Editor.preventStorageAccessOnLoad = true;
						//   and then call:
						//       Editor.loadContentAndPrefs();
						setupBeforeUnloadListener();
						
						Editor.storagePromptClosed = true;
					},
					null,
					null,
					{
						label: uiStrings.confirmSetStorage.rememberLabel,
						checked: false,
						tooltip: uiStrings.confirmSetStorage.rememberTooltip
					}
				);
			}
			else {
				setupBeforeUnloadListener();
			}
		}
	};
});
