CKEDITOR.plugins.add('asciisvg', {
    lang: 'en,es',
    icons: 'asciisvg',
    init: function(editor) {
        $.getScript(this.path+"ASCIIsvgPI.js");
        var pluginName = 'asciisvg';
        CKEDITOR.dialog.add( pluginName, this.path + 'dialogs/mathjax.js' );
        editor.addCommand(pluginName, new CKEDITOR.dialogCommand(pluginName));
        editor.ui.addButton('Asciisvg',
        {
            label: 'Asciisvg',
            command: pluginName,
            icon : this.path+'icons/'+pluginName+'.png'
        });
    }
} );
