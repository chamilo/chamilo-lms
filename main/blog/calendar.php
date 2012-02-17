<?php
/* For licensing terms, see /license.txt */
/**
 * Blog calendar
 * @package chamilo.blogs
 */
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = 'agenda';

// including the global
require_once '../inc/global.inc.php';

// the variables for the days and the months
// Defining the shorts for the days
$DaysShort = api_get_week_days_short();
// Defining the days of the week to allow translation of the days
$DaysLong = api_get_week_days_long();
// Defining the months of the year to allow translation of the months
$MonthsLong = api_get_months_long();

?>
<html>
<head>
<title>Calendar</title>

<style type="text/css">
table.calendar
{
	width: 100%;
	font-size: 11px;
	font-family: verdana, arial, helvetica, sans-serif;
}
table.calendar .monthyear
{
	background-color: #4171B5;
	text-align: center;
	color: #ffffff;
}
table.calendar .daynames
{
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
table.calendar .monthyear a
{
	text-align: center;
	color: #ffffff;
}
table.calendar .monthyear a:hover
{
	text-align: center;
	color: #ff0000;
	background-color: #ffff00;
}
</style>
<script type="text/javascript">
<!--
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
//-->
</script>
<script type="text/javascript" src="tbl_change.js"></script>
<script type="text/javascript">
<!--
var month_names = new Array(
<?php
foreach($MonthsLong as $index => $month){
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
//-->
</script>
</head>
<body onLoad="initCalendar();">
<div id="calendar_data"></div>
<div id="clock_data"></div>
</body>
</html>
