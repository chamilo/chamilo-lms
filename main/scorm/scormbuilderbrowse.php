<?php
/*
----------------------------------------------------------------------
Dokeos - elearning and course management software

Copyright (c) 2004 Dokeos S.A.
Copyright (c) Denes Nagy (darkden@freemail.hu)

For a full list of contributors, see "credits.txt".
The full license can be read in "license.txt".

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

See the GNU General Public License for more details.

Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
----------------------------------------------------------------------
*/
/**
============================================================================== 
* The table of contents manager file of the Scorm Builder tool
*
* @author   Denes Nagy <darkden@freemail.hu>
* @version  v 0.1
* @access   public
*	@package dokeos.scorm
============================================================================== 
*/

$langFile = "scorm";
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

header('Content-Type: text/html; charset='. $charset);  ?>

<html><head>
<script type='text/javascript'>
/* <![CDATA[ */
	function additem() { 
		var s=builderwindow.net.location.toString();
		//it took me 1 hour to discover that top.net.location is not a string in itself, and
		//as a consequence, i cannot use indexOf function to that; but no error messages appeared ! never !
		//I hate Javascript !!!
		c=s.charAt(s.length-1);
		if (c=='/') { s=s.slice(0,s.length-1); } //we cut the final '/' if exists
		last=s.lastIndexOf('/',s.length-2);
		amount=s.length-last-1;
		suggestion=s.slice(last+1, s.length);
		answer=prompt('Give a name to this item : ',suggestion);
		if (answer=='') { alert('You cannot add an item without a name !'); return false; }
		if (answer) {
			document.theform.newname.value=answer;
			document.theform.newaddress.value=top.net.location;
		}
		document.theform.submit();
		builderwindow=open(s,'');
	}
	function addchaptertitle() { 
		answer=prompt('Write in the chapter title : ','');
		if (answer=='') { alert('You cannot add an empty chapter title !'); return false; }
		if (answer) {
			document.theform.newname.value=answer;
			document.theform.newaddress.value='';
		}
		document.theform.submit();
	}
/* ]]> */
</script>
<link rel='stylesheet' type='text/css' href='../css/scorm.css' />
</head>

<body bgcolor='#EEEEEE'>
 <form action="<?php echo $_SERVER['PHP_SELF']; ?>" name='theform'>
  <div align='left'>
   <FIELDSET>
	   <LEGEND>Other building tools</LEGEND>
	   <br />
	&nbsp;<a href='#' onclick="addchaptertitle()" class='menu'>Add chapter title</a><br>
	&nbsp;<a href='#' onclick="javascript:document.theform.action.value='clear';document.theform.submit();"; class='menu'>Clear Path</a><br />
	   <br />
   </FIELDSET>
   <br />
  </div>
  <div align='center'>
<?php
echo	"</div>",
		"<input type='hidden' name='newaddress'><input type='hidden' name='newname'><input type='hidden' name='action'></form>";

if (($newaddress != '') || ($newname != '')) {
	$newelement['url']=$newaddress; $newelement['name']=$newname; $items[]=$newelement;
}
if (($move == 'up') and ($index != 0)) {
	$temp=$items[$index-1];
	$items[$index-1]=$items[$index];
	$items[$index]=$temp;
}
if (($move == 'down') and ($index != count($items)-1)) {
	$temp=$items[$index+1];
	$items[$index+1]=$items[$index];
	$items[$index]=$temp;
}
if ($delete != '') {
	array_splice($items,$delete,1);
}
if ($action=='clear') {
	api_session_unregister('items');
	unset($items);
	api_session_register('items');
}
echo "<table width='245' cellpadding='0' cellspacing='0' border='0'>";
for ($i=0; $i<count($items); $i++) {
	echo "<tr><td>";
	if ($items[$i]['url'] != '') {
		echo "&nbsp;&nbsp;<a href='{$items[$i]['url']}' target='net'>{$items[$i]['name']}</a>";
	} else {
		echo "{$items[$i]['name']}";
	}
	echo "</td><td>";
	if ($i != 0) { echo "<a href='".$_SERVER['PHP_SELF']."?move=up&index=$i'><img src='../img/up.gif' border='0' alt='Move up'></a>"; }
	echo "</td><td>";
	if ($i != count($items)-1) { echo "<a href='".$_SERVER['PHP_SELF']."?move=down&index=$i' alt='Move down'><img src='../img/down.gif' border=0></a>"; }
	echo "</td><td><a href='".$_SERVER['PHP_SELF']."?delete=$i'><img src='../img/delete.gif' border='0' alt='Delete'></a><td>",
	"<img src='../img/scormpre.gif' border=0 alt='Prerequirements'>",
	"</td></tr>";
}
echo "</table>";
//print_r($items);
?>
</body></html>