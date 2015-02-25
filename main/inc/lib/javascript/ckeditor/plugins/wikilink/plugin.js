CKEDITOR.plugins.add('wikilink',
{
    init: function(editor)
    {
        var pluginName = 'wikilink';
        CKEDITOR.dialog.add(pluginName, this.path + 'dialogs/wikilink.js');

        editor.addCommand(pluginName, new CKEDITOR.dialogCommand(pluginName));
        editor.ui.addButton('Wikilink',
            {
                label: 'Wikilink',
                command: pluginName,
                icon : this.path + 'images/wikilink.gif'
            });
    }
});
