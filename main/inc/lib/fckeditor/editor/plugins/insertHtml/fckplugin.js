/*
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 *
 * File Name: fckplugin.js
 * 	Plugin to add some HTML, a single snippet; a choice from multiple snippets; or manually entered HTML
 *
 * File Authors:
 * 		Paul Moers (http://www.saulmade.nl/FCKeditor/FCKPlugins.php)
 */

    // insertHtmlObject constructor
    var insertHtmlToolbarCommand = function()
    {
    }

    // register the command
    FCKCommands.RegisterCommand('insertHtml', new insertHtmlToolbarCommand());

    // create the toolbar  button
    var insertHtmlButton = new FCKToolbarButton('insertHtml', FCKConfig.insertHtml_buttonTooltip || FCKLang.inserHTML_buttonTooltip);
    insertHtmlButton.IconPath = FCKPlugins.Items['insertHtml'].Path + 'images/toolbarIcon_default.gif'; // or pick any other in folder 'images'
    FCKToolbarItems.RegisterItem('insertHtml', insertHtmlButton);

    // manage the plugins' button behavior
    insertHtmlToolbarCommand.prototype.GetState = function()
    {
        return FCK_TRISTATE_OFF;
    }

    // insertHtml's button click function
    insertHtmlToolbarCommand.prototype.Execute = function()
    {
        if (FCKConfig.insertHtml_showDialog || !FCKConfig.insertHtml_snippets || (FCKConfig.insertHtml_snippets && !FCKConfig.insertHtml_snippets.length))
        {
            var dialog = new FCKDialogCommand('insertHtml', FCKLang.insertHtml_dialogTitle, FCKPlugins.Items['insertHtml'].Path + 'insertHtml.html', FCKConfig.insertHtml_dialogWidth || 475, FCKConfig.insertHtml_dialogHeight || 475);
            dialog.Execute();
        }
        else
        {
            FCK.InsertHtml(FCKConfig.insertHtml_snippet);
            FCK.EditorWindow.parent.FCKUndo.SaveUndoStep();
        }
    }
