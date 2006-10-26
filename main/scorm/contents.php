<?php //$id: $
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
*	Show the table of contents of a scorm-based tutorial
*
* This script is a double-pass script. When called, it initialises stuff
* (generally depending on $menu or $openfirst parameters), then calls
* the opensco.php script which opens the content in another frame and
* calls this script back without any action parameter.
*	@author   Denes Nagy <darkden@freemail.hu>
*	@author   Yannick Warnier <yannick.warnier@dokeos.com>
*	@access   public
*
*	@package dokeos.scorm
*
============================================================================== 
*/

//Initialisation includes
$langFile = "scorm";
require('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include_once(api_get_path(LIBRARY_PATH).'database.lib.php');
include('XMLencode.php');
include('scormparsing.lib.php');

//error_log($_SERVER['REQUEST_URI']."---menu=".$_POST['menu'],0);
//error_log("---Script started. s_identifier is now:".$_SESSION['s_identifier']." and old s_identifier is now:".$_SESSION['old_sco_identifier'],0);

//Parameters reception
$edoceo			= $_GET['edoceo'];
$openDir		= $_REQUEST['openDir'];
$menu					= $_REQUEST['menu'];
$openfirst	= $_REQUEST['openfirst'];
//$request_file must be like this : http://localhost/root/Dokeos/Scorm_content1/imsmanifest.xml
//with SERVER_NAME (=127.0.0.1) it does not work
$request_file		= $_REQUEST['file'];
// Check if the requested file is in the right location (scorm folder in course directory and no .. in the path)
$file_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/scorm';
if(substr($request_file,0,strlen($file_path)) != $file_path || strpos($request_file,'..') > 0)
{
	api_not_allowed();
}
//$s_identifier		= $_SESSION['s_identifier'];
$_uid					= $_SESSION['_uid'];

//Charset settings (very important for imported contents)
$charset = GetXMLEncode($_GET['file']);
header('Content-Type: text/html; charset='. $charset);
//The following charset describes the encoding of most language files
//For further enhancements and in order to ensure internationalisation, we
//should make every language file UTF-8, or adapt get_lang to be able to sort
//out the file encoding and use htmlentities if required.
//If using unicode characters (courses in non-european alphabets), change this
//to $charset_lang = 'UTF-8';
$charset_lang = 'ISO-8859-15';

//Latest language inits
$array_status=array(
					'completed' => get_lang('ScormCompstatus'),
					'passed' => get_lang('ScormPassed'),
					'failed' => get_lang('ScormFailed'),
					'incomplete' => get_lang('ScormIncomplete'),
					'not attempted' => get_lang('ScormNotAttempted')
);


//Database table names init
$TBL_SCORM_MAIN     = Database :: get_scorm_table(SCORM_MAIN_TABLE);
$TBL_SCORM_SCO_DATA = Database::get_scorm_sco_data_table();

//if the last sco visited hasn't been closed, mark it complete:
if(!empty($_SESSION['last_sco_closed']) && !empty($_SESSION['old_sco_identifier'])){
	if($_SESSION['last_sco_closed'] != $_SESSION['old_sco_identifier']){
		$sql = "SELECT status FROM $TBL_SCORM_SCO_DATA 
			WHERE contentId='".$_SESSION['contentId']."' 
			and scoIdentifier='".$_SESSION['old_sco_identifier']."' 
			and studentId='".$_SESSION['_uid']."'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		if(mysql_num_rows($result)==1){
			$my_row = mysql_fetch_row($result);
			$my_status=$my_row[0];
			if($my_status == 'not attempted'){
				$sql = "UPDATE $TBL_SCORM_SCO_DATA 
					SET score='0', status='completed', time='00:00' 
					WHERE (studentId='".$__SESSION['_uid']."' 
						and scoIdentifier='".$_SESSION['old_sco_identifier']."' 
						and contentId='".$_SESSION['contentId']."')";
				$result = api_sql_query($sql,__FILE__,__LINE__);
				$_SESSION['last_sco_closed'] = $_SESSION['old_sco_identifier'];
			}
		}
	}
}

//Backup latest SCO-identifier opened (to use as reference to the last element seen in closesco.php) 
//This operation needs only to be done when the user takes an action, not on automatic reloading
//of the script. This is checked with the $menu parameter (set on user actions)

$_SESSION['old_sco_identifier'] = $_SESSION['s_identifier'];

//Special condition to avoid buggy database refresh in case of restart (see closesco.php)
if($_SESSION['just_started']==true){
	//error_log("Content was just started - prevent saving last element",0);
	$_SESSION['dont_save_last']=true;
	$_SESSION['just_started']=false;
}else{
	//error_log("Content was not just started - allow saving last element",0);
	$_SESSION['dont_save_last']=false;
}


//Ensure the items array (TOC) and other session variables are really erased on first visit or on restart
if ($openfirst == 'yes' or $menu == 'restart'){
	//error_log("Openfirst or restart was set (unset session[s_identifier] and session[old_sco_identifier]",0);
	//api_session_unregister('items');
	$_SESSION['items'] = array();
	$items = array();
	$_SESSION['items_dictionary'] = array();
	$items_dictionary = array();
	$_SESSION['defaultorgtitle'] = '';
	$defaultorgtitle = '';
	$_SESSION['contentId'] = '';
	if($menu == 'restart'){
		$_SESSION['just_started']=true;
	}
	unset($_SESSION['s_identifier']);
	unset($_SESSION['sco_identifier']);
	unset($_SESSION['old_sco_identifier']);
}else{
	//error_log("Openfirst or restart was NOT set",0);
	$items = $_SESSION['items'];
	$defaultorgtitle = $_SESSION['defaultorgtitle'];
	if(!empty($menu)){
		$_SESSION['just_started']=false;
	}
}

//HTML start

$output = '<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/scorm.css">
</head>
<body>';

//incoming variables :
//- $request_file
//- $tabledraw -> if true, then the program draws a help table for showing imsmanifest.xml tags (technical)


// More init (display tweaks)
$tabledraw=false;  //if true, then technical information is shown
$wrap=true; //if false then the toc is not in a table, if true, then it is
$tablewidth=255;  //this is the width of the content tables

$defaultorgref='';
$version='0.0';
$inorg=false; //whether we are inside an organisation or not
$intitle=$inmeta=$ingeneral=false;
$inversion=false;
$prereq=false;
$initem=0; //how deep we are in the hieracrchy of itmes
$itemindex=0;
$previouslevel=1;
$clusterinfo=0;

$exit_after_menu = false; //used to disable printing of javascript redirections in output.

/**
 * This function displays a message in the message frame.
 *
 * For some reason (apparently JavaScript reasons), the message shown 
 * cannot be more than 66 characters. Together with the enveloppe, it 
 * gets to something around 250 characters, which apparently is too 
 * big for the "write" method 
 * @param		string	The message to show. Should be smaller than 66 characters.
 * @return void	Doesn't return anything, just writes to the message frame.
 */
function message($text) {
	//ugly little patch for text encoding of accentuated characters - see other comments on 'accentuated' characters
	// and improvements to this patch below in this file
	#global $charset_lang;
	#$text = htmlentities($text,ENT_QUOTES,$charset_lang);
	$string = "<script type='text/javascript'>\n/* <![CDATA[ */\n".
		"zwindow=open('','message');".
		"s='<html><head><link rel=\"stylesheet\" type=\"text/css\" href=\"../css/scorm.css\"></head><body>".
		"<div class=\"message\">$text</div></body></html>';".
		"z=zwindow.document;".
		"z.write(s);".
		"z.close();".
		"\n/* ]]> */\n</script>";
	return $string;
}



/****************************************** PROCESSING **********************************/
//Real processing starts here

//1. Check whether we need to build the TOC ($items array) or if it already exists
if($openfirst=='yes' or $menu=='restart'){
	//error_log("Openfirst or restart was set (generate $items)",0);

	/*====================
	  PARSING THE XML FILE
	  ====================*/
	//1.1
	if (!(list($xml_parser, $fp) = new_xml_parser($request_file))) {
	   die("<font color='red'>Error : could not open XML input - $request_file</font>");
	}
	
	//my variables : $xml_parser, new_xml_parser()
	
	if ($tabledraw) {
	  $output .= "<table border='0'>"
			."<tr>"
				."<td>Row</td>"
				."<td>Inorg</td>"
				."<td>Initem</td>"
				."<td>Tag name</td>"
				."<td>Attributes</td>"
			."</tr>";
	}

	while ($data = fread($fp, 4096)) {  //reads the file in 4096 byte amounts
	   if (!xml_parse($xml_parser, $data, feof($fp))) {
		   die(sprintf("XML error: %s at line %d\n",
					   xml_error_string(xml_get_error_code($xml_parser)),
					   xml_get_current_line_number($xml_parser)));
	   }
	}
	if ($tabledraw) {
	  $output .= "</table>";  
	  $output .= "parse complete<br /><hr />";  
	  $output .= "<br />Total lines in xml file : ";
	  $output .= xml_get_current_line_number($xml_parser);  
	  $output .= " (xml_get_current_line_number)";  
	  $output .= "<br /><br />";
	}

	//build an index of items (item identifier => item index in $items). This should improve parsing speed.
	foreach($items as $key => $content){
		$items_dictionary[$content['identifier']] = $key;
	}
	$_SESSION['items_dictionary'] = $items_dictionary;
	
	//As we asked to start from the beginning, set the current identifier to the first elem's
	$i=1;
	for(;$items[$i]['href']=='';$i++){
		/*do nothing (get the index of the first item of href<>'')*/
	}
	$_SESSION['s_identifier'] = $items[$i]['identifier'];
	$href=api_get_path('WEB_COURSE_PATH').$_course['path']."/scorm".$openDir."/".$items[$i]['href'];
	//error_log("New s_identifier calculated: ".$_SESSION['s_identifier']." - Old one is: ".$_SESSION['old_sco_identifier'],0);

}



//2. Compute the maximum depth level ot the TOC.
//		For fixed width : width=$tablewidth (in two tables) and enable nbsp substitution in two places
// 	$items is a result of the imsmanifest.xml parsing
$maxlevel=1;
$i=1;

while ($items[$i]) {
  if (($items[$i]['level'])>$maxlevel) { $maxlevel=$items[$i]['level']; }
  $i++;
}



//3. Check if the content was ever opened or not, 
//			If not, add a line to scorm_main with the title and course code to record its existence
$sql = "SELECT dokeosCourse FROM $TBL_SCORM_MAIN WHERE "
			." (contentTitle='".$openDir."' and dokeosCourse='".$_course['official_code']."')";
$result = api_sql_query($sql,__FILE__,__LINE__);
$numrows = mysql_num_rows($result);

if ($numrows==0) { //this means that this Scorm content was never opened by anyone in this dokeos course
	//$result = api_sql_query("SELECT MAX(contentId) FROM $TBL_SCORM_MAIN");
	//$ar = mysql_fetch_array($result);
	//$maxcontentId = $ar['MAX(contentId)']+1;
	$sql = "INSERT INTO $TBL_SCORM_MAIN (contentTitle, dokeosCourse) VALUES "
			." ('".$openDir."','".$_course['official_code']."')";
	$result = api_sql_query($sql,__FILE__,__LINE__);
}

//4. Get the contentId for this content (whether it has just been inserted or not) to check if the
//			current user ever opened it.
$sql = "SELECT contentId FROM $TBL_SCORM_MAIN "
        ." WHERE (contentTitle='".$openDir."' and dokeosCourse='".$_course['official_code']."')";

$result = api_sql_query($sql,__FILE__,__LINE__);
$ar = mysql_fetch_array($result);
$mycontentId = $ar['contentId']; //it has to exist, because the previous step checked it existed and if not inserted it
$_SESSION['contentId'] = $mycontentId;

$everopened = true;
$numrows2 = 0; 
$result2='';
$sql2 = "SELECT * FROM $TBL_SCORM_SCO_DATA WHERE (studentId='$_uid' and contentId='$mycontentId')";
$result2 = api_sql_query($sql2,__FILE__,__LINE__);
$numrows2 = mysql_num_rows($result2);

if ($numrows2 == 0) { //this means that this Scorm content was not opened by the current student
	$everopened = false;
}


/*if($openfirst=='yes' or $menu='restart'){
	$s_identifier = $items[1]['identifier'];
	$href=api_get_path('WEB_COURSE_PATH').$_course['path']."/scorm".$openDir."/".$items[1]['href'];
	$output .= "<script type='text/javascript'>",
		"nextwindow=open('opensco.php?sco_href=$href&sco_identifier=$s_identifier&file=$request_file&edoceo=$edoceo&openDir=$openDir','message');",
		"</script>";
}*/



/*=========================
  IF RESTART WAS CLICKED...
  =========================*/
//5 If the user clicked on "restart", the $items array has been cleaned 
// and no element has any special mark whatsoever, except the first one which is highlighted
if ( $menu == 'restart' ) { //Restrart clicked
	//error_log("Restart was set...",0);
	if ( $version == '1.1' ) {
		//5.1.a Ignore if version of SCORM is <1.2 (or that's basically what is intended)
		//display warning message in message frame (side-menu bottom)
		$output .= message(htmlentities(get_lang('ScormNoStatus'),ENT_QUOTE,$charset_lang)); 
	} else {
		//error_log("Update database for restart",0);
		//5.1.b.1 Loads a blank page in the "sco" frame, probably to enable scorm API reinit
		//$output .= "<script type='text/javascript'>
		//	xwindow=open('about:blank','sco');
		//	</script>";
		
		//5.1.b.2 update the status for the current user and content in the SCORM data table
		$sql = "UPDATE $TBL_SCORM_SCO_DATA SET score='0', status='not attempted', time='00:00' "
							." WHERE (studentId='$_uid' and contentId='$mycontentId')";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		//5.1.b.3 clean session vars about this content
		api_session_unregister('s_href');
		$i=1;
		for(;$items[$i]['href']=='';$i++){/*do nothing, just get the index of first elem with href*/}
		$_SESSION['s_identifier'] = $items[$i]['identifier'];
		//error_log("New s_identifier generated: ".$_SESSION['s_identifier'],0);
		//api_session_unregister('s_identifier');
		//5.1.b.4 display info message in message frame (side-menu bottom)
		//$output .=message("<img src=\'../img/restart.jpg\' alt='restart'/>".get_lang('ScormRestarted'));//the img tag fails because of the htmlentities() call in message()
		//$output .=message(htmlentities(get_lang('ScormRestarted'),ENT_QUOTES,$charset_lang));
	}
 //that's all for the special "restart" treatment, although it includes loading the scorm document - see rest of script

}elseif (($menu=='prev') or ($menu=='next')) {
	//error_log("Menu was 'prev' or 'next'",0);

	/*==============================
	  IF PREV OR NEXT WAS CLICKED...
	  ==============================*/
	//5.2.15 If 'previous' or 'next' were clicked, then we might need to reload this script in order
	// to gain benefits of the database updates before we display the TOC
	
	if ($version=='1.3') { 
		$output .=message('Sequencing for 1.3 not yet available'); 
	}
	else 
	{
		// if the current identifier is empty, set to first item identifier
		if (empty($_SESSION['s_identifier'])) { 
			$i=1;
			for(;$items[$i]['href']=='';$i++){/*get index of first elem with href*/}
			$_SESSION['s_identifier']=$items[$i]['identifier'];	
			//error_log("s_identifier was empty - new one: ".$_SESSION['s_identifier'],0);
			
		}
		
		$i=1; 
		if($openfirst == 'yes'){
		 //do nothing so that we start on first element
		 //$s_identifier = $items[1]['identifier'];
			//error_log("Openfirst was set, found first valid elem: ".$items[$i]['identifier'],0);
			for(;$items[$i]['href']=='';$i++){/*get index of first elem with href*/}
		}else{
			$i = $items_dictionary[$_SESSION['s_identifier']];
			
			if ($menu=='next') {
				//error_log("Menu was 'next'",0);

				do {  // we take the next sco which has a href
					$i++;
				} while (($items[$i]['href'] == '') and ($i <= count($items)));
				if ($i>count($items)) { 
					$output .=message(htmlentities(get_lang('ScormNoNext'),ENT_QUOTES,$charset_lang)); 
					#echo $output; 
					$exit_after_menu=true; 
				}
			}
			if ($menu=='prev') {
				//error_log("Menu was 'prev'",0);
				do {  // we take the previous sco which has href
					$i--;
				} while (($items[$i]['href'] == '') and ($i >= 1));
				if ($i<1) { 
					$output .=message(htmlentities(get_lang('ScormNoPrev'),ENT_QUOTES,$charset_lang)); 
					#echo $output; 
					$exit_after_menu=true; 
				}
			}
		}
		//sco opening begin
		$href=api_get_path('WEB_COURSE_PATH').$_course['path']."/scorm".$openDir."/".$items[$i]['href'];
		$_SESSION['s_identifier'] = $items[$i]['identifier'];
		//error_log("New s_identifier: ".$_SESSION['s_identifier']." - Old one is: ".$_SESSION['old_sco_identifier'],0);
		//$s_identifier = $identifier;	
	}
}elseif ($menu=='my_status') {
	//error_log("Menu was 'my_status'",0);

/*===========================
  IF MY STATUS WAS CLICKED...
  ===========================*/
//6. The user chose to display the status screen. 
// @todo It is a very bad idea to mix status display and the rest of the TOC display.
//  Ideally, this should be fixed by including here two different display scripts depending on what to display.
	
	//allows to not highlight an element if currently displaying status
	$_SESSION['displaying_status']=true;
	
	if ($version=='1.1') { 
		$output .=message(htmlentities(get_lang('ScormNoStatus'),ENT_QUOTES,$charset_lang)); 
	} else {
		$w=$tablewidth-20;
		$output .= "<br />";
	
		//if display in fullscreen required
		if (strcmp($_GET["fs"],"true")==0)
		{ $output .= "<table align='center'>"; }

		else
		{ $output .= "<table class='margin_table'>"; }
		
		$output .="<tr><td><div class='title'>".htmlentities(get_lang('ScormMystatus'),ENT_QUOTES,$charset_lang)."</div></td></tr>"
			."<tr><td>&nbsp;</td></tr>"
			."<tr><td>"
				."<table border='0' class='data_table'><tr>\n"
					."<td><div class='mystatusfirstrow'>".htmlentities(get_lang('ScormLessonTitle'),ENT_QUOTES,$charset_lang)."</div></td>\n"
					."<td><div class='mystatusfirstrow'>".htmlentities(get_lang('ScormStatus'),ENT_QUOTES,$charset_lang)."</div></td>\n"
					."<td><div class='mystatusfirstrow'>".htmlentities(get_lang('ScormScore'),ENT_QUOTES,$charset_lang)."</div></td>\n"
					."<td><div class='mystatusfirstrow'>".htmlentities(get_lang('ScormTime'),ENT_QUOTES,$charset_lang)."</div></td></tr>\n";

		//going through the items using the $items[] array instead of the database order ensures
		// we get them in the same order as in the imsmanifest file, which is rather random when using
		// the database table
		foreach($items as $i => $myitem ) {
			$sql="SELECT * FROM $TBL_SCORM_SCO_DATA 
				WHERE (studentId='$_uid' 
				AND contentId='".$mycontentId."' 
				AND scoIdentifier = '".$myitem['identifier']."')";// order by scoId";
			$result = api_sql_query($sql,__FILE__,__LINE__);
			$ar = mysql_fetch_array($result);

			$counter++;
			if (($counter % 2)==0) { $oddclass="row_odd"; } else { $oddclass="row_even"; }

			$lesson_status=$ar['status'];
			$score=$ar['score'];
			$time=$ar['time'];
			$scoIdentifier=$ar['scoIdentifier'];
			$title=$myitem['title'];
			if (strlen($title)>65) {
				$title=substr($title,0,64);
				$title.="...";
			}
			//$title=str_replace(' ','&nbsp;',$title);
			//Remove "NaN" if any (@todo: locate the source of these NaN)
			$time = str_replace('NaN','00',$time);
			if (($lesson_status=='completed') or ($lesson_status=='passed')) { $color='green'; } else { $color='black'; }
			$output .= "<tr class='$oddclass'>\n"
						."<td><div class='mystatus'>$title</div></td>\n"
						."<td><font color='$color'><div class='mystatus'>".htmlentities($array_status[$lesson_status],ENT_QUOTES,$charset_lang)."</div></font></td>\n"
						."<td><div class='mystatus' align='center'>".($score==0?'-':$score)."</div></td>\n"
						."<td><div class='mystatus'>$time</div></td>\n"
					."</tr>\n";
		}

		$output .= "</table></td></tr></table>";
	}
	$output .= "</body></html>";
	//We just wanted the status, so skip the rest now (we don't need any footer anyway)
	echo $output;
	exit();
}
//echo "<pre>".print_r($items,true)."</pre>";
//if the action requested is any of restart, openfirst, next or prev, open the content in the content frame
//This is done by calling opensco.php in the 'message' frame, which in turn calls the document in the 'sco' frame.
if(($menu=='restart' or $menu=='next' or $menu=='prev' or $openfirst=='yes') and ($exit_after_menu==false)){
	//error_log("Menu was 'prev' or 'next' or restart or openfirst was set and could include javascript for opensco and contents.php.",0);
	//error_log('get_better_anchor_target returned '.$target_identifier,0);
	$output .= "<script type='text/javascript'>\n/* <![CDATA[ */\n".
		"nextwindow=open('opensco.php?sco_href=".base64_encode($href)."&sco_identifier=".$_SESSION['s_identifier']
				."&file=$request_file&edoceo=$edoceo&openDir=$openDir','message');".
		//this line enables the movement of the menu
				//"thiswindow=open('contents.php?file=$request_file&openDir=$openDir&time=$time&edoceo=$edoceo#$identifier','contents');".
				"thiswindow=open('contents.php?file=$request_file&openDir=$openDir&time=$time&edoceo=$edoceo','contents');".
				"\n/* ]]> */\n</script>";
			//sco opening end
}


/*=======================
  TABLE OF CONTENTS TITLE
  =======================*/
//6. If we are here, it's only because the user didn't choose the status display 
//   (otherwise we would have exited). SHOW the TOC here... 
//6.1 Deal with the title
$t=$defaultorgtitle;
$t=str_replace(' ','&nbsp;',$t);
if ($wrap) {
	$output .= "<div><b>$defaultorgtitle</b></div><br />";
} else {
	$output .= "<table border='0' cellspacing='0' cellpadding='0' width='$tablewidth'>
		<tr>
			<td>
				<div><b>$t</b></div>
			</td>
		</tr>
	</table>
	<br />
	<table border='0' cellspacing='0' cellpadding='0' width='$tablewidth'>";
}

//remember the title in the session (for later reuse)
$_SESSION['defaultorgtitle'] = $defaultorgtitle;

/*====================================
  TABLE OF CONTENTS LISTING ROW BY ROW
  ====================================*/
//6.2 Displaying TOC

//$output .= "<pre>".print_r($items,true)."</pre>";

//6.2.1 Init vars to compute total items completed
$num_of_completed=0;
$i=1;

while ($items[$i]) {
	//error_log("Now processing item $i: ".$items[$i]['identifier'],0);

	//6.2.2 Set whether to highlight the item or not (don't highlight if displaying status)
	$bold = false;
	if( ( $items[$i]['identifier'] == $_SESSION['s_identifier']) && (!$_SESSION['displaying_status'])){
		//error_log("Item is the current one",0);
		$bold = true;
	}
	
	if (!$wrap) { $output .= "<tr>"; } //do not wrap this item with the previous one }
	
	//6.2.3 if this user never opened this content 
	// make initial entries in the scorm data table for this student
	// the order of insertions is the same as in the file (so it is the one we want to keep for status display)
	// $index comes from outside this script and represents the scoId in the table
	if ($everopened==false) { 
		$sql = "INSERT INTO $TBL_SCORM_SCO_DATA "
					."(contentId, scoId, scoIdentifier, scoTitle, status, studentID, score, time)VALUES "
					."('".$mycontentId."','".$index."','".$items[$i]['identifier']."','"
					.addslashes($items[$i]['title'])."','not attempted','".$_uid."','0','00:00')";
		$result = api_sql_query($sql,__FILE__,__LINE__);
	}
	
	//6.2.4 Display spaces/cells an amount of time that corresponds to the depth of the element in the hierarchy
	if ($wrap) { 
		$output .= str_repeat("&nbsp;&nbsp;",$items[$i]['level']-1); 
	} else { 
		$output .= "<td>".str_repeat("&nbsp;&nbsp;",$items[$i]['level']-1)."</td>"; 
	}
	$col=$maxlevel-$items[$i]['level']+1; //prepare the number of columns to display
	if (!$wrap) { $output .= '<td colspan="'.$col.'"><table border="0" cellspacing="0" cellpadding="0">'; }
	
	//6.2.5 Get the document/content path
	//$href="../../courses/$_course[path]/scorm".$openDir."/".urlencode($items[$i]['href']);
	if(substr($items[$i]['href'],0,4)=='http'){
		$href = $items[$i]['href'];
	}else{
		$href=api_get_path('WEB_COURSE_PATH').$_course['path']."/scorm".$openDir."/".$items[$i]['href'];
	}
	//6.2.6 Get useful values in practical variables
	$identifier=$items[$i]['identifier'];
	$prereq=$items[$i]['prereq'];
	
	//6.2.7 Get the recorded status for this document/content
	$sql3 = "SELECT status FROM $TBL_SCORM_SCO_DATA WHERE "
				." (contentId='$mycontentId' and studentId='$_uid' and scoIdentifier='$identifier')";
	$result3 = api_sql_query($sql3,__FILE__,__LINE__);
	$ar3=mysql_fetch_array($result3);
	$lesson_status=$ar3['status'];
	
	//6.2.8 Count this lesson as completed if applicable
	if (($lesson_status == 'completed') or ($lesson_status == 'passed')) { $num_of_completed++; }
	
	//6.2.9 display this document/content name as a link to the document/content itself
	if ($items[$i]['href']!='') {
		if (!$wrap) { $output .= "<tr><td>"; } //display choice
		
		//6.2.9.1 if the lesson was completed, display a little icon
		if (($lesson_status=='completed') or ($lesson_status=='passed')) { 
			$output .= '<img src="../img/checkbox_on2.gif" border="0" width="13" height="11" alt="on"/>'; 
		} else {
			//insert the image but make it invisible, so that the space taken is identical (no movement when image appears)
			$output .= '<img src="../img/checkbox_on2.gif" border="0" width="13" height="11" alt="on" style="visibility: hidden"/>'; 
			//$output .= "&nbsp;"; 
		}
		
		if (!$wrap) { $output .= "</td><td>"; } //display choice
		
		//6.2.9.2 Add HTML anchor
		$output .= "<a name='$identifier'>";
		//6.2.9.3 Highlight if applicable
		if ($bold) { $output .= "<b>";}
		//6.2.9.4 Display the link
		$output .= "<a title='". $items[$i]['title']. "' href='opensco.php";
		if ($version=='1.3') { //SCORM version 1.3
			if ($items[$i]['parameters']!='') { 
				// there should probably be an additional '?' here... maybe...
				$output .= "{$items[$i]['parameters']}"."&"; //add parameters to the link 
			} else { 
				$output .= "?"; 
			}
		} else { //version is not 1.3 
			$output .= "?"; 
		}
		
		$output .= "sco_href=".base64_encode($href)."&sco_identifier=$identifier&file=$request_file&edoceo=$edoceo&openDir=$openDir' target='message' class='";
		//as you might realise, this links points to the message frame, to opensco.php, which will then open the content
		//in the contents frame
		
		//change CSS class to "completed" if completed or passed
		if (($lesson_status=='completed') or ($lesson_status=='passed')) { $output .= $array_status[$lesson_status]; }
		$output .= "'>"; 
		//error_log("Added link to opensco with sco_identifier=".$items[$i]['identifier'],0);

	}else{
		if (($lesson_status=='completed') or ($lesson_status=='passed')) { 
			$output .= '<img src="../img/checkbox_on2.gif" border="0" width="13" height="11" alt="on"/>'; 
		} else {
			//insert the image but make it invisible, so that the space taken is identical (no movement when image appears)
			$output .= '<img src="../img/checkbox_on2.gif" border="0" width="13" height="11" alt="on" style="visibility: hidden"/>'; 
		}
	}
	
	//6.2.10 Simulate htmlentities() just for spaces
	$t=$items[$i]['title'];
	
	//6.2.11 Display title (unfiltered if wrapped display - which is the default)
	$t=str_replace(' ','&nbsp;',$t);
	$twrap = $items[$i]['title'];
	//cut the title string if too long to fit in typical frame width
	$max_len = 37-(3*$items[$i]['level']);
	if(strlen($twrap)>$max_len){
		$twrap = str_replace(' ','&nbsp;',substr($twrap,0,$max_len-3).'...');
	}

	if (!$wrap) { $output .= $t; } else { $output .= $twrap; }
	
	if ( $items[$i]['href'] != '' ) { $output .= "</a>"; } //see above for opening anchor tag "a name=..."
	if ($bold) { $output .= "</b>";}   
	if (!$wrap) { $output .= "</td></tr></table></td></tr>\n"; } else { $output .= "<br />\n"; }
	$i++;
}

//6.2.12 Display percentage complete
if ( count($items) != 0 ) { 
    $percent=round($num_of_completed/count($items)*100); 
} else { //no item found. Display an error.
    $output .= message(htmlentities(get_lang('ScormNoItems'),ENT_QUOTES,$charset_lang)); echo $output; exit();
}

$npercent=100-$percent;
if (($percent==0) and ($openfirst=='yes') and ($version != '1.3') and ($menu=='')) { $menu="next"; } 
//ie. the first lesson appears if nothing is completed, not if in 1.3 and the user clicked onto Launch just now

if (!$wrap) { $output .= "</table>\n"; } // display-choice

/*=================
  COMPLETION STATUS
  =================*/

//6.2.13 Display the completion status of the whole learning path
//! temporary fix for the accentuated characters bug (due to different encoding
// between the imsmanifest file - which is the source to this frame's encoding - and the language file)
// The fix is temporary because it will most probably fail on any non-european special character
// To really fix this, there should be a function to check the language files' encoding and put it
// in the third argument to htmlentities() here below
$output .= '<br /><a name="statustable"></a><table border="0"><tr><td>'
    .htmlentities(get_lang('ScormCompstatus'),ENT_QUOTES,$charset_lang)."<br />"
    .'<table border="0" cellpadding="0" cellspacing="0"><tr><td>'
    .'<img src="../img/bar_1.gif" width="1" height="12">'
    .'<img src="../img/bar_1u.gif" width="'.$percent.'" height="12">'
    .'<img src="../img/bar_1m.gif" width="1" height="12">'
    .'<img src="../img/bar_1r.gif" width="'.$npercent.'" height="12">'
    .'<img src="../img/bar_1.gif" width="1" height="12"></td></tr></table>'
    ."</td><td valign=\"bottom\"><font align=\"left\"><br />$percent&nbsp;%</td></tr></table>\n";

if ($tabledraw) {
    $output .= "<br /><br /><hr /><br />";
    $i=1;
    while ($items[$i]) {
        $output .= "Index : {$items[$i]['index']} "
            ."Identifierref : {$items[$i]['identifierref']} "
            ."Identifier : {$items[$i]['identifier']} "
            ."Level : {$items[$i]['level']}   "
            ."Title : {$items[$i]['title']} "
            ."Href : {$items[$i]['href']} "
            ."Prereq : {$items[$i]['prereq']} "
            ."Parameters : {$items[$i]['parameters']} "
            ."Clusterinfo : {$items[$i]['clusterinfo']}<br />\n";
        $i++;
    }
    $output .= "<br />\n";  
    $output .= "defaultorg : $defaultorgref<br />defaultorgtitle : $defaultorgtitle";
}

//6.2.14 If the parser was used, close it (and the XML file too)
if($openfirst=='yes' or $menu=='restart'){
	xml_parser_free($xml_parser);
	fclose($fp);
}

//$openfirst='no';

/*===========================
  VERSION INFO
  ===========================*/

//$output .= "<p class=version>[ Scorm&nbsp;".get_lang('ScormVersion')."&nbsp;:&nbsp;$version ]</p>";

//if($menu=='restart'){unset($_SESSION['s_identifier']);}
$_SESSION['contentId'] = $mycontentId;
$_SESSION['items'] = $items;
//if(empty($_SESSION['old_sco_identifier'])){
//	$_SESSION['old_sco_identifier'] = $_SESSION['s_identifier'];
//}
$_SESSION['displaying_status']=false;

//if the user didn't click a menu button, then assume he clicked a SCORM button in the content,
// and that the element that needs to be updated by closesco.php is the latest element we have here,
// namely $_SESSION['s_identifier'], instead of $_SESSION['old_s_identifier']
if(empty($menu)){
	$_SESSION['old_sco_identifier'] = $_SESSION['s_identifier'];
}

//error_log("End of script (before output). s_identifier is now:".$_SESSION['s_identifier']." and old s_identifier is now:".$_SESSION['old_sco_identifier'],0);


echo $output;
//error_log("End of script (after output). s_identifier is now:".$_SESSION['s_identifier']." and old s_identifier is now:".$_SESSION['old_sco_identifier'],0);

?>
</body></html>
