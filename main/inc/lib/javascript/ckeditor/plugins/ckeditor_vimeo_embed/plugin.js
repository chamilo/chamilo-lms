/* For licensing terms, see /LICENSE */

(function () {
    CKEDITOR.plugins.add('ckeditor_vimeo_embed', {
        lang: ['en', 'es'],
        icons: 'VimeoEmbed',
        init: function (editor) {
            editor.addCommand(
                'vimeoEmbed',
                new CKEDITOR.dialogCommand('vimeoEmbedDialog')
            );

            editor.ui.addButton('VimeoEmbed', {
                label: 'Embed a Vimeo video',
                command: 'vimeoEmbed',
                toolbar: 'insert,0'
            });

            CKEDITOR.dialog.add('vimeoEmbedDialog', this.path + 'dialogs/vimeo_embed.js');
        }
    });
})();
