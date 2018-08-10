/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

/**
 * @fileOverview  Plugin that changes the toolbar and maximizes the editor 
 *                for the big toolbar.
 * 
 *                You need a custom config to define the small and big toolbars.
 *                Also the maximize plug-in is needed but not the maximize button.
 *                For this plugin you should use the 'Toolbarswitch' button instead.
 * 
 *                CKEDITOR.replace('sometextcomponentname', {
 *               		customConfig: '/...custom_ckeditor_config.js'
 *               		toolbar: 'yoursmalltoolbarname', 
 *               		smallToolbar: 'yoursmalltoolbarname',
 *               		maximizedToolbar: 'yourbigtoolbarname' });
 *               
 *                Requires:
 *                - Maximize plugin. But not the button that goes with it. 
 *                - All toolbars used in the ckeditor instance have to use the 'Toolbarswitch' button instead.
 *                - A custom config to define the small and big toolbars.
 *                - function CKeditor_OnComplete(ckEditorInstance){ ... your own custom code or leave empty... }
 *                  This was added to the plugin for those that wrap the ckeditor in other java script to shield 
 *                  the rest of their code from ckeditor version particularities.
 *                - jQuery
 */


function switchMe(editor, callback) {
    var origToolbar =  editor.config.toolbar;
    var origSmallToolbar = editor.config.smallToolbar;
    var origMaximizedToolbar = editor.config.maximizedToolbar;
    var newToolbar = origToolbar == origSmallToolbar ? origMaximizedToolbar : origSmallToolbar;

	var origConfig = editor.config,
		newConfig = jQuery.extend(true, {}, origConfig);

    newConfig.toolbar = newToolbar;
    newConfig.on = {
        instanceReady: function(e) {
            if (typeof CKeditor_OnComplete !== 'undefined') {
                CKeditor_OnComplete(e.editor);
            }
            if (callback) {
                callback.call(null, e);
            }
        }
    };

	// Copy data to original text element before getting rid of the old editor
	var data = editor.getData();
	var domTextElement = editor.element.$;
	jQuery(domTextElement).val(data);
	
	// Remove old editor and the DOM elements, else you get two editors
	var id = domTextElement.id;
	editor.destroy(true);

	CKEDITOR.replace(id, newConfig);
}

CKEDITOR.plugins.add('toolbarswitch', {
	requires: [ 'button', 'toolbar', 'maximize' ],
	lang: 'af,ar,bg,bn,bs,ca,cs,cy,da,de,el,en,en-au,en-ca,en-gb,eo,es,et,eu,fa,fi,fo,fr,fr-ca,gl,gu,he,hi,hr,hu,id,is,it,ja,ka,km,ko,ku,lt,lv,mk,mn,ms,nb,nl,no,pl,pt,pt-br,ro,ru,si,sk,sl,sq,sr,sr-latn,sv,th,tr,ug,uk,vi,zh,zh-cn', // %REMOVE_LINE_CORE%
	icons: 'toolbarswitch', // %REMOVE_LINE_CORE%
	hidpi: true, // %REMOVE_LINE_CORE%
	init: function (editor) {
		var lang = editor.lang;
		var commandFunction = {
			exec: function( editor ) {
				if ( editor.config.toolbar == editor.config.maximizedToolbar ) {
					// For switching to the small toolbar first minimize
					editor.commands.maximize.exec();
					switchMe(editor, function(e){
						var newEditor = e.editor;
						newEditor.fire('triggerResize');
					});
				} else {
					switchMe(editor, function(e){
						var newEditor = e.editor;
						newEditor.commands.maximize.exec();
						newEditor.fire('triggerResize');
					});
				}
			}
		}

		var command = editor.addCommand( 'toolbarswitch', commandFunction );
		command.modes = { wysiwyg:1,source:1 };
		command.canUndo = false;
		command.readOnly = 1;

		editor.ui.addButton && editor.ui.addButton( 'Toolbarswitch', {
			label: lang.toolbarswitch.toolbarswitch,
			command: 'toolbarswitch',
			toolbar: 'tools,10'
		});
	}
});

