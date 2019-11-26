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

CKEDITOR.dialog.add('inserthtmlDialog',function(editor){
	return{
		title:'Insert HTML',
		minWidth:380,
		minHeight:220,
		contents:[
			{	id:'info',
				label:'HTML',
				elements:[
				  { type:'textarea',
				    id:'insertcode_area',
					label:''
				  }
				]
			}
		],
		onOk: function() {
			var sInsert=this.getValueOf('info','insertcode_area');
			if ( sInsert.length > 0 )
			editor.insertHtml(sInsert);
		}
	};
});