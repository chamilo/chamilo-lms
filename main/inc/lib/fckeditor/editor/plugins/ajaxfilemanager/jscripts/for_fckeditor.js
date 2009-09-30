// The function below has added by logan (cailongqun [at] yahoo [dot] com [dot] cn) from www.phpletter.com
// Modifications by Juan Carlos Raña and Ivan Tcholakov, 2009

function selectFile(url) {
	var selectedFileRowNum = $('#selectedFileRowNum').val();
	if(selectedFileRowNum != '' && $('#row' + selectedFileRowNum)) {
		if ( window.opener ) {
			// The advanced file manager has been opened in a new window.
			window.opener.SetUrl( url ) ;
			window.close() ;
		} else  if ( window.parent && typeof (window.parent.ParentDialog) == 'function' ) {
			// The file manager is inside a dialog.
			window.parent.ParentDialog().contentWindow.frames['frmMain'].SetUrl( url ) ;
			window.parent.CloseDialog() ;
		}
	} else {
		alert(noFileSelected);
	}
}

function cancelSelectFile() {
	window.close() ;
}
