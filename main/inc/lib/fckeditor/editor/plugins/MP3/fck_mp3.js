var oEditor = window.parent.InnerDialogLoaded() ;
var FCK		= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;
var mp3_url="";

// Set the language direction.
window.document.dir = oEditor.FCKLang.Dir ;

// Set the dialog tabs.
window.parent.AddTab( 'Upload', FCKLang.DlgMP3Upload ) ;

window.parent.AddTab( 'Info', FCKLang.DlgMP3Tab ) ;


function OnDialogTabChange( tabCode )
{
	ShowE('divInfo'		, ( tabCode == 'Info' ) ) ;
	ShowE('divUpload'	, ( tabCode == 'Upload' ) ) ;
	
}


var sAgent = navigator.userAgent.toLowerCase() ;

var is_ie = (sAgent.indexOf("msie") != -1); // FCKBrowserInfo.IsIE
var is_gecko = !is_ie; // FCKBrowserInfo.IsGecko

var oMedia = null;

function window_onload()
{
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	window.parent.SetSelectedTab( 'Upload' ) ;

	// Load the selected element information (if any).
	LoadSelection() ;

	// Show/Hide the "Browse Server" button.
	GetE('tdBrowse').style.display = FCKConfig.MP3Browser ? '' : 'none' ;

	// Set the actual uploader URL.
	if ( FCKConfig.MP3Upload ){
		GetE('frmUpload').action = FCKConfig.MP3UploadURL ;
	}

	window.parent.SetAutoSize( true ) ;

	// Activate the "OK" button.
	//window.parent.SetOkButton( true ) ;


}


/**
 * obtiene el elemento seleccionado
 */
function getSelectedMovie(){
	var oSel = null;

	// explorer..
	if (is_ie){
		oSel = FCK.Selection.GetSelectedElement( 'OBJECT' );
	}
	
	// gecko
	else if (is_gecko){
		var o = FCK.EditorWindow.getSelection() ;

		if ((o != null) && (o.anchorNode.tagName == 'OBJECT')){
			oSel = o.anchorNode;
		}
	}
	
	// other
	else {
		alert ("Browser Not Supported");
	}

	return oSel;
}


function LoadSelection()
{
	/*
	if ( ! oMedia ) return ;

	GetE('mpUrl').value    = GetAttribute( oMedia, 'data', '' ) ; //
	updatePreview() ;*/

	oMedia = new Media();
	oMedia.setObjectElement(getSelectedMovie());
	GetE('mpUrl').value    	= getObjUrl(oMedia.url);
	updatePreview();
}

//#### The OK button was hit.
function Ok()
{
	if ( GetE('mpUrl').value.length == 0 )
	{
		window.parent.SetSelectedTab( 'Info' ) ;
		GetE('mpUrl').focus() ;	
		alert( 'Please insert the URL.' ) ;
		return false ;
	}

	var e = (oMedia || new Media()) ;

	if(!is_ie){
		if ( !oMedia )
		{
			var oFakeImage  = null ;
		}

		if ( !oFakeImage )
		{
			oFakeImage	= oEditor.FCKDocumentProcessors_CreateFakeImage( 'FCK__MP3', e ) ;
			oFakeImage.setAttribute( '_fckmp3', 'true', 0 ) ;
			oFakeImage	= FCK.InsertElementAndGetIt( oFakeImage ) ;
		}
		else
			oEditor.FCKUndo.SaveUndoStep() ;
		
		oEditor.FCKFlashProcessor.RefreshView( oFakeImage, oMedia ) ;
	}

	updateMovie(e) ;

	FCK.InsertHtml(e.getOuterHTML()) ;


	return true ;
}

/**
 * Obtiene los datos del form y actualiza el objeto..
 */
function updateMovie(e){
	e.url = GetE('mpUrl').value;
}

var ePreview ;

function SetPreviewElement( previewEl )
{
	ePreview = previewEl ;
	
	if ( GetE('mpUrl').value.length > 0 )
		updatePreview() ;
}

function updatePreview(){
	if ( GetE('mpUrl').value.length == 0 ){
		return;
	}
	else {
		window.parent.SetSelectedTab( 'Info' ) ;

	}
}


function BrowseServer()
{
	// Set the browser window feature.
	var iWidth	= oEditor.FCKConfig.MP3BrowserWindowWidth ;
	var iHeight	= oEditor.FCKConfig.MP3BrowserWindowHeight ;
	
	var iLeft = (screen.width  - iWidth) / 2 ;
	var iTop  = (screen.height - iHeight) / 2 ;

	var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes" ;
	sOptions += ",width=" + iWidth ; 
	sOptions += ",height=" + iHeight ;
	sOptions += ",left=" + iLeft ;
	sOptions += ",top=" + iTop ;

	// Open the browser window.
	var oWindow = window.open( oEditor.FCKConfig.MP3BrowserURL, "FCKBrowseWindow", sOptions ) ;
}

function SetUrl( url )
{
	document.getElementById('mpUrl').value = url ;
	updatePreview() ;
	Ok();
	window.parent.close();
}


var Media = function (o){
	this.url = '';
	this.width = '';
	this.height = '';
	
	if (o) 
		this.setObjectElement(o);
};

/**
 * Toma los datos de un elemento.
 */ 
Media.prototype.setObjectElement = function (e){
	if (!e) return ;
	
	this.width = GetAttribute( e, 'width', this.width );
	this.height = GetAttribute( e, 'height', this.height );
	this.url = GetAttribute( e, 'data', this.url );	
	// params
	for (var i=0;i<e.childNodes.length;i++){
		if (e.childNodes[i].tagName == 'PARAM'){
			var paramName = GetAttribute(e.childNodes[i], 'name', '').toLowerCase();
			var paramValue = GetAttribute(e.childNodes[i], 'value', '');
			
			switch (paramName){
				case 'movie':
					this.url = paramValue;
					break;
				case 'quality':
					this.quality = paramValue;
					break;
				case 'scale':
					this.scale = paramValue;
					break;
				case 'bgcolor':
					this.bgcolor = paramValue;
					break;
				case 'loop':
					this.loop = paramValue;
					break;
				case 'play':
					this.play = paramValue;
					break;
			}
		}
	}
};




/**
 * Devuelve el codigo HTML externo del elemento
 */
Media.prototype.getOuterHTML = function (objectId){
	var s;

  s= this.getInnerHTML(objectId);
  
  return s;
};


/**
 * Devuelve el codigo HTML interno del elemento
 */
Media.prototype.getInnerHTML = function (objectId){
	var s='<object id="mp3player" type="application/x-shockwave-flash" data="'+getObjData(this.url)+'" width="220" height="30" style="vertical-align: bottom;"><!-- MP3 Flash player. Credits, license, contact & examples: http://pyg.keonox.com/flashmp3player/ --><param name="type" value="application/x-shockwave-flash" /><param name="codebase" value="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" /><param name="movie" value="'+getObjData(this.url)+'" /><param name="FlashVars" value="file='+getSoundUrl()+'&amp;autolaunch=false" /></object>';
	return s;
};


Media.prototype.createAttribute = function(n,v){
	return ' '+n+'="'+v+'" ';
}


function OnUploadCompleted( errorNumber, fileUrl, fileName, customMsg )
{
	switch ( errorNumber )
	{
		case 0 :	// No errors
			//alert( 'Your file has been successfully uploaded' ) ;
			break ;
		case 1 :	// Custom error
			alert( customMsg ) ;
			return ;
		case 101 :	// Custom warning
			alert( customMsg ) ;
			break ;
		case 201 :
			alert( 'A file with the same name is already available. The uploaded file has been renamed to "' + fileName + '"' ) ;
			break ;
		case 202 :
			alert( 'Invalid file type' ) ;
			window.location.href=FCKConfig.PluginsPath + 'MP3/fck_mp3.php';
			return ;
		case 203 :
			alert( "Security error. You probably don't have enough permissions to upload. Please check your server." ) ;
			window.location.href=FCKConfig.PluginsPath + 'MP3/fck_mp3.php';
			return ;
		default :
			alert( 'Error on file upload. Error number: ' + errorNumber ) ;
			window.location.href=FCKConfig.PluginsPath + 'MP3/fck_mp3.php';
			return ;
	}

	SetUrl( fileUrl ) ;
	GetE('frmUpload').reset() ;
	// Reset the interface elements.
	//document.getElementById('eUploadMessage').innerHTML = 'Upload' ;
	document.getElementById('btnUpload').disabled = false ;

}

var oUploadAllowedExtRegex	= new RegExp( FCKConfig.MP3UploadAllowedExtensions, 'i' ) ;
var oUploadDeniedExtRegex	= new RegExp( FCKConfig.MP3UploadDeniedExtensions, 'i' ) ;

function CheckUpload()
{
	var sFile = GetE('txtUploadFile').value ;
	
	if ( sFile.length == 0 )
	{
		alert( 'Please select a file to upload' ) ;
		return false ;
	}
	
	if ( ( FCKConfig.MP3UploadAllowedExtensions.length > 0 && !oUploadAllowedExtRegex.test( sFile ) ) ||
		( FCKConfig.MP3UploadDeniedExtensions.length > 0 && oUploadDeniedExtRegex.test( sFile ) ) )
	{
		OnUploadCompleted( 202 ) ;
		return false ;
	}

	//document.getElementById('eUploadMessage').innerHTML = 'Upload in progress, please wait...' ;
	document.getElementById('btnUpload').disabled = true ;
	
	return true ;
}

function getObjData(mpUrl){ // to create data attribute for object
		var url=mpUrl;		
		var configBasePath = FCKConfig.BasePath;
		var cor_indx=configBasePath.indexOf("inc/")+4;
		var objdata = configBasePath.substring(0, cor_indx)+"lib/mp3player/player_mp3.swf";
		
		setSoundUrl(GetE('mpUrl').value);
		
		return objdata;
}

function setSoundUrl(url){
	mp3_url=url;
}

function getSoundUrl(){
	return mp3_url;
}

function getObjUrl(mpUrl2){ // to get source url
		var url2=mpUrl2;
		var cor_indx2 = url2.indexOf("son=")+4;
		
		var objdata2 = url2.substring(cor_indx2, mpUrl2.length);
		return objdata2;
}
