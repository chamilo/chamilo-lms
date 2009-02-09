var dialog			= window.parent ;
var oEditor		= dialog.InnerDialogLoaded() ;
var FCK				= oEditor.FCK; 
var FCKLang			= oEditor.FCKLang ;
var FCKConfig		= oEditor.FCKConfig ;
var FCKTools	= oEditor.FCKTools ;

// Set the language direction.
window.document.dir = FCKLang.Dir ;

//#### Dialog Tabs

// Set the dialog tabs.
dialog.AddTab( 'Info', FCKLang.DlgInfoTab ) ;

if ( FCKConfig.VideoUpload )
	dialog.AddTab( 'Upload', FCKLang.DlgLnkUpload ) ;


// Function called when a dialog tag is selected.
function OnDialogTabChange( tabCode )
{
	ShowE('divInfo'		, ( tabCode == 'Info' ) ) ;
	ShowE('divUpload'	, ( tabCode == 'Upload' ) ) ;
}

// <object><param><embed> alternative not working properly
// for reasons only microsoft can know)
var EmbedInObject = false; 

// Get the selected embedded movie and its container div (if available).
var oMovie = null;
var oContainerDiv = FCK.Selection.GetSelectedElement();


// Get the selected video embed (if available).
var oFakeImage = dialog.Selection.GetSelectedElement() ;
var oEmbed ;

if ( oFakeImage )
{
	if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute('_fckvideo') )
		oEmbed = FCK.GetRealElement( oFakeImage ) ;
	else
		oFakeImage = null ;
}

if (oContainerDiv)
{
	if(oContainerDiv.tagName == 'DIV' && 
		 oContainerDiv.childNodes.length > 0 &&
		 oContainerDiv.childNodes[0].tagName == (EmbedInObject ? 'OBJECT' : 'EMBED'))
	 oMovie = oContainerDiv.childNodes[0];
	else if (oContainerDiv.tagName == (EmbedInObject ? 'OBJECT' : 'EMBED') &&
	         oContainerDiv.parentNode.tagName == 'DIV')
	{
		oMovie = oContainerDiv;
		oContainerDiv  = oContainerDiv.parentNode;
	}
	else
		oContainerDiv = null;
}

// Added by Ivan Tcholakov.
if (!EmbedInObject)
{
	oMovie = oEmbed;
}

function GetParam(e, pname, defvalue)
{
	if (!e) return defvalue;
	if (EmbedInObject)
	{
		for (var i = 0; i < e.childNodes.length; i++)
		{
			if (e.childNodes[i].tagName == 'PARAM' && GetAttribute(e.childNodes[i], 'name') == pname)
			{
				var retval = GetAttribute(e.childNodes[i], 'value');
				if (retval == "false") return false;
				return retval;
			}
		}
		return defvalue;
	}
	else
	{
		var retval = GetAttribute(e, pname, defvalue);
		if (retval == "false") return false;
		return retval;
	}
}

window.onload = function ()	
{
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document);
	
	// Read settings from existing embedded movie or set to default.

	GetE('txtUrl').value = GetParam( oMovie, ( EmbedInObject ? 'url' : 'src' ), '' ) ;

	GetE('chkAutosize').checked      = GetParam(oMovie,  'autosize',     true);
	GetE('txtWidth').value           = GetParam(oMovie,  'width',        320  );
	GetE('txtHeight').value          = GetParam(oMovie,  'height',       240  );
	GetE('chkAutostart').checked     = GetParam(oMovie, 'autostart',     false);
	GetE('chkShowgotobar').checked   = GetParam(oMovie, 'showgotobar',   false);
	GetE('chkShowstatusbar').checked = GetParam(oMovie, 'showstatusbar', false);
	GetE('chkShowcontrols').checked  = GetParam(oMovie, 'showcontrols',  true );
	GetE('chkShowtracker').checked   = GetParam(oMovie, 'showtracker',   true );
	GetE('chkShowaudiocontrols').checked    = GetParam(oMovie, 'showaudiocontrols',    true);
	GetE('chkShowpositioncontrols').checked = GetParam(oMovie, 'showpositioncontrols', true);

	// Show/Hide according to settings.
	ShowE('divSize',  !GetE('chkAutosize').checked);
	ShowE('tdBrowse', FCKConfig.LinkBrowser);
	ShowE('divControlsettings', GetE('chkShowcontrols').checked)

	// Show/Hide the "Browse Server" button.
	GetE('tdBrowse').style.display = FCKConfig.VideoBrowser ? '' : 'none' ;

	// Set the actual uploader URL.
	if ( FCKConfig.VideoUpload )
		GetE('frmUpload').action = FCKConfig.VideoUploadURL ;

	dialog.SetAutoSize( true ) ;

	// Activate the "OK" button.
	dialog.SetOkButton( true ) ;

	SelectField( 'txtUrl' ) ;
} 

function CreateEmbeddedMovie(e, url)
{
	var sType, pluginspace, codebase, classid;
	var sExt = url.match(/\.(mpg|mpeg|mp4|avi|wmv|mov|asf)$/i);

	if (sExt ==null)
	{
		alert('We only support these extensions mpg, mpeg, mp4, avi, wmv, mov and asf. ')
		return false;
	}
	else
	{
		if (sExt.length && sExt.length > 0)
			sExt = sExt[0];
		else
			sExt = '';
			
		sType = (sExt=="mpg"||sExt=="mpeg") ? "video/mpeg" :
				(sExt=="avi"||sExt=="wmv"||sExt=="asf") ? "video/x-msvideo" :
				(sExt=="mov") ? "video/quicktime" :
				(sExt=="mp4") ? "video/mpeg4-generic" :
				"video/x-msvideo" ;
		
		// window media player?
		var wmp = sExt != "mov";
		if (wmp)
		{
			pluginspace = "http://www.microsoft.com/Windows/MediaPlayer/";
			codebase    = "http://www.microsoft.com/Windows/MediaPlayer/";
			classid     = 'classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95"';
		}
		else
		{
			pluginspace = "http://www.apple.com/quicktime/download/";
			codebase    = "http://www.apple.com/qtactivex/qtplugin.cab";
			classid     = "";
		}

	
		var html;
		if (EmbedInObject)
		{
			html  = '<object '+ classid +'>';
			html += '<param name="url" value="'+ url +'" />';
			html += '<param name="filename" value="'+ url +'" />';
			html += '<param name="autostart" value="'+ (GetE('chkAutostart').checked?"true":"false") +'" />';
			html += '<param name="showcontrols" value="'+ (GetE('chkShowcontrols').checked?"true":"false") +'" />';
			html += '<param name="showpositioncontrols" value="'+ (GetE('chkShowpositioncontrols').checked?"true":"false") +'" />';
			html += '<param name="showtracker" value="'+ (GetE('chkShowtracker').checked?"true":"false") +'" />';
			html += '<param name="showaudiocontrols" value="'+ (GetE('chkShowaudiocontrols').checked?"true":"false") +'" />';
			html += '<param name="showgotobar" value="'+ (GetE('chkShowgotobar').checked?"true":"false") +'" />';
			html += '<param name="showstatusbar" value="'+ (GetE('chkShowstatusbar').checked?"true":"false") +'" />';
			html += '<param name="standby" value="Loading Video..." />';
			html += '<param name="pluginspace" value="'+ pluginspace +'" />';
			html += '<param name="codebase" value="'+ codebase +'" />'; 
			html += '<embed type="'+ sType +'" src="'+ url +'"></embed>';
			html += '<noembed>Download movie: <a href="'+ url +'">'+ url +'</a></noembed>';
			html += '</object>';
		}
		else
		{
			html = '<embed type="'+ sType +'" src="'+ url +'" '+
			       'autosize="'+ (GetE('chkAutosize').checked?"true":"false") +'" '+
			       'autostart="'+ (GetE('chkAutostart').checked?"true":"false") +'" '+
			       'showcontrols="'+ (GetE('chkShowcontrols').checked?"true":"false") +'" '+
			       'showpositioncontrols="'+ (GetE('chkShowpositioncontrols').checked?"true":"false") +'" '+
			       'showtracker="'+ (GetE('chkShowtracker').checked?"true":"false") +'" '+
			       'showaudiocontrols="'+ (GetE('chkShowaudiocontrols').checked?"true":"false") +'" '+
			       'showgotobar="'+ (GetE('chkShowgotobar').checked?"true":"false") +'" '+
			       'showstatusbar="'+ (GetE('chkShowstatusbar').checked?"true":"false") +'" '+
			       'pluginspace="'+ pluginspace +'" '+
			       'codebase="'+ codebase +'"';
			if (!GetE('chkAutosize').checked)	
				html += 'width="'+ GetE('txtWidth').value +'" height="'+ GetE('txtHeight').value +'"';
			html += '></embed>';
		}

		//e.innerHTML = html;
		//FCK.InsertHtml(html);

		return html;
	}
}

//#### The OK button was hit.
function Ok()
{
	if ( GetE('txtUrl').value.length == 0 )
	{
		dialog.SetSelectedTab( 'Info' ) ;
		GetE('txtUrl').focus() ;

		alert( FCKLang.DlgAlertUrl ) ;

		return false ;
	}

	// Disabled by Ivan Tcholakov.
	//if (!oContainerDiv)
	//{
	//	oContainerDiv = FCK.CreateElement('DIV');
	//}

	if ( !oEmbed )
	{
		oEmbed		= FCK.EditorDocument.createElement( 'EMBED' ) ;		
		oFakeImage  = null ;
	}

	url = GetE( 'txtUrl' ).value ; 

	if ( !oFakeImage )
	{	
		oFakeImage	= oEditor.FCKDocumentProcessor_CreateFakeImage( 'FCK__Video', oEmbed ) ;
		oFakeImage.setAttribute( '_fckvideo', 'true', 0 ) ;
		//oFakeImage	= FCK.InsertElement( oFakeImage ) ;
	}

	oEditor.FCKEmbedAndObjectProcessor.RefreshView( oFakeImage, oEmbed ) ;	

	html = CreateEmbeddedMovie(oContainerDiv, url )	
	FCK.InsertHtml(html) ;

	oEditor.FCKUndo.SaveUndoStep();

	return true;
}

function BrowseServer()
{
	OpenFileBrowser( FCKConfig.VideoBrowserURL, FCKConfig.VideoBrowserWindowWidth, FCKConfig.VideoBrowserWindowHeight ) ;
}

function SetUrl( url )
{
	url = FCK.GetSelectedUrl( url ) ;

	GetE('txtUrl').value = url;

	dialog.SetSelectedTab( 'Info' ) ;
}

function OnUploadCompleted( errorNumber, fileUrl, fileName, customMsg )
{
	// Remove animation
	window.parent.Throbber.Hide() ;
	GetE( 'divUpload' ).style.display  = '' ;

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
			return ;
		case 203 :
			alert( "Security error. You probably don't have enough permissions to upload. Please check your server." ) ;
			return ;
		case 500 :
			alert( 'The connector is disabled' ) ;
			break ;
		default :
			alert( 'Error on file upload. Error number: ' + errorNumber ) ;
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
	
	if ( ( FCKConfig.VideoUploadAllowedExtensions.length > 0 && !oUploadAllowedExtRegex.test( sFile ) ) ||
		( FCKConfig.VideoUploadDeniedExtensions.length > 0 && oUploadDeniedExtRegex.test( sFile ) ) )
	{
		OnUploadCompleted( 202 ) ;
		return false ;
	}

	// Show animation
	window.parent.Throbber.Show( 100 ) ;
	GetE( 'divUpload' ).style.display  = 'none' ;

	return true ;
}
