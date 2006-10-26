<?php // $id: $
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
*	@package dokeos.scorm
*   Opens a scorm lesson
============================================================================== 
*/

//Maritime :if you open the first, done it, then the second, then the first and not done it and then the second, then //your access is denied (the scos behave this way) !!
//error_log($_SERVER['REQUEST_URI'],0);
$langFile = "scorm";
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include('XMLencode.php');
$charset = GetXMLEncode($_GET['file']);
header('Content-Type: text/html; charset='. $charset);

//error_log("Opensco start... s_identifier is now:".$_SESSION['s_identifier']." and old s_identifier is now:".$_SESSION['old_sco_identifier'],0);


//$TBL_SCORM_SCO_DATA=$scormDbName."`.`scorm_sco_data";
$TBL_SCORM_SCO_DATA 	= Database::get_scorm_sco_data_table();
$sco_href	= base64_decode($_GET['sco_href']);
$sco_identifier = $_GET['sco_identifier'];
$file 		= $_GET['file'];
$edoceo 	= $_GET['edoceo'];
$items 		= $_SESSION['items'];
$_uid		= $_SESSION['_uid'];
$contentId	= $_SESSION['contentId'];
$openDir = $_REQUEST['openDir'];
//prepare those variables for scormfunctions.php
$_SESSION['file']=$file;
$charset_lang = 'ISO-8859-15';
/*
 * Small utility function to position the menu on a sensible item (several elements before the current one)
 * @param string	sco_id of the target element
 * @param array		reference to the items array
 * @param array		reference to the sco_id inverse-index array
 * @param integer Optional number of elements we want before the selected element
 * @return string		sco_id (and HTML anchor ID) of the element to which the menu should point
 * @author	Yannick Warnier <yannick.warnier@dokeos.com> 
 *
function get_better_anchor_target($my_id, &$my_items, &$my_items_dictionary, $num = 6){
	$k = $my_items_dictionary[$my_id];
	if( ( $k<$num ) || (!is_array($my_items[$k-$num]))){
		return $my_items[1]['identifier'];
	}else{
		return $my_items[$k-$num]['identifier'];
	}
	return $my_items[1]['identifier'];
}
Function apparently useless to call from here
*/

if(!is_array($items)){
	//error_log('$items is not an array!!!',0);
}

//the problem is that it is not sure that the closesco.php is called, maybe not, the user can click to any other sco
//without finishing the current one, that is why that this code looks ugly

/*======================================
  SEARCHING FOR PREREQUISITIES OF COURSE
  ======================================*/

//$i=0;
$i=$_SESSION['items_dictionary'][$sco_identifier];
//do {
//  $i++;
//} while (($items[$i]['identifier'] != $sco_identifier) and ($i <= count($items)));
//while goes on if it is true, it stops at false
//we search the prerequisites of the current course (now only working for scorm 1.2 ; scorm 1.3 use another method)
$prereq=$items[$i]['prereq'];
$title=$items[$i]['title'];
//$i=0;
$i=$_SESSION['items_dictionary'][$prereq];
//do {
//  $i++;
//} while (($items[$i]['identifier'] != $prereq) and ($i <= count($items)));
$prereqtitle=$items[$i]['title'];
if ($prereq != '') {
	$result = api_sql_query("SELECT status FROM $TBL_SCORM_SCO_DATA 
		WHERE (scoIdentifier='$prereq' and studentId='$_uid' and contentId='$contentId')",__FILE__,__LINE__);
	//echo "SELECT status FROM `$TBL_SCORM_SCO_DATA` WHERE (scoIdentifier='$prereq' and studentId='$_uid' and contentId='$contentId')";
	$ar=mysql_fetch_array($result);
	$status=$ar['status'];
	if ($status=='completed' or $status='passed') 	{ $openpage=true; }	else { $openpage=false; }
} else {
    $openpage=true;
}

/*==============================
  OPENING THE PAGE IF ACCESSIBLE
  ==============================*/

if ($openpage) {
	api_session_unregister('s_href');
	//api_session_unregister('s_identifier');
	//unset($_SESSION['s_identifier']);
	
	//If the user clicked on an item directly in the menu, then we didn't pass through
	//content.php, and consequently $_SESSION['s_identifier'] has not been renewed.
	//It is important to reset $_SESSION['s_identifier'] and $_SESSION['old_sco_identifier']
	//before loading scormfunctions.php, which will use them to call closesco.php
	if($sco_identifier != $_SESSION['s_identifier']){
		$_SESSION['s_identifier'] = $sco_identifier;
		$_SESSION['old_sco_identifier'] = $sco_identifier;
	}

	$s_href=$sco_href;
	//$_SESSION['s_identifier']=$sco_identifier;

	//check that file actually exists on this system (or if it doesn't start with the domain name
	// of this system, leave it to the risk of generating a crappy apache list page - we cannot be sure)
	$my_href = str_replace(api_get_path(WEB_PATH),api_get_path(SYS_PATH),$sco_href);
	if(($my_href == $sco_href) or (is_file(preg_replace('/(.*)\?.*/','$1',$my_href)))){
		//ok
	}else{
		$sco_href = 'blank.php';
	}
	
	api_session_register('s_href');
	//api_session_register('s_identifier');

	$pos=strpos($sco_href,'http:',5);   //in case of external sco_hrefs, we cut the Dokoes-related part off
	if ($pos === false) {
	} else {
       		$sco_href=substr($sco_href,$pos);
	}

	echo "<html><head><link rel='stylesheet' type='text/css' href='../css/scorm.css'></head><body>",
		"<script type='text/javascript'>\n/* <![CDATA[ */\n";
		//ok that the session has the s_href and the s_identifier but the scormfunction.php ran before this and
		//these new variables are not written in the hidden scormfunction.php in the hidden frame, so we must
		//refresh it in order to be able to use these variables ! here is the refresh :
	if ($edoceo=="no") {
		echo
		"apiwindow=open('scormfunctions.php','API');",
		"api1484window=open('scormfunctions.php','API_1484_11');";
	}
	//$target_identifier = get_better_anchor_target($sco_identifier, $_SESSION['items'], $_SESSION['items_dictionary'],6);
	echo
		"zwindow=open('$sco_href','sco');",
		"cwindow=open('contents.php?file=$file&edoceo=$edoceo&openDir=$openDir','contents');",
//		"parent.load.document.location=parent.load.document.location;",
		"\n/* ]]> */\n</script>",
		"<div class='message'><font color='black'>$title</font></div></body></html>";
		//error_log("Opensco ending... s_identifier is now:".$_SESSION['s_identifier']." and old s_identifier is now:".$_SESSION['old_sco_identifier'],0);

} else {
		//ugly patch with htmlentities here for the accentuated characters bug. See contents.php for a tip on how
		// to improve this.
	  echo "<html><head><link rel='stylesheet' type='text/css' href='../css/scorm.css'></head><body>",
	  "<div class='message_thin'>".htmlentities(get_lang('ScormToEnter'),ENT_QUOTES,$charset_lang)
		  ." <b>$title</b> ".htmlentities(get_lang('ScormFirstNeedTo'),ENT_QUOTES,$charset_lang)
		  ." <b>$prereqtitle</b></div>.</body></html>";
		//error_log("Opensco ending (prereqs)... s_identifier is now:".$_SESSION['s_identifier']." and old s_identifier is now:".$_SESSION['old_sco_identifier'],0);
 
}

?>
