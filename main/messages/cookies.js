
function Set_Cookie( name, value, expires, path, domain, secure ) {
	// set time, it's in milliseconds
	var today = new Date();
	today.setTime( today.getTime() );
	// if the expires variable is set, make the correct expires time, the
	// current script below will set it for x number of days, to make it
	// for hours, delete * 24, for minutes, delete * 60 * 24
	if ( expires )
	{
		expires = expires * 1000 * 60 * 60 * 24;
	}
	//alert( 'today ' + today.toGMTString() );// this is for testing purpose only
	var expires_date = new Date( today.getTime() + (expires) );
	//alert('expires ' + expires_date.toGMTString());// this is for testing purposes only

	document.cookie = name + "=" +escape( value ) +
		( ( expires ) ? ";expires=" + expires_date.toGMTString() : "" ) + //expires.toGMTString()
		( ( path ) ? ";path=" + path : "" ) +
		( ( domain ) ? ";domain=" + domain : "" ) +
		( ( secure ) ? ";secure" : "" );
}

function Get_Cookie( name ) {
	var start = document.cookie.indexOf( name + "=" );
	var len = start + name.length + 1;
	if ( ( !start ) && ( name != document.cookie.substring( 0, name.length ) ) )
	{
		return null;
	}
	if ( start == -1 ) return null;
	var end = document.cookie.indexOf( ";", len );
	if ( end == -1 ) end = document.cookie.length;
	return unescape( document.cookie.substring( len, end ) );
}

var xmlHttp=null;
var url=null
function set_url(ref)
{
	url=ref;
}
function mostrar_aviso()
{

	document.getElementById("box").style.visibility="visible";

}

function ocultar_aviso()
{
	document.getElementById("box").style.visibility="hidden";

}

function notificar()
{
	vernuevos()
	setTimeout("notificar()",60000)
}

function vernuevos()
{

	xmlHttp=GetXmlHttpObject(stateChanged)
	xmlHttp.open("GET", url , true)
	xmlHttp.send(null)
}

function stateChanged()
{

	if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete")
	{
		document.getElementById("nuevos").innerHTML=xmlHttp.responseText
		if(Get_Cookie("nuevos")==null || xmlHttp.responseText-Get_Cookie("nuevos")>0 )
		{

			mostrar_aviso()
			setTimeout("ocultar_aviso()",7000);
		}
		Set_Cookie( "nuevos", xmlHttp.responseText, 0, "/",'','')
	}
}

	function GetXmlHttpObject(handler)
{
	var objXmlHttp=null
	if (navigator.userAgent.indexOf("Opera")>=0)
	{
		objXmlHttp=new XMLHttpRequest()
		objXmlHttp.onload=handler
		objXmlHttp.onerror=handler
		return objXmlHttp
	}
	if (navigator.userAgent.indexOf("MSIE")>=0)
	{
		var strName="Msxml2.XMLHTTP"
		if (navigator.appVersion.indexOf("MSIE 5.5")>=0)
		{
			strName="Microsoft.XMLHTTP"
		}
		try
		{
			objXmlHttp=new ActiveXObject(strName)
			objXmlHttp.onreadystatechange=handler
			return objXmlHttp
		}
		catch(e)
		{
		alert("Error. Scripting for ActiveX might be disabled")
		return
		}
	}
	if (navigator.userAgent.indexOf("Mozilla")>=0)
	{
		objXmlHttp=new XMLHttpRequest()
		objXmlHttp.onload=handler
		objXmlHttp.onerror=handler
		return objXmlHttp
	}
}
