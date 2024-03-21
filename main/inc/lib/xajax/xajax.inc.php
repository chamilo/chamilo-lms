<?php
/**
 * xajax.inc.php :: Main xajax class and setup file
 *
 * xajax version 0.2.4
 * copyright (c) 2005 by Jared White & J. Max Wilson
 * http://www.xajaxproject.org
 *
 * xajax is an open source PHP class library for easily creating powerful
 * PHP-driven, web-based Ajax Applications. Using xajax, you can asynchronously
 * call PHP functions and update the content of your your webpage without
 * reloading the page.
 *
 * xajax is released under the terms of the LGPL license
 * http://www.gnu.org/copyleft/lesser.html#SEC3
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package chamilo.include.xajax
 * @version $Id: xajax.inc.php,v 1.1 2006/07/21 15:29:46 elixir_inter Exp $
 * @copyright Copyright (c) 2005-2006  by Jared White & J. Max Wilson
 * @license http://www.gnu.org/copyleft/lesser.html#SEC3 LGPL License
 */

/*
   ----------------------------------------------------------------------------
   | Online documentation for this class is available on the xajax wiki at:   |
   | http://wiki.xajaxproject.org/Documentation:xajax.inc.php                 |
   ----------------------------------------------------------------------------
*/

/**
 * Define XAJAX_DEFAULT_CHAR_ENCODING that is used by both
 * the xajax and xajaxResponse classes
 */
if (!defined ('XAJAX_DEFAULT_CHAR_ENCODING'))
{
	define ('XAJAX_DEFAULT_CHAR_ENCODING', 'utf-8' );
}

/**
 * Communication Method Defines
 */
if (!defined ('XAJAX_GET'))
{
	define ('XAJAX_GET', 0);
}
if (!defined ('XAJAX_POST'))
{
	define ('XAJAX_POST', 1);
}

/**
 * The xajax class generates the xajax javascript for your page including the
 * Javascript wrappers for the PHP functions that you want to call from your page.
 * It also handles processing and executing the command messages in the XML responses
 * sent back to your page from your PHP functions.
 *
 * @package chamilo.include.xajax
 */
class xajax
{
	/**#@+
	 * @access protected
	 */
	/**
	 * @var array Array of PHP functions that will be callable through javascript wrappers
	 */
	public array $aFunctions;
	/**
	 * @var array Array of object callbacks that will allow Javascript to call PHP methods (key=function name)
	 */
	public array $aObjects;
	/**
	 * @var array Array of RequestTypes to be used with each function (key=function name)
	 */
	public array $aFunctionRequestTypes;
	/**
	 * @var array Array of Include Files for any external functions (key=function name)
	 */
	public array $aFunctionIncludeFiles;
	/**
	 * @var string Name of the PHP function to call if no callable function was found
	 */
	public string $sCatchAllFunction;
	/**
	 * @var string Name of the PHP function to call before any other function
	 */
	public string $sPreFunction;
	/**
	 * @var string The URI for making requests to the xajax object
	 */
	public string $sRequestURI;
	/**
	 * @var string The prefix to prepend to the javascript wraper function name
	 */
	public string $sWrapperPrefix;
	/**
	 * @var boolean Show debug messages (default false)
	 */
    public bool $bDebug;
	/**
	 * @var boolean Show messages in the client browser's status bar (default false)
	 */
    public bool $bStatusMessages;
	/**
	 * @var boolean Allow xajax to exit after processing a request (default true)
	 */
    public bool $bExitAllowed;
	/**
	 * @var boolean Use wait cursor in browser (default true)
	 */
    public bool $bWaitCursor;
	/**
	 * @var boolean Use a special xajax error handler so the errors are sent to the browser properly (default false)
	 */
    public bool $bErrorHandler;
	/**
	 * @var string Specify what, if any, file xajax should log errors to (and more information in a future release)
	 */
    public string $sLogFile;
	/**
	 * @var boolean Clean all output buffers before outputting response (default false)
	 */
    public bool $bCleanBuffer;
	/**
	 * @var string String containing the character encoding used
	 */
    public string $sEncoding;
	/**
	 * @var boolean Decode input request args from UTF-8 (default false)
	 */
    public bool $bDecodeUTF8Input;
	/**
	 * @var boolean Convert special characters to HTML entities (default false)
	 */
    public bool $bOutputEntities;
	/**
	 * @var array Array for parsing complex objects
	 */
    public array $aObjArray;
	/**
	 * @var integer Position in $aObjArray
	 */
    public int $iPos;

	/**#@-*/

	/**
	 * Constructor. You can set some extra xajax options right away or use
	 * individual methods later to set options.
	 *
	 * @param string $sRequestURI defaults to the current browser URI
	 * @param string $sWrapperPrefix defaults to "xajax_";
	 * @param string $sEncoding defaults to XAJAX_DEFAULT_CHAR_ENCODING defined above
	 * @param boolean $bDebug defaults to false
	 */
	public function __construct(string $sRequestURI = '', string $sWrapperPrefix = 'xajax_', string $sEncoding = XAJAX_DEFAULT_CHAR_ENCODING, bool $bDebug = false)
	{
		$this->aFunctions = array();
		$this->aObjects = array();
		$this->aFunctionIncludeFiles = array();
		$this->sRequestURI = $sRequestURI;
		if ($this->sRequestURI == "")
			$this->sRequestURI = $this->_detectURI();
		$this->sWrapperPrefix = $sWrapperPrefix;
		$this->bDebug = $bDebug;
		$this->bStatusMessages = false;
		$this->bWaitCursor = true;
		$this->bExitAllowed = true;
		$this->bErrorHandler = false;
		$this->sLogFile = "";
		$this->bCleanBuffer = false;
		$this->setCharEncoding($sEncoding);
		$this->bDecodeUTF8Input = false;
		$this->bOutputEntities = false;
        $this->sPreFunction = '';
	}

	/**
	 * Sets the URI to which requests will be made.
	 * <i>Usage:</i> <kbd>$xajax->setRequestURI("http://www.xajaxproject.org");</kbd>
	 *
	 * @param string $sRequestURI the URI (can be absolute or relative) of the PHP script
	 *               that will be accessed when a xajax request occurs
	 */
	function setRequestURI(string $sRequestURI)
	{
		$this->sRequestURI = $sRequestURI;
	}

	/**
	 * Sets the prefix that will be appended to the Javascript wrapper
	 * functions (default is "xajax_").
	 *
	 * @param string $sPrefix
	 */
	//
	function setWrapperPrefix(string $sPrefix)
	{
		$this->sWrapperPrefix = $sPrefix;
	}

	/**
	 * Enables debug messages for xajax.
	 * */
	function debugOn()
	{
		$this->bDebug = true;
	}

	/**
	 * Disables debug messages for xajax (default behavior).
	 */
	function debugOff()
	{
		$this->bDebug = false;
	}

	/**
	 * Enables messages in the browser's status bar for xajax.
	 */
	function statusMessagesOn()
	{
		$this->bStatusMessages = true;
	}

	/**
	 * Disables messages in the browser's status bar for xajax (default behavior).
	 */
	function statusMessagesOff()
	{
		$this->bStatusMessages = false;
	}

	/**
	 * Enables the wait cursor to be displayed in the browser (default behavior).
	 */
	function waitCursorOn()
	{
		$this->bWaitCursor = true;
	}

	/**
	 * Disables the wait cursor to be displayed in the browser.
	 */
	function waitCursorOff()
	{
		$this->bWaitCursor = false;
	}

	/**
	 * Enables xajax to exit immediately after processing a request and
	 * sending the response back to the browser (default behavior).
	 */
	function exitAllowedOn()
	{
		$this->bExitAllowed = true;
	}

	/**
	 * Disables xajax's default behavior of exiting immediately after
	 * processing a request and sending the response back to the browser.
	 */
	function exitAllowedOff()
	{
		$this->bExitAllowed = false;
	}

	/**
	 * Turns on xajax's error handling system so that PHP errors that occur
	 * during a request are trapped and pushed to the browser in the form of
	 * a Javascript alert.
	 */
	function errorHandlerOn()
	{
		$this->bErrorHandler = true;
	}

	/**
	 * Turns off xajax's error handling system (default behavior).
	 */
	function errorHandlerOff()
	{
		$this->bErrorHandler = false;
	}

	/**
	 * Specifies a log file that will be written to by xajax during a request
	 * (used only by the error handling system at present). If you don't invoke
	 * this method, or you pass in "", then no log file will be written to.
	 * <i>Usage:</i> <kbd>$xajax->setLogFile("/xajax_logs/errors.log");</kbd>
	 */
	function setLogFile($sFilename)
	{
		$this->sLogFile = $sFilename;
	}

	/**
	 * Causes xajax to clean out all output buffers before outputting a
	 * response (default behavior).
	 */
	function cleanBufferOn()
	{
		$this->bCleanBuffer = true;
	}
	/**
	 * Turns off xajax's output buffer cleaning.
	 */
	function cleanBufferOff()
	{
		$this->bCleanBuffer = false;
	}

	/**
	 * Sets the character encoding for the HTTP output based on
	 * <kbd>$sEncoding</kbd>, which is a string containing the character
	 * encoding to use. You don't need to use this method normally, since the
	 * character encoding for the response gets set automatically based on the
	 * <kbd>XAJAX_DEFAULT_CHAR_ENCODING</kbd> constant.
	 * <i>Usage:</i> <kbd>$xajax->setCharEncoding("utf-8");</kbd>
	 *
	 * @param string $sEncoding the encoding type to use (utf-8, iso-8859-1, etc.)
	 */
	function setCharEncoding(string $sEncoding)
	{
		$this->sEncoding = $sEncoding;
	}

	/**
	 * Causes xajax to decode the input request args from UTF-8 to the current
	 * encoding if possible. Either the iconv or mb_string extension must be
	 * present for optimal functionality.
	 */
	function decodeUTF8InputOn()
	{
		$this->bDecodeUTF8Input = true;
	}

	/**
	 * Turns off decoding the input request args from UTF-8 (default behavior).
	 */
	function decodeUTF8InputOff()
	{
		$this->bDecodeUTF8Input = false;
	}

	/**
	 * Tells the response object to convert special characters to HTML entities
	 * automatically (only works if the mb_string extension is available).
	 */
	function outputEntitiesOn()
	{
		$this->bOutputEntities = true;
	}

	/**
	 * Tells the response object to output special characters intact. (default
	 * behavior).
	 */
	function outputEntitiesOff()
	{
		$this->bOutputEntities = false;
	}

	/**
	 * Registers a PHP function or method to be callable through xajax in your
	 * Javascript. If you want to register a function, pass in the name of that
	 * function. If you want to register a static class method, pass in an
	 * array like so:
	 * <kbd>array("myFunctionName", "myClass", "myMethod")</kbd>
	 * For an object instance method, use an object variable for the second
	 * array element (and in PHP 4 make sure you put an & before the variable
	 * to pass the object by reference). Note: the function name is what you
	 * call via Javascript, so it can be anything as long as it doesn't
	 * conflict with any other registered function name.
	 *
	 * <i>Usage:</i> <kbd>$xajax->registerFunction("myFunction");</kbd>
	 * or: <kbd>$xajax->registerFunction(array("myFunctionName", &$myObject, "myMethod"));</kbd>
	 *
	 * @param mixed $mFunction contains the function name or an object callback array
	 * @param mixed $sRequestType request type (XAJAX_GET/XAJAX_POST) that should be used
	 *               for this function.  Defaults to XAJAX_POST.
	 */
	function registerFunction($mFunction,$sRequestType=XAJAX_POST)
	{
		if (is_array($mFunction)) {
			$this->aFunctions[$mFunction[0]] = 1;
			$this->aFunctionRequestTypes[$mFunction[0]] = $sRequestType;
			$this->aObjects[$mFunction[0]] = array_slice($mFunction, 1);
		}
		else {
			$this->aFunctions[$mFunction] = 1;
			$this->aFunctionRequestTypes[$mFunction] = $sRequestType;
		}
	}

	/**
	 * Registers a PHP function to be callable through xajax which is located
	 * in some other file.  If the function is requested the external file will
	 * be included to define the function before the function is called.
	 *
	 * <i>Usage:</i> <kbd>$xajax->registerExternalFunction("myFunction","myFunction.inc.php",XAJAX_POST);</kbd>
	 *
	 * @param string $mFunction contains the function name or an object callback array
	 *               ({@link xajax::registerFunction() see registerFunction} for
	 *               more info on object callback arrays)
	 * @param string $sIncludeFile contains the path and filename of the include file
	 * @param mixed  $sRequestType the RequestType (XAJAX_GET/XAJAX_POST) that should be used
	 *		          for this function. Defaults to XAJAX_POST.
	 */
	function registerExternalFunction($mFunction,string $sIncludeFile,$sRequestType=XAJAX_POST)
	{
		$this->registerFunction($mFunction, $sRequestType);

		if (is_array($mFunction)) {
			$this->aFunctionIncludeFiles[$mFunction[0]] = $sIncludeFile;
		}
		else {
			$this->aFunctionIncludeFiles[$mFunction] = $sIncludeFile;
		}
	}

	/**
	 * Registers a PHP function to be called when xajax cannot find the
	 * function being called via Javascript. Because this is technically
	 * impossible when using "wrapped" functions, the catch-all feature is
	 * only useful when you're directly using the xajax.call() Javascript
	 * method. Use the catch-all feature when you want more dynamic ability to
	 * intercept unknown calls and handle them in a custom way.
	 *
	 * <i>Usage:</i> <kbd>$xajax->registerCatchAllFunction("myCatchAllFunction");</kbd>
	 *
	 * @param string $mFunction contains the function name or an object callback array
	 *               ({@link xajax::registerFunction() see registerFunction} for
	 *               more info on object callback arrays)
	 */
	function registerCatchAllFunction($mFunction)
	{
		if (is_array($mFunction)) {
			$this->sCatchAllFunction = $mFunction[0];
			$this->aObjects[$mFunction[0]] = array_slice($mFunction, 1);
		}
		else {
			$this->sCatchAllFunction = $mFunction;
		}
	}

	/**
	 * Registers a PHP function to be called before xajax calls the requested
	 * function. xajax will automatically add the request function's response
	 * to the pre-function's response to create a single response. Another
	 * feature is the ability to return not just a response, but an array with
	 * the first element being false (a boolean) and the second being the
	 * response. In this case, the pre-function's response will be returned to
	 * the browser without xajax calling the requested function.
	 *
	 * <i>Usage:</i> <kbd>$xajax->registerPreFunction("myPreFunction");</kbd>
	 *
	 * @param string $mFunction contains the function name or an object callback array
	 *               ({@link xajax::registerFunction() see registerFunction} for
	 *               more info on object callback arrays)
	 */
	function registerPreFunction($mFunction)
	{
		if (is_array($mFunction)) {
			$this->sPreFunction = $mFunction[0];
			$this->aObjects[$mFunction[0]] = array_slice($mFunction, 1);
		}
		else {
			$this->sPreFunction = $mFunction;
		}
	}

	/**
	 * Returns true if xajax can process the request, false if otherwise.
	 * You can use this to determine if xajax needs to process the request or
	 * not.
	 *
	 * @return boolean
	 */
	function canProcessRequests()
	{
		if ($this->getRequestMode() != -1) return true;
		return false;
	}

	/**
	 * Returns the current request mode (XAJAX_GET or XAJAX_POST), or -1 if
	 * there is none.
	 *
	 * @return int
	 */
	function getRequestMode(): int
	{
		if (!empty($_GET["xajax"]))
			return XAJAX_GET;

		if (!empty($_POST["xajax"]))
			return XAJAX_POST;

		return -1;
	}

	/**
	 * This is the main communications engine of xajax. The engine handles all
	 * incoming xajax requests, calls the apporiate PHP functions (or
	 * class/object methods) and passes the XML responses back to the
	 * Javascript response handler. If your RequestURI is the same as your Web
	 * page then this function should be called before any headers or HTML has
	 * been sent.
	 */
	function processRequests()
	{
		$requestMode = -1;
		$sFunctionName = "";
		$bFoundFunction = true;
		$bFunctionIsCatchAll = false;
		$sFunctionNameForSpecial = "";
		$aArgs = array();
		$sPreResponse = "";
		$bEndRequest = false;
		$sResponse = "";

		$requestMode = $this->getRequestMode();
		if ($requestMode == -1) return;

		if ($requestMode == XAJAX_POST)
		{
			$sFunctionName = $_POST["xajax"];

			if (!empty($_POST["xajaxargs"]))
				$aArgs = $_POST["xajaxargs"];
		}
		else
		{
			header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header ("Cache-Control: no-cache, must-revalidate");
			header ("Pragma: no-cache");

			$sFunctionName = $_GET["xajax"];

			if (!empty($_GET["xajaxargs"]))
				$aArgs = $_GET["xajaxargs"];
		}

		// Use xajax error handler if necessary
		if ($this->bErrorHandler) {
			$GLOBALS['xajaxErrorHandlerText'] = "";
			set_error_handler("xajaxErrorHandler");
		}

		if (!empty($this->sPreFunction)) {
			if (!$this->_isFunctionCallable($this->sPreFunction)) {
				$bFoundFunction = false;
				$objResponse = new xajaxResponse();
				$objResponse->addAlert("Unknown Pre-Function ". $this->sPreFunction);
				$sResponse = $objResponse->getXML();
			}
		}
		//include any external dependencies associated with this function name
		if (array_key_exists($sFunctionName,$this->aFunctionIncludeFiles))
		{
			ob_start();
			include_once($this->aFunctionIncludeFiles[$sFunctionName]);
			ob_end_clean();
		}

		if ($bFoundFunction) {
			$sFunctionNameForSpecial = $sFunctionName;
			if (!array_key_exists($sFunctionName, $this->aFunctions))
			{
				if ($this->sCatchAllFunction) {
					$sFunctionName = $this->sCatchAllFunction;
					$bFunctionIsCatchAll = true;
				}
				else {
					$bFoundFunction = false;
					$objResponse = new xajaxResponse();
					$objResponse->addAlert("Unknown Function $sFunctionName.");
					$sResponse = $objResponse->getXML();
				}
			}
			else if ($this->aFunctionRequestTypes[$sFunctionName] != $requestMode)
			{
				$bFoundFunction = false;
				$objResponse = new xajaxResponse();
				$objResponse->addAlert("Incorrect Request Type.");
				$sResponse = $objResponse->getXML();
			}
		}

		if ($bFoundFunction)
		{
			for ($i = 0; $i < sizeof($aArgs); $i++)
			{
				if (stristr($aArgs[$i],"<xjxobj>") != false)
				{
					$aArgs[$i] = $this->_xmlToArray("xjxobj",$aArgs[$i]);
				}
				else if (stristr($aArgs[$i],"<xjxquery>") != false)
				{
					$aArgs[$i] = $this->_xmlToArray("xjxquery",$aArgs[$i]);
				}
				else if ($this->bDecodeUTF8Input)
				{
					$aArgs[$i] = $this->_decodeUTF8Data($aArgs[$i]);
				}
			}

			if ($this->sPreFunction) {
				$mPreResponse = $this->_callFunction($this->sPreFunction, array($sFunctionNameForSpecial, $aArgs));
				if (is_array($mPreResponse) && $mPreResponse[0] === false) {
					$bEndRequest = true;
					$sPreResponse = $mPreResponse[1];
				}
				else {
					$sPreResponse = $mPreResponse;
				}
				if (is_a($sPreResponse, "xajaxResponse")) {
					$sPreResponse = $sPreResponse->getXML();
				}
				if ($bEndRequest) $sResponse = $sPreResponse;
			}

			if (!$bEndRequest) {
				if (!$this->_isFunctionCallable($sFunctionName)) {
					$objResponse = new xajaxResponse();
					$objResponse->addAlert("The Registered Function $sFunctionName Could Not Be Found.");
					$sResponse = $objResponse->getXML();
				}
				else {
					if ($bFunctionIsCatchAll) {
						$aArgs = array($sFunctionNameForSpecial, $aArgs);
					}
					$sResponse = $this->_callFunction($sFunctionName, $aArgs);
				}
				if (is_a($sResponse, "xajaxResponse")) {
					$sResponse = $sResponse->getXML();
				}
				if (!is_string($sResponse) || strpos($sResponse, "<xjx>") === FALSE) {
					$objResponse = new xajaxResponse();
					$objResponse->addAlert("No XML Response Was Returned By Function $sFunctionName.");
					$sResponse = $objResponse->getXML();
				}
				else if ($sPreResponse != "") {
					$sNewResponse = new xajaxResponse($this->sEncoding, $this->bOutputEntities);
					$sNewResponse->loadXML($sPreResponse);
					$sNewResponse->loadXML($sResponse);
					$sResponse = $sNewResponse->getXML();
				}
			}
		}

		$sContentHeader = "Content-type: text/xml;";
		if ($this->sEncoding && strlen(trim($this->sEncoding)) > 0)
			$sContentHeader .= " charset=".$this->sEncoding;
		header($sContentHeader);
		if ($this->bErrorHandler && !empty( $GLOBALS['xajaxErrorHandlerText'] )) {
			$sErrorResponse = new xajaxResponse();
			$sErrorResponse->addAlert("** PHP Error Messages: **" . $GLOBALS['xajaxErrorHandlerText']);
			if ($this->sLogFile) {
				$fH = @fopen($this->sLogFile, "a");
				if (!$fH) {
					$sErrorResponse->addAlert("** Logging Error **\n\nxajax was unable to write to the error log file:\n" . $this->sLogFile);
				}
				else {
					fwrite($fH, "** xajax Error Log - " . strftime("%b %e %Y %I:%M:%S %p") . " **" . $GLOBALS['xajaxErrorHandlerText'] . "\n\n\n");
					fclose($fH);
				}
			}

			$sErrorResponse->loadXML($sResponse);
			$sResponse = $sErrorResponse->getXML();

		}
		if ($this->bCleanBuffer) while (@ob_end_clean());
		print $sResponse;
		if ($this->bErrorHandler) restore_error_handler();

		if ($this->bExitAllowed)
			exit();
	}

	/**
	 * Prints the xajax Javascript header and wrapper code into your page by
	 * printing the output of the getJavascript() method. It should only be
	 * called between the <pre><head> </head></pre> tags in your HTML page.
	 * Remember, if you only want to obtain the result of this function, use
	 * {@link xajax::getJavascript()} instead.
	 *
	 * <i>Usage:</i>
	 * <code>
	 *  <head>
	 *		...
	 *		< ?php $xajax->printJavascript(); ? >
	 * </code>
	 *
	 * @param string $sJsURI the relative address of the folder where xajax has been
	 *               installed. For instance, if your PHP file is
	 *               "http://www.myserver.com/myfolder/mypage.php"
	 *               and xajax was installed in
	 *               "http://www.myserver.com/anotherfolder", then $sJsURI
	 *               should be set to "../anotherfolder". Defaults to assuming
	 *               xajax is in the same folder as your PHP file.
	 * @param ?string $sJsFile the relative folder/file pair of the xajax Javascript
	 *               engine located within the xajax installation folder.
	 *               Defaults to xajax_js/xajax.js.
	 */
	function printJavascript(string $sJsURI="", string $sJsFile=NULL)
	{
		print $this->getJavascript($sJsURI, $sJsFile);
	}

	/**
	 * Returns the xajax Javascript code that should be added to your HTML page
	 * between the <kbd><head> </head></kbd> tags.
	 *
	 * <i>Usage:</i>
	 * <code>
	 *  < ?php $xajaxJSHead = $xajax->getJavascript(); ? >
	 *	<head>
	 *		...
	 *		< ?php echo $xajaxJSHead; ? >
	 * </code>
	 *
	 * @param string $sJsURI the relative address of the folder where xajax has been
	 *               installed. For instance, if your PHP file is
	 *               "http://www.myserver.com/myfolder/mypage.php"
	 *               and xajax was installed in
	 *               "http://www.myserver.com/anotherfolder", then $sJsURI
	 *               should be set to "../anotherfolder". Defaults to assuming
	 *               xajax is in the same folder as your PHP file.
	 * @param ?string $sJsFile the relative folder/file pair of the xajax Javascript
	 *               engine located within the xajax installation folder.
	 *               Defaults to xajax_js/xajax.js.
	 * @return string
	 */
	function getJavascript(string $sJsURI="", string $sJsFile=NULL): string
	{
		$html = $this->getJavascriptConfig();
		$html .= $this->getJavascriptInclude($sJsURI, $sJsFile);

		return $html;
	}

	/**
	 * Returns a string containing inline Javascript that sets up the xajax
	 * runtime (typically called internally by xajax from get/printJavascript).
	 *
	 * @return string
	 */
	function getJavascriptConfig(): string
	{
		$html  = "\t<script type=\"text/javascript\">\n";
		$html .= "var xajaxRequestUri=\"".$this->sRequestURI."\";\n";
		$html .= "var xajaxDebug=".($this->bDebug?"true":"false").";\n";
		$html .= "var xajaxStatusMessages=".($this->bStatusMessages?"true":"false").";\n";
		$html .= "var xajaxWaitCursor=".($this->bWaitCursor?"true":"false").";\n";
		$html .= "var xajaxDefinedGet=".XAJAX_GET.";\n";
		$html .= "var xajaxDefinedPost=".XAJAX_POST.";\n";
		$html .= "var xajaxLoaded=false;\n";

		foreach($this->aFunctions as $sFunction => $bExists) {
			$html .= $this->_wrap($sFunction,$this->aFunctionRequestTypes[$sFunction]);
		}

		$html .= "\t</script>\n";
		return $html;
	}

	/**
	 * Returns a string containing a Javascript include of the xajax.js file
	 * along with a check to see if the file loaded after six seconds
	 * (typically called internally by xajax from get/printJavascript).
	 *
	 * @param string $sJsURI the relative address of the folder where xajax has been
	 *               installed. For instance, if your PHP file is
	 *               "http://www.myserver.com/myfolder/mypage.php"
	 *               and xajax was installed in
	 *               "http://www.myserver.com/anotherfolder", then $sJsURI
	 *               should be set to "../anotherfolder". Defaults to assuming
	 *               xajax is in the same folder as your PHP file.
	 * @param ?string $sJsFile the relative folder/file pair of the xajax Javascript
	 *               engine located within the xajax installation folder.
	 *               Defaults to xajax_js/xajax.js.
	 * @return string
	 */
	function getJavascriptInclude(string $sJsURI="", ?string $sJsFile=NULL): string
	{
		if ($sJsFile == NULL) $sJsFile = "xajax_js/xajax.js";

		if ($sJsURI != "" && substr($sJsURI, -1) != "/") $sJsURI .= "/";

		$html = "\t<script type=\"text/javascript\" src=\"" . $sJsURI . $sJsFile . "\"></script>\n";
		$html .= "\t<script type=\"text/javascript\">\n";
		$html .= "window.setTimeout(function () { if (!xajaxLoaded) { alert('Error: the xajax Javascript file could not be included. Perhaps the URL is incorrect?\\nURL: {$sJsURI}{$sJsFile}'); } }, 6000);\n";
		$html .= "\t</script>\n";
		return $html;
	}

	/**
	 * Returns the current URL based upon the SERVER vars.
	 *
	 * @access private
	 * @return string
	 */
	function _detectURI(): string
	{
		$aURL = array();

		// Try to get the request URL
		if (!empty($_SERVER['REQUEST_URI'])) {
			$aURL = parse_url($_SERVER['REQUEST_URI']);
		}

		// Fill in the empty values
		if (empty($aURL['scheme'])) {
			if (!empty($_SERVER['HTTP_SCHEME'])) {
				$aURL['scheme'] = $_SERVER['HTTP_SCHEME'];
			} else {
				$aURL['scheme'] = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https' : 'http';
			}
		}

		if (empty($aURL['host'])) {
			if (!empty($_SERVER['HTTP_HOST'])) {
				if (strpos($_SERVER['HTTP_HOST'], ':') > 0) {
					list($aURL['host'], $aURL['port']) = explode(':', $_SERVER['HTTP_HOST']);
				} else {
					$aURL['host'] = $_SERVER['HTTP_HOST'];
				}
			} else if (!empty($_SERVER['SERVER_NAME'])) {
				$aURL['host'] = $_SERVER['SERVER_NAME'];
			} else {
				print "xajax Error: xajax failed to automatically identify your Request URI.";
				print "Please set the Request URI explicitly when you instantiate the xajax object.";
				exit();
			}
		}

		if (empty($aURL['port']) && !empty($_SERVER['SERVER_PORT'])) {
			$aURL['port'] = $_SERVER['SERVER_PORT'];
		}

		if (empty($aURL['path'])) {
			if (!empty($_SERVER['PATH_INFO'])) {
				$sPath = parse_url($_SERVER['PATH_INFO']);
			} else {
				$sPath = parse_url(api_get_self());
			}
			$aURL['path'] = $sPath['path'];
			unset($sPath);
		}

		if (!empty($aURL['query'])) {
			$aURL['query'] = '?'.$aURL['query'];
		}

		// Build the URL: Start with scheme, user and pass
		$sURL  = api_get_self();
		// Add the path and the query string
		$sURL.= @$aURL['query'];

		// Clean up
		unset($aURL);
		return $sURL;
	}

	/**
	 * Returns true if the function name is associated with an object callback,
	 * false if not.
	 *
	 * @param string $sFunction the name of the function
	 * @access private
	 * @return boolean
	 */
	function _isObjectCallback(string $sFunction): bool
	{
		if (array_key_exists($sFunction, $this->aObjects)) return true;
		return false;
	}

	/**
	 * Returns true if the function or object callback can be called, false if
	 * not.
	 *
	 * @param string $sFunction the name of the function
	 * @access private
	 * @return boolean
	 */
	function _isFunctionCallable(string $sFunction): bool
	{
		if ($this->_isObjectCallback($sFunction)) {
			if (is_object($this->aObjects[$sFunction][0])) {
				return method_exists($this->aObjects[$sFunction][0], $this->aObjects[$sFunction][1]);
			}
			else {
				return is_callable($this->aObjects[$sFunction]);
			}
		}
		else {
			return function_exists($sFunction);
		}
	}

	/**
	 * Calls the function, class method, or object method with the supplied
	 * arguments.
	 *
	 * @param string $sFunction the name of the function
	 * @param array  $aArgs arguments to pass to the function
	 * @access private
	 * @return mixed the output of the called function or method
	 */
	function _callFunction(string $sFunction, array $aArgs)
	{
		if ($this->_isObjectCallback($sFunction)) {
			$mReturn = call_user_func_array($this->aObjects[$sFunction], $aArgs);
		}
		else {
			$mReturn = call_user_func_array($sFunction, $aArgs);
		}
		return $mReturn;
	}

	/**
	 * Generates the Javascript wrapper for the specified PHP function.
	 *
	 * @param string $sFunction the name of the function
	 * @param mixed  $sRequestType the request type
	 * @access private
	 * @return string
	 */
	function _wrap(string $sFunction, $sRequestType = XAJAX_POST): string
	{
		return "function ".$this->sWrapperPrefix."$sFunction(){return xajax.call(\"$sFunction\", arguments, ".$sRequestType.");}\n";
	}

	/**
	 * Takes a string containing xajax xjxobj XML or xjxquery XML and builds an
	 * array representation of it to pass as an argument to the PHP function
	 * being called.
	 *
	 * @param string $rootTag the root tag of the XML
	 * @param string $sXml XML to convert
	 * @access private
	 * @return array
	 */
	function _xmlToArray(string $rootTag, string $sXml): array
	{
		$sXml = str_replace("<$rootTag>","<$rootTag>|~|",$sXml);
		$sXml = str_replace("</$rootTag>","</$rootTag>|~|",$sXml);
		$sXml = str_replace("<e>","<e>|~|",$sXml);
		$sXml = str_replace("</e>","</e>|~|",$sXml);
		$sXml = str_replace("<k>","<k>|~|",$sXml);
		$sXml = str_replace("</k>","|~|</k>|~|",$sXml);
		$sXml = str_replace("<v>","<v>|~|",$sXml);
		$sXml = str_replace("</v>","|~|</v>|~|",$sXml);
		$sXml = str_replace("<q>","<q>|~|",$sXml);
		$sXml = str_replace("</q>","|~|</q>|~|",$sXml);

		$this->aObjArray = explode("|~|",$sXml);

		$this->iPos = 0;

		return $this->_parseObjXml($rootTag);
	}

	/**
	 * A recursive function that generates an array from the contents of
	 * $this->aObjArray.
	 *
	 * @param string $rootTag the root tag of the XML
	 * @access private
	 * @return array
	 */
	function _parseObjXml(string $rootTag): array
	{
		$aArray = array();

		if ($rootTag == "xjxobj")
		{
			while(!stristr($this->aObjArray[$this->iPos],"</xjxobj>"))
			{
				$this->iPos++;
				if(stristr($this->aObjArray[$this->iPos],"<e>"))
				{
					$key = "";
					$value = null;

					$this->iPos++;
					while(!stristr($this->aObjArray[$this->iPos],"</e>"))
					{
						if(stristr($this->aObjArray[$this->iPos],"<k>"))
						{
							$this->iPos++;
							while(!stristr($this->aObjArray[$this->iPos],"</k>"))
							{
								$key .= $this->aObjArray[$this->iPos];
								$this->iPos++;
							}
						}
						if(stristr($this->aObjArray[$this->iPos],"<v>"))
						{
							$this->iPos++;
							while(!stristr($this->aObjArray[$this->iPos],"</v>"))
							{
								if(stristr($this->aObjArray[$this->iPos],"<xjxobj>"))
								{
									$value = $this->_parseObjXml("xjxobj");
									$this->iPos++;
								}
								else
								{
									$value .= $this->aObjArray[$this->iPos];
									if ($this->bDecodeUTF8Input)
									{
										$value = $this->_decodeUTF8Data($value);
									}
								}
								$this->iPos++;
							}
						}
						$this->iPos++;
					}

					$aArray[$key]=$value;
				}
			}
		}

		if ($rootTag == "xjxquery")
		{
			$sQuery = "";
			$this->iPos++;
			while(!stristr($this->aObjArray[$this->iPos],"</xjxquery>"))
			{
				if (stristr($this->aObjArray[$this->iPos],"<q>") || stristr($this->aObjArray[$this->iPos],"</q>"))
				{
					$this->iPos++;
					continue;
				}
				$sQuery	.= $this->aObjArray[$this->iPos];
				$this->iPos++;
			}

			parse_str($sQuery, $aArray);
			if ($this->bDecodeUTF8Input)
			{
				foreach($aArray as $key => $value)
				{
					$aArray[$key] = $this->_decodeUTF8Data($value);
				}
			}
		}

		return $aArray;
	}

	/**
	 * Decodes string data from UTF-8 to the current xajax encoding.
	 *
	 * @param string $sData data to convert
	 * @access private
	 * @return string converted data
	 */
	function _decodeUTF8Data(string $sData): string
	{
		$sValue = $sData;
		if ($this->bDecodeUTF8Input)
		{
			$sFuncToUse = NULL;

			// An adaptation for the Dokeos LMS, 22-AUG-2009.
			if (function_exists('api_convert_encoding'))
			{
				$sFuncToUse = "api_convert_encoding";
			}
			//if (function_exists('iconv'))
			elseif (function_exists('iconv'))
			//
			{
				$sFuncToUse = "iconv";
			}
			else if (function_exists('mb_convert_encoding'))
			{
				$sFuncToUse = "mb_convert_encoding";
			}
			else if ($this->sEncoding == "ISO-8859-1")
			{
				$sFuncToUse = "utf8_decode";
			}
			else
			{
				trigger_error("The incoming xajax data could not be converted from UTF-8", E_USER_NOTICE);
			}

			if ($sFuncToUse)
			{
				if (is_string($sValue))
				{
					if ($sFuncToUse == "iconv")
					{
						$sValue = iconv("UTF-8", $this->sEncoding.'//TRANSLIT', $sValue);
					}
					else if ($sFuncToUse == "mb_convert_encoding")
					{
						$sValue = mb_convert_encoding($sValue, $this->sEncoding, "UTF-8");
					}
					// Added code, an adaptation for the Dokeos LMS, 22-AUG-2009.
					else if ($sFuncToUse == "api_convert_encoding")
					{
						$sValue = api_convert_encoding($sValue, $this->sEncoding, "UTF-8");
					}
					//
					else
					{
						$sValue = utf8_decode($sValue);
					}
				}
			}
		}
		return $sValue;
	}

}// end class xajax

/**
 * This function is registered with PHP's set_error_handler() function if
 * the xajax error handling system is turned on.
 */
function xajaxErrorHandler($errno, $errstr, $errfile, $errline)
{
	$errorReporting = error_reporting();
	if (($errno & $errorReporting) == 0) return;

	if ($errno == E_NOTICE) {
		$errTypeStr = "NOTICE";
	}
	else if ($errno == E_WARNING) {
		$errTypeStr = "WARNING";
	}
	else if ($errno == E_USER_NOTICE) {
		$errTypeStr = "USER NOTICE";
	}
	else if ($errno == E_USER_WARNING) {
		$errTypeStr = "USER WARNING";
	}
	else if ($errno == E_USER_ERROR) {
		$errTypeStr = "USER FATAL ERROR";
	}
	else if ($errno == E_STRICT) {
		return;
	}
	else {
		$errTypeStr = "UNKNOWN: $errno";
	}
	$GLOBALS['xajaxErrorHandlerText'] .= "\n----\n[$errTypeStr] $errstr\nerror in line $errline of file $errfile";
}

?>
