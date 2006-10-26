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
* This file is needed because this opens the new window and this keeps the contact with it
* if this reloads, I cannot reach the new window via Javascript
* so this window must stay and never reload
*
* @author   Denes Nagy <darkden@freemail.hu>
* @version  v 0.1
* @access   public
*
*	@package dokeos.scorm
============================================================================== 
*/

$langFile = "scorm";
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

header('Content-Type: text/html; charset='. $charset);

api_session_unregister('items');
unset($items);

?>

<html><head>
<script type='text/javascript'>
/* <![CDATA[ */
	function additem() { 
		if (document.theform.started.value!='yes') { alert("First, click to start to begin browsing !"); return; }
	    if (window.builderwindow && window.builderwindow.open && !window.builderwindow.closed) {
			alert("our little browsi is open, sure");
		} else {
			alert("our little browsi is not found, my godness !");
			return false;
		}
		if (document.theform.starter.value=='dead') { alert("but the starter honey is dead inside");       			 	//s=window.builderwindow.location.toString();
		}
		if (document.theform.starter.value!='dead') { alert("and the starter honey is also alive");                  	//s=window.builderwindow.buildernet.location.toString();
		}
		s=builderwindow.document.location.href;
		  //I can set the builderwindow.location.href, like this :
  		  //builderwindow.location.href="http://www.hvg.hu"; 
		  //BUT I cannot read it, if it is a web-url, not localhost !!!!!!!!!!!!
		alert("browsi's loc : "+s);
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
			document.theform.newaddress.value=s;
		}
		document.theform.submit();
		s="<html><link rel='stylesheet' type='text/css' href='../css/scorm.css'></head><body bgcolor='#EEEEEE'><br><br><br><br>";
		s+="If you want to add more items to your Path, go back to the other browser window, select the desired ";
		s+="page and click on <font color=red>'Add Item'</font> here.</body></html>";
		zwindow=open('','net');
		z=zwindow.document;
		z.write(s);
		z.close();
	}
	function startit() {
		if (document.theform.pathname.value=='') { alert('You MUST give a name first to your Path !'); return false; }
		builderwindow=window.open('scormbuilder.php?open=builderwindow','builderwindow'); //with header frame
		//builderwindow=window.open('http://www.google.com',''); //with no frames
		document.theform.started.value="yes";
	}
/* ]]> */
</script>
<link rel='stylesheet' type='text/css' href='../css/scorm.css'>
</head>

<body bgcolor='#EEEEEE'><form action="scormbuilderbrowse.php" name='theform' target='toc2'><p style='text-align:justify'>

<FIELDSET>
	<LEGEND>Basic</LEGEND>
Give a name to your Path below and click 'Start' button, and a new window will open, where you can browse the web or Dokeos pages. If you find a page interesting, click 'Add Item' below to add it into your Path.<br><div align=center>
	Path name : <input type='text' name='pathname' maxlength='100' size='6'></input><input type='button' onclick="startit()" value="START" /><br />
	<input type='button' onclick="additem()" value="ADD ITEM" />
</FIELDSET>
<input type='hidden' name='newaddress'><input type='hidden' name='newname'>
<input type='hidden' name='action'><input type='hidden' name='starter'><input type='hidden' name='started'>

</div>
</form></body></html>