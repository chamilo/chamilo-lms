/*********************************************************************************************************/
/**
 * inserthtml plugin for CKEditor 4.x (Author: gpickin ; email: gpickin@gmail.com)
 * version:	2.0
 * Released: On 2015-03-10
 * Download: http://www.github.com/gpickin/ckeditor-inserthtml
 *
 *
 * Modified from original: inserthtml plugin for CKEditor 3.x (Author: Lajox ; Email: lajox@19www.com)
 * mod-version:	 1.0
 * mod-Released: On 2009-12-11
 * mod-Download: http://code.google.com/p/lajox
 */
/*********************************************************************************************************/
(function() {
    CKEDITOR.plugins.add('inserthtml',
      {
        init: function( editor ) {
            editor.addCommand( 'inserthtml', new CKEDITOR.dialogCommand( 'inserthtmlDialog' ) );
            editor.ui.addButton( 'inserthtml', {
                label: 'Insert HTML',
                command: 'inserthtml',
                toolbar: 'insert',
                icon : this.path + 'inserthtml.png'
            });

            CKEDITOR.dialog.add( 'inserthtmlDialog', this.path + 'dialogs/inserthtml.js' );
        }
    });
})();