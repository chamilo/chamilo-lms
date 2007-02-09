/**
 * @author Patrick Vandermaesen
 */
function OpenFileBrowser( url, width, height )
{

	var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes" ;
	sOptions += ",width=" + width ;
	sOptions += ",height=" + height ;

	window.open( url, 'FCKBrowseWindow', sOptions ) ;
}