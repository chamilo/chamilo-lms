//function below added by logan (cailongqun [at] yahoo [dot] com [dot] cn) from www.phpletter.com

function selectFile(url)
{
	var selectedFileRowNum = $('#selectedFileRowNum').val();
  if(selectedFileRowNum != '' && $('#row' + selectedFileRowNum))
  {
	  // insert information now
	 // var url = $('#fileUrl'+selectedFileRowNum).val();  	//comment and replaced for  put url into selecFile(url) by Juan Carlos Raña
		window.opener.SetUrl( url ) ;
		window.close() ;
		
  }else
  {
  	alert(noFileSelected);
  }
  

}



function cancelSelectFile()
{
  // close popup window
  window.close() ;
}



/*

// Alternative configuration. Juan Carlos Raña

function selectFile()
{
	//juan carlos raña quizá si metemos aquí un while metería todos los marcados y no solo el ultimo?, así se recogerían selecciones múltiples
  var selectedFileRowNum = getNum($('input[@type=checkbox][@checked]').attr('id'));
  if(selectedFileRowNum != '' && $('#row' + selectedFileRowNum))
  {
     // insert information now
     var url = files[selectedFileRowNum]['url'];
      window.opener.SetUrl(url) ;
      window.close() ;
      
  }else
  {
     alert(noFileSelected);
  }
}

function cancelSelectFile()
{
  // close popup window
  window.close() ;
}

*/