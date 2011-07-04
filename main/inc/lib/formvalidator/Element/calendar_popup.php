<?php
// $Id: calendar_popup.php 20456 2009-05-10 17:27:44Z ivantcholakov $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

$language_file = 'agenda';
require_once dirname(__FILE__).'/../../../global.inc.php';

//session
if(isset($_GET['id_session']))
	$_SESSION['id_session'] = Security::remove_XSS($_GET['id_session']);

// the variables for the days and the months
// Defining the shorts for the days
$DaysShort = api_get_week_days_short();
// Defining the days of the week to allow translation of the days
$DaysLong = api_get_week_days_long();
// Defining the months of the year to allow translation of the months
$MonthsLong = api_get_months_long();

$iso_lang = api_get_language_isocode($language_interface);

header('Content-Type: text/html; charset='. api_get_system_encoding());

?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $iso_lang; ?>" lang="<?php echo $iso_lang; ?>">
<head>
<title>Calendar</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>">
<style type="text/css">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/<?php echo api_get_setting('stylesheets'); ?>/default.css";
table.calendar {
	width: 100%;
	font-size: 12px;
	font-family: verdana, arial, helvetica, sans-serif;
}
table.calendar .monthyear
{
	background-color: #4171B5;
	text-align: center;
	color: #ffffff;
	font-size:12px;
	padding:5px;
	margin:10px;
}
table.calendar .daynames {
	background-color: #D3DFF1;
	text-align: center;
}
table.calendar td
{
	width: 25px;
	height: 25px;
	background-color: #f5f5f5;
	text-align: center;
}
table.calendar td.selected
{
	border: 1px solid #ff0000;
	background-color: #FFCECE;
}
table.calendar td a
{
	width: 25px;
	height: 25px;
	text-decoration: none;
}
table.calendar td a:hover
{
	background-color: #ffff00;
}

table.calendar td a div
{
	padding:5px;
}

table.calendar td a div:hover
{
	background-color: #ffff00;
}

table.calendar .monthyear a {	
	text-align: center;
	/* color: #ffffff; */
}
table.calendar .monthyear a:hover
{
	text-align: center;
	/*color: #ff0000;*/
	/*background-color: #ffff00;*/
}
/*]]>*/
</style>
<script type="text/javascript">
/* <![CDATA[ */
    /* added 2004-06-10 by Michael Keck
     *       we need this for Backwards-Compatibility and resolving problems
     *       with non DOM browsers, which may have problems with css 2 (like NC 4)
     */
    var isDOM      = (typeof(document.getElementsByTagName) != 'undefined'
                      && typeof(document.createElement) != 'undefined')
                   ? 1 : 0;
    var isIE4      = (typeof(document.all) != 'undefined'
                      && parseInt(navigator.appVersion) >= 4)
                   ? 1 : 0;
    var isNS4      = (typeof(document.layers) != 'undefined')
                   ? 1 : 0;
    var capable    = (isDOM || isIE4 || isNS4)
                   ? 1 : 0;
    // Uggly fix for Opera and Konqueror 2.2 that are half DOM compliant
    if (capable) {
        if (typeof(window.opera) != 'undefined') {
            var browserName = ' ' + navigator.userAgent.toLowerCase();
            if ((browserName.indexOf('konqueror 7') == 0)) {
                capable = 0;
            }
        } else if (typeof(navigator.userAgent) != 'undefined') {
            var browserName = ' ' + navigator.userAgent.toLowerCase();
            if ((browserName.indexOf('konqueror') > 0) && (browserName.indexOf('konqueror/3') == 0)) {
                capable = 0;
            }
        } // end if... else if...
    } // end if
/* ]]> */
</script>
<script type="text/javascript" src="tbl_change.js.php"></script>
<script type="text/javascript">
/* <![CDATA[ */
var month_names = new Array(
<?php
foreach($MonthsLong as $index => $month)
{
	echo '"'.$month.'",';
}
?>
"");
var day_names = new Array(
<?php
foreach($DaysShort as $index => $day)
{
	echo '"'.$day.'",';
}
?>
"");
/* ]]> */
</script>
</head>
<body dir="<?php echo api_get_text_direction(); ?>" onLoad="javascript: initCalendar();">
<div id="calendar_data"></div>
<div id="clock_data"></div>
</body>
</html>
