var oEditor		= window.parent.InnerDialogLoaded() ;
var FCK			= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;


window.parent.AddTab( 'Upload', FCKLang.DlgLnkUpload ) ;


window.onload = function()
{
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	//FCK.InsertHtml('testing') ;
	//FCK.SetInnerHtml('<b>hello</b>');	
	//alert(FCK.EditorDocument.body.innerHTML ) ;
	//alert(window.top.document.body.innerHTML) ;
	//alert(window.parent.document.frameElement) ;
	//alert(FCK.LinkedField.value);
	//alert(FCK.LinkedField.form.action);
	//alert(FCK.GetXHTML( FCKConfig.FormatOutput ));
	//alert(FCK.Name);	
	//alert(top.window.parent.document.body.outerHTML);

	

		/*var oHiddenForm = FCK.LinkedField.form;
		for(i=0;i<oHiddenForm.elements.length;i++){
			alert(oHiddenForm.elements[i].value)
		}
		var oHidden = top.window.opener.createElement("input");
			oHidden.type = "hidden";
			oHidden.name = "filename";
			oHidden.value = "test123.doc";
			oHiddenForm.appendChild(oHidden);
		*/
}

