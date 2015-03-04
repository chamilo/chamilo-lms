CKEDITOR.plugins.add('glossary',
{
    init: function(editor)
    {
        var pluginName = 'glossary';
        editor.addCommand(
            pluginName,
            {
                exec: function(editor)
                {
                    var selectedText = editor.getSelection().getSelectedText();
                    if (selectedText !== '') {
                        var spanElement = new CKEDITOR.dom.element("span");
                        spanElement.setAttributes({
                            class: 'glossary',
                            style: 'color: rgb(0, 151, 74);' +
                            'cursor: pointer;' +
                            'font-weight: bold;'
                        });
                        spanElement.setText(selectedText);
                        editor.insertElement(spanElement);
                    }
                }
            }
        );
        editor.ui.addButton(
            'Glossary',
            {
                label: 'Glossary',
                command: pluginName,
                icon: this.path + 'images/glossary.gif'
            }
        );
    }
});