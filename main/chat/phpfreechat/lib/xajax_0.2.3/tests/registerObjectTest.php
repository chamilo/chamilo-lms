<?php
require_once("../xajax.inc.php");

class myObjectTest {
	var $myNumber = 42;
	function testInstanceMethod($formData)
	{
		$objResponse = new xajaxResponse();
		$objResponse->addAlert("My object number is: {$this->myNumber}");
		$objResponse->addAlert("formData: " . print_r($formData, true));
		$objResponse->addAssign("submittedDiv", "innerHTML", nl2br(print_r($formData, true)));
		return $objResponse->getXML();
	}
	function testClassMethod($formData)
	{
		$objResponse = new xajaxResponse();
		$objResponse->addAlert("This is a class method.");
		$objResponse->addAlert("formData: " . print_r($formData, true));
		$objResponse->addAssign("submittedDiv", "innerHTML", nl2br(print_r($formData, true)));
		return $objResponse->getXML();
	}
}

$xajax = new xajax();
//$xajax->debugOn();
$myObj = new myObjectTest();
$myObj->myNumber = 50;
$xajax->registerFunction(array("testForm", &$myObj, "testInstanceMethod"));
$xajax->registerFunction(array("testForm2", "myObjectTest", "testClassMethod"));
$myObj->myNumber = 56;
$xajax->processRequests();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Register Object Test | xajax Tests</title>
<?php $xajax->printJavascript("../") ?>
</head>
<body>

<h2><a href="index.php">xajax Tests</a></h2>
<h1>Register Object Test</h1>

<form id="testForm1" onsubmit="return false;">
<p><input type="text" id="textBox1" name="textBox1" value="This is some text" /></p>
<p><input type="submit" value="Submit to Instance Method" onclick="xajax_testForm(xajax.getFormValues('testForm1')); return false;" /></p>
<p><input type="submit" value="Submit to Class Method" onclick="xajax_testForm2(xajax.getFormValues('testForm1')); return false;" /></p>
</form>

<div id="submittedDiv"></div>

</body>
</html>