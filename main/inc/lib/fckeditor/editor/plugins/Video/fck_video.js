var oEditor		= window.parent.InnerDialogLoaded() ;
var FCK			= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;
var flv_url = "";

// Set the dialog tabs.
window.parent.AddTab( 'Upload', FCKLang.DlgVideoUpload ) ;
window.parent.AddTab( 'Info', FCKLang.DlgVideoTab ) ;


var oMedia = null;

// Function called when a dialog tag is selected.
function OnDialogTabChange( tabCode )
{
	ShowE('divUpload'	, ( tabCode == 'Upload' ) ) ;
	ShowE('divInfo'		, ( tabCode == 'Info' ) );
}

// Get the selected Video embed (if available).
var oFakeImage = FCK.Selection.GetSelectedElement() ;
var oEmbed ;

if ( oFakeImage )
{
	if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute('_fckVideo') )
		oEmbed = FCK.GetRealElement( oFakeImage ) ;
	else
		oFakeImage = null ;
}

window.onload = function()
{
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	window.parent.SetSelectedTab( 'Upload' ) ;
	// Load the selected element information (if any).
	LoadSelection() ;

	// Show/Hide the "Browse Server" button.
	GetE('tdBrowse').style.display = FCKConfig.VideoBrowser	? '' : 'none' ;

	// Set the actual uploader URL.
	if ( FCKConfig.VideoUpload )
		GetE('frmUpload').action = FCKConfig.VideoUploadURL ;

	window.parent.SetAutoSize( true ) ;

	// Activate the "OK" button.
	//window.parent.SetOkButton( true ) ;
}

function LoadSelection()
{
	if ( ! oEmbed ) return ;

	//var sUrl = GetAttribute( oEmbed, 'src', '' ) ;

	GetE('txtUrl').value    = GetAttribute( oEmbed, 'src', '' ) ;
	GetE('controller').value    = 'true' ;
	//GetE('txtWidth').value  = GetAttribute( oEmbed, 'width', '' ) ;
	//GetE('txtHeight').value = GetAttribute( oEmbed, 'height', '' ) ;

	// Get Advances Attributes
	//GetE('txtAttId').value		= oEmbed.id ;
	//GetE('chkAutoPlay').checked	= GetAttribute( oEmbed, 'play', 'true' ) == 'true' ;
	//GetE('chkLoop').checked		= GetAttribute( oEmbed, 'loop', 'true' ) == 'true' ;

	UpdatePreview() ;
}

//#### The OK button was hit.
function Ok()
{
	if ( GetE('txtUrl').value.length == 0 )
	{
		window.parent.SetSelectedTab( 'Info' ) ;
		GetE('txtUrl').focus() ;

		alert( oEditor.FCKLang.DlgAlertUrl ) ;

		return false ;
	}
	
	
	if ( !oEmbed )
	{
		oEmbed		= FCK.EditorDocument.createElement( 'EMBED' ) ;
		oFakeImage  = null ;
	}
	
	
	// check if it's a flv file
	tmp_url = GetE('txtUrl').value;
	extension = tmp_url.substring(tmp_url.lastIndexOf('.')+1);
	
	if(extension == 'flv')
		flvplayer = true;
	else
		flvplayer = false;
	
	if(flvplayer == true)
	{
		var e = (oMedia || new Media()) ;
		updateMovie(e);
		FCK.InsertHtml(e.getOuterHTML()) ;	
			
		if ( !oFakeImage )
		{
			oFakeImage	= oEditor.FCKDocumentProcessors_CreateFakeImage( 'FCK__Video_flv', oEmbed ) ;
			oFakeImage.setAttribute( '_fckVideo', 'true', 0 ) ;
			oFakeImage	= FCK.InsertElementAndGetIt( oFakeImage ) ;
		}
		else
			oEditor.FCKUndo.SaveUndoStep() ;
	}
	else
	{	
		if ( !oFakeImage )
		{
			oFakeImage	= oEditor.FCKDocumentProcessors_CreateFakeImage( 'FCK__Video', oEmbed ) ;
			oFakeImage.setAttribute( '_fckVideo', 'true', 0 ) ;
			oFakeImage	= FCK.InsertElementAndGetIt( oFakeImage ) ;
		}
		else
			oEditor.FCKUndo.SaveUndoStep() ;
		UpdateEmbed( oEmbed ) ;		
	}
	

	
	oEditor.FCKFlashProcessor.RefreshView( oFakeImage, oEmbed ) ;
	return true ;
}


function UpdateEmbed( e )
{
	//SetAttribute( e, 'type'			, 'application/x-shockwave-flash' ) ;
	//SetAttribute( e, 'pluginspage'	, 'http://www.macromedia.com/go/getflashplayer' ) ;

	SetAttribute( e, "controller", 'true' ) ;

	e.src = GetE('txtUrl').value ;
	//SetAttribute( e, "width" , GetE('txtWidth').value ) ;
	//SetAttribute( e, "height", GetE('txtHeight').value ) ;
	
	// Advances Attributes

	//SetAttribute( e, 'id'	, GetE('txtAttId').value ) ;
	//SetAttribute( e, 'scale', GetE('cmbScale').value ) ;
	
	//SetAttribute( e, 'play', GetE('chkAutoPlay').checked ? 'true' : 'false' ) ;
	//SetAttribute( e, 'loop', GetE('chkLoop').checked ? 'true' : 'false' ) ;
	//SetAttribute( e, 'menu', GetE('chkMenu').checked ? 'true' : 'false' ) ;

	//SetAttribute( e, 'title'	, GetE('txtAttTitle').value ) ;

	/*
	if ( oEditor.FCKBrowserInfo.IsIE )
	{
		SetAttribute( e, 'className', GetE('txtAttClasses').value ) ;
		e.style.cssText = GetE('txtAttStyle').value ;
	}
	else
	{
		SetAttribute( e, 'class', GetE('txtAttClasses').value ) ;
		SetAttribute( e, 'style', GetE('txtAttStyle').value ) ;
	}
	*/
}

function UpdatePreview()
{
	
	if ( GetE('txtUrl').value.length == 0 )
	{
		return;
	}
	else
	{
		window.parent.SetSelectedTab( 'Info' ) ;
	}
}

// <embed id="ePreview" src="fck_flash/claims.swf" width="100%" height="100%" style="visibility:hidden" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">

function BrowseServer()
{
	OpenFileBrowser( FCKConfig.VideoBrowserURL, FCKConfig.VideoBrowserWindowWidth, FCKConfig.VideoBrowserWindowHeight ) ;
}

function SetUrl( url, width, height )
{
	GetE('txtUrl').value = url ;
	
	if ( width )
		GetE('txtWidth').value = width ;
		
	if ( height ) 
		GetE('txtHeight').value = height ;

	UpdatePreview() ;

	//window.parent.SetSelectedTab( 'Info' ) ;
	
	Ok();
	window.parent.close();

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
			window.location.href=FCKConfig.PluginsPath + 'Video/fck_video.php';
			return ;
		case 203 :
			alert( "Security error. You probably don't have enough permissions to upload. Please check your server." ) ;
			window.location.href=FCKConfig.PluginsPath + 'Video/fck_video.php';
			return ;
		default :
			alert( 'Error on file upload. Error number: ' + errorNumber ) ;
			window.location.href=FCKConfig.PluginsPath + 'Video/fck_video.php';
			return ;
	}

	SetUrl( fileUrl ) ;
	GetE('frmUpload').reset() ;
}

var oUploadAllowedExtRegex	= new RegExp( FCKConfig.VideoUploadAllowedExtensions, 'i' ) ;
var oUploadDeniedExtRegex	= new RegExp( FCKConfig.VideoUploadDeniedExtensions, 'i' ) ;

function CheckUpload()
{
	var sFile = GetE('txtUploadFile').value ;
	
	if ( sFile.length == 0 )
	{
		alert( 'Please select a file to upload' ) ;
		return false ;
	}
	
	alert("ici "+oUploadAllowedExtRegex.test( sFile ));
	
	if ( ( FCKConfig.VideoUploadAllowedExtensions.length > 0 && !oUploadAllowedExtRegex.test( sFile ) ) ||
		( FCKConfig.VideoUploadDeniedExtensions.length > 0 && oUploadDeniedExtRegex.test( sFile ) ) )
	{
		OnUploadCompleted( 202 ) ;
		return false ;
	}
	
	return true ;
}

function OnSizeChanged( dimension, value ) 
{	
	UpdatePreview() ;
}

function updateMovie(e){
	e.url = GetE('txtUrl').value;
}


function getObjData(txtUrl){ // to create data attribute for object
		var url=txtUrl;		
		var configBasePath = FCKConfig.BasePath;
		var cor_indx=configBasePath.indexOf("inc/")+4;
		var objdata = configBasePath.substring(0, cor_indx)+"lib/flv_player/player_flv_mini.swf";
		
		setVideoUrl(GetE('txtUrl').value);
		
		return objdata;
}

function setVideoUrl(url){
	flv_url=url;
}

function getVideoUrl(){
	return flv_url;
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
	var s = '<object type="application/x-shockwave-flash" data="'+getObjData(this.url)+'" height="240" width="320">\r\n<param name="movie" value="'+getObjData(this.url)+'">\r\n\t<param name="FlashVars" value="flv='+getVideoUrl()+'&autoplay=1&width=320&amp;height=240" /></object>\r\n';

	return s;
};


Media.prototype.createAttribute = function(n,v){
	return ' '+n+'="'+v+'" ';
}