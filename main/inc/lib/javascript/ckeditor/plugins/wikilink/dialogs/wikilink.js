( function(){

    var wikilinkDialog = function(editor){
        return {
            title : "Wikilink",
            minWidth : 100,
            minHeight : 50,
            buttons:[CKEDITOR.dialog.okButton, CKEDITOR.dialog.cancelButton],
            onOk: function()
            {
                var data = {};
                this.commitContent(data);
                editor.insertText('[['+data.wikiLinkText+']]');
            },
            contents:
            [
                {
                    id : 'general',
                    label : 'Settings',
                    elements :
                    [
                        {
                            type : 'text',
                            id : 'wikiLinkText',
                            label : 'TIP: You can also create a wiki link placing between double brackets [[]] a word',
                            validate : CKEDITOR.dialog.validate.notEmpty( 'The wikilink field cannot be empty.' ),
                            required : true,
                            commit : function(data)
                            {
                                data.wikiLinkText = this.getValue();
                            }
                        }
                    ]
                }
            ]
        }
    }

    CKEDITOR.dialog.add('wikilink', function(editor) {
        return wikilinkDialog(editor);
    });

})();