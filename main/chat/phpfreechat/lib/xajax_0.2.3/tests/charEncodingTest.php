<?php
require_once("../xajax.inc.php");

function setOptions($formData)
{
	$_SESSION['useEncoding'] = $formData['useEncoding'];
	$_SESSION['htmlEntities'] = (boolean)$formData['htmlEntities'];
	$_SESSION['decodeUTF8'] = (boolean)$formData['decodeUTF8'];
	$objResponse = new xajaxResponse();
	$objResponse->addAlert("Your options have been saved.");
	return $objResponse;
}

function testForm($formData, $strText)
{
	global $useEncoding, $htmlEntities;
	$objResponse = new xajaxResponse($useEncoding, $htmlEntities);
	$objResponse->addAlert("formData: " . print_r($formData, true) . $strText);
	$objResponse->addAssign("submittedDiv", "innerHTML", nl2br(print_r($formData, true)) . '<br /><br />' . $strText);
	return $objResponse->getXML();
}

$useEncoding = "UTF-8";
$htmlEntities = false;
$decodeUTF8 = false;

session_start();
session_name("xajaxCharEncodingTest");

if (@$_GET['refresh'] == "yes") {
	session_destroy();
	header("location: charEncodingTest.php");
	exit();
}

if (isset($_SESSION['useEncoding'])) {
	$useEncoding = $_SESSION['useEncoding'];	
}
if (isset($_SESSION['htmlEntities'])) {
	$htmlEntities = $_SESSION['htmlEntities'];	
}
if (isset($_SESSION['decodeUTF8'])) {
	$decodeUTF8 = $_SESSION['decodeUTF8'];	
}

$xajax = new xajax();
$xajax->setCharEncoding($useEncoding);
if ($htmlEntities) {
	$xajax->outputEntitiesOn();	
}
if ($decodeUTF8) {
	$xajax->decodeUTF8InputOn();
}
//$xajax->debugOn();
$xajax->registerFunction("setOptions");
$xajax->registerFunction("testForm");
$xajax->processRequests();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Character Encoding Test | xajax Tests</title>
<?php $xajax->printJavascript("../") ?>
</head>
<body>

<h2><a href="index.php">xajax Tests</a></h2>
<h1>Character Encoding Test</h1>

<h2>Options Form</h2>

<p><strong>NOTE:</strong> if you change these options, make sure you click the Save Options button or the options won't be used.</p>

<form id="optionsForm" onsubmit="return false;">
<p>Encoding: <input type="text" value="<?php echo $useEncoding ?>" name="useEncoding" /><br />
Output HTML Entities? <input type="radio" name="htmlEntities" value="1" <?php if ($htmlEntities) echo ' checked="checked"' ?>/> Yes
 <input type="radio" name="htmlEntities" value="0" <?php if (!$htmlEntities) echo ' checked="checked"' ?>/> No<br />
Decode UTF-8 Input? <input type="radio" name="decodeUTF8" value="1" <?php if ($decodeUTF8) echo ' checked="checked"' ?>/> Yes
 <input type="radio" name="decodeUTF8" value="0" <?php if (!$decodeUTF8) echo ' checked="checked"' ?>/> No<br />
<p><input type="submit" value="Save Options" onclick="xajax_setOptions(xajax.getFormValues('optionsForm')); return false;" /></p>
</form>

<p><a href="charEncodingTest.php?refresh=yes">Clear and Refresh</a></p>

<h2>Text Test Form</h2>

<p><a href="http://www.i18nguy.com/unicode-example.html" target="_blank">Here are some Unicode examples</a> you can paste into the text box below. You can see <a href="http://www.unicode.org/iuc/iuc10/languages.html" target="_blank">more examples and a list of standard encoding schemes here</a>.</p>

<form id="testForm1" onsubmit="return false;">
<p><input type="text" value="Enter test text here" id="textField1" name="textField1" size="60" /></p>
<p><input type="submit" value="Submit Text" onclick="xajax_testForm(xajax.getFormValues('testForm1'),xajax.$('textField1').value); return false;" /></p>
</form>

<div id="submittedDiv"></div>

</body>
</html>