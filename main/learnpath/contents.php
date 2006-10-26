<?php
/*
==============================================================================
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
==============================================================================
*/
/**
============================================================================== 
* Show the table of contents of a learning path

* Bugfixes and developments since version 1.0 :

* ...
* addslashes for scorm titles containing apostrophe
* changing the img\scorm_logo.gif
* 'Travaux' should never go into database
* option for wrapping/non-wrapping done
* Dokeos LPs always open the first lesson
* add a step -> add a documents from subdirectory, and the directory remains there, not jumping back to root
* Hotpotatoes import to Learning Path
* anonymous login + path with prerequisities had an sql bug in learnpath_functions.inc.php
* Bug with empty path
* in document.php there should be no link to learnpath.php (which file is not needed any more)
* linking in path and in documents (different bahaviours according to parsing variable)
* not visible tests appear in path correctly due to exercice_submit.php
* tracking of paths

*
* @author   Denes Nagy <darkden@freemail.hu>
* @version  2.0
* @access   public
* @package	dokeos.learnpath
============================================================================== 
*/

$langFile = "learnpath";
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

require_once(api_get_path(LIBRARY_PATH) . "database.lib.php");

header('Content-Type: text/html; charset='. $charset);

$tbl_learnpath_item     = Database::get_course_table(LEARNPATH_ITEM_TABLE);
$tbl_learnpath_chapter  = Database::get_course_table(LEARNPATH_CHAPTER_TABLE);
$tbl_learnpath_main     = Database::get_course_table(LEARNPATH_MAIN_TABLE);
$tbl_learnpath_user     = Database::get_course_table(LEARNPATH_USER_TABLE);

$action = $_REQUEST['action'];
$how = $_REQUEST['how'];
$id_in_path = mysql_real_escape_string($_REQUEST['id_in_path']);
$learnpath_id = $_REQUEST['learnpath_id'];
$type = $_REQUEST['type'];
$origin = $_REQUEST['origin'];
$docurl = $_REQUEST['docurl'];
$thelink    = $_REQUEST['thelink'];
$_uid       = $_SESSION['_uid'];
$menu       = $_REQUEST['menu'];
$item_id    = $_REQUEST['item_id'];

/*******************************/
/* Clears the exercise session */
/*******************************/
include('../exercice/exercise.class.php');
include('../exercice/question.class.php');
include('../exercice/answer.class.php');
if(isset($_SESSION['objExercise']))		{ api_session_unregister('objExercise');	unset($objExercise); }
if(isset($_SESSION['objQuestion']))		{ api_session_unregister('objQuestion');	unset($objQuestion); }
if(isset($_SESSION['objAnswer']))		{ api_session_unregister('objAnswer');		unset($objAnswer);   }
if(isset($_SESSION['questionList']))	{ api_session_unregister('questionList');	unset($questionList); }
if(isset($_SESSION['exerciseResult']))	{ api_session_unregister('exerciseResult');	unset($exerciseResult); }

$tablewidth=225;  //this is the width of the content table
$wrap=true; //if false then the toc is not in a table, if true, then it is
$version='2.0';
$items='';

// including the functions
include("learnpath_functions.inc.php");
include("../resourcelinker/resourcelinker.inc.php");

$place=$saveplace;
api_session_unregister('saveplace');
unset($saveplace);


// init from POST/GET vars
if(empty($learnpath_id) && !empty($_GET['learnpath_id'])){
  $learnpath_id = mysql_real_escape_string($_GET['learnpath_id']);
}

?>

<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/scorm.css">

<?php

#echo "<pre>";
#var_dump($_GET);
#var_dump($_POST);
#echo($_SERVER['REQUEST_URI']);
#echo "</pre>";

if ($action=='closelesson')
{
    
	$_SESSION['cur_open']=$id_in_path; //means : currently opened item (by id)
	$prereq_msg=prereqcheck($id_in_path);
	if (is_string($prereq_msg)) {
		//@todo Fix this bloody bug preventing to ouput a message of more than 66 chars into the message frame
		message("<table><tr><td><img src=\"../img/wrong.gif\"></td><td><div class=\"message\"><font color=\"red\">".substr($prereq_msg,0,63)."</font></div></td></tr></table>",'norefresh');
	} else {
		if ($how=='complete')
		{
			if (!$_uid) { 
				$user_id=0; 
			} else { 
				$user_id=$_uid; 
			}
			
			//setting completed status
			$sql = "UPDATE $tbl_learnpath_user SET status='completed' where (learnpath_item_id='$id_in_path' and user_id='$user_id')";
			$result = api_sql_query($sql,__FILE__,__LINE__);
			message("<div class='message'>".get_lang('LearnpathThisStatus')." : ".get_lang('LearnpathCompstatus').".</div></body></html>",'norefresh');
	
			//then opening the item
			$properties="'alwaysRaised=yes, alwaysLowered=no,alwaysOnTop=yes,toolbar=no,location=yes,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=500,height=450,left='+((screen.width-500)/2)+',top='+((screen.height-450)/2)";
			if ($type=="Link") { $type="Link _blank"; } //for backward compatibility
			switch ($type)
			{
				case "Agenda":
				if(empty($agenda_id)){$agenda_id = $item_id;}
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('../calendar/agenda.php?origin=$origin&agenda_id=$agenda_id','content',$properties);",
					"\n/* ]]> */\n</script>";
				break;
				case "Ad_Valvas":
				if(empty($ann_id)){$ann_id = $item_id;}
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('../announcements/announcements.php?origin=$origin&ann_id=$ann_id','content',$properties);",
					"\n/* ]]> */\n</script>";
				break;
				case "Link _blank":
				if(empty($theLink)){$theLink = $item_id;}
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open(\"$thelink\",'learnpathwindow',$properties);",
					"\n/* ]]> */\n</script>";
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('','content'); ",
					"s=\"<html><head><link rel='stylesheet' type='text/css' href='../css/scorm.css'></head><body><br /><div class='message'>".get_lang('link_opened')."</div></body></html>\"; ",
					"z=zwindow.document; ",
					"z.write(s); ",
					"z.close();\n/* ]]> */\n</script>";
				break;
				case "Link _self":
				if(empty($theLink)){$theLink = $item_id;}
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open(\"$thelink\",'content',$properties); ",
					"\n/* ]]> */\n</script>";
				break;
				case "Exercise":
				if(empty($exerciseId)){$exerciseId = $item_id;}
				echo "<script language='Javascript' type='text/javascript'>",
					"zwindow=open('../exercice/exercice_submit.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$id_in_path&exerciseId=$exerciseId','content',$properties);",
					"</script>";
				break;
				case "HotPotatoes":
					if(empty($id)){$id = $item_id;}
					$TBL_DOCUMENT  = Database::get_course_table(DOCUMENT_TABLE);
					$result = api_sql_query("SELECT * FROM ".$TBL_DOCUMENT." WHERE id=$id",__FILE__,__LINE__);
					$myrow= mysql_fetch_array($result);
					$path=$myrow["path"];
					$fullpath=$rootWeb."main/exercice/showinframes.php?file=$path&origin=$origin&cid=".$_course['official_code']."&uid=$_uid&learnpath_id=$learnpath_id&learnpath_item_id=$id_in_path";
					
					echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('$fullpath','content',$properties);",
					"\n/* ]]> */\n</script>";
				break;
				case "Forum":
				if(empty($forumparameters)){$forumparameters = $item_id;}
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open(\"../phpbb/viewforum.php?$forumparameters&lp=true\",'content',$properties);",
					"\n/* ]]> */\n</script>";
	/*				echo "<script type='text/javascript'> ",
					"zwindow=open('','content'); ",
					"s=\"<html><head><link rel=stylesheet type=text/css href='../css/scorm.css'></head><body><br><div class=message>".get_lang('forum_opened')."</div></body></html>\"; ",
					"z=zwindow.document; ",
					"z.write(s); ",
					"z.close(); </script>";
	*/
				break;
				case "Thread":
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open(\"../phpbb/viewtopic.php?topic=$topic&forum=$forum&md5=$md5&lp=true\",'content',$properties);",
					"\n/* ]]> */\n</script>";
	/*				echo "<script type='text/javascript'> ",
					"zwindow=open('','content'); ",
					"s=\"<html><head><link rel=stylesheet type=text/css href='../css/scorm.css'></head><body><br><div class=message>".get_lang('forum_opened')."</div></body></html>\"; ",
					"z=zwindow.document; ",
					"z.write(s); ",
					"z.close(); </script>";  */
				break;
				case "Post":
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('','content',$properties);",
					"s=\"<html><head><link rel='stylesheet' type='text/css' href='../css/default.css'></head><body><table border='0' cellpadding='3' cellspacing='1' width='100%'><tr><td colspan='2' bgcolor='#e6e6e6'><b>$posttitle</b><br />$posttext</td></tr><tr><td colspan='2'></td></tr><tr><td bgcolor='#cccccc' align='left'>".get_lang('author')." : $posterprenom $posternom</td><td align='right' bgcolor='#cccccc'>".get_lang('date')." : $posttime</td></tr><tr><td colspan='2' height='10'></td></tr></table></body></html>\";",
					"z=zwindow.document;",
					"z.write(s);",
					"z.close();",
					"\n/* ]]> */\n</script>";
				break;
				case "Document":
	
				//if you use the window.open function in case of opening Office docs, the Explorer cannot find the files !!!
				//and after an Office document, the 'top.content.document.location=' does not work either,
				//only the window.open works, so the best solution is combining the two opening methods in a sophisticated way
	
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"xwindow=open('blank.php?open=doc','content');";
				$prevofficedoc=$_SESSION['officedoc'];
				$pos1=strpos($docurl,'.doc');
				$pos2=strpos($docurl,'.ppt');
				$pos3=strpos($docurl,'.pps');
				$pos4=strpos($docurl,'.xls');
				if (($pos1>0) or ($pos2>0) or ($pos3>0) or ($pos4>0)) 
				{ 
						$officedoc=true;
						$openmethod=1;
				} 
				else { 
						$officedoc=false; 
				} 
				if ($prevofficedoc==false)
				{ 
					$openmethod=1; 
				}
				// echo "alert('Previous_was_officedoc : $prevofficedoc Now_Ofiicedoc : $officedoc Method : $openmethod');";
	
				$enableDocumentParsing=''; //=true
	
				/* -----------------------------------------------------------------------------------------------
				
				$enableDocumentParsing was used in 1.5.5, now not existing, all files are parsed, the code below
				however is able to handle the non-parsed version also, if the courses dir is named 'courses' !!!!!!! 
				
				----------------------------------------------------------------------------------------------- */
	
				if ($openmethod==1) {  //document.location=...
					if (!$enableDocumentParsing) {
						$file='courses/'.urlencode($_course['path']).'/document'.urlencode($curDirPath).$docurl;
						echo "top.content.document.location='../../$file';";
					}
					else {
						echo "top.content.document.location='../document/download.php?doc_url=".urlencode($docurl)."';";
					}
					$openmethod=2;
				} else {  //window.open(...)
					if (!$enableDocumentParsing) {
						$file='courses/'.urlencode($_course['path']).'/document'.urlencode($curDirPath).$docurl;
						echo "xwindow=open('../../$file','content');";
					}
					else {
						echo "xwindow=open('../document/download.php?doc_url=".urlencode($docurl)."','content');";
					}
					$openmethod=1;
				}
				echo "\n/* ]]> */\n</script>";
				api_session_register('openmethod');
				api_session_register('officedoc');
				break;
				case "Assignments":
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('../work/work.php?origin=learnpath','content',$properties);",
					"\n/* ]]> */\n</script>";
				break;
				case "Dropbox":
				echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('../dropbox/index.php?origin=learnpath','content',$properties);",
					"\n/* ]]> */\n</script>";
				break;
				case "Introduction_text":
					echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('learnpath_item_show.php?type=Introduction_text','content',$properties);",
					"\n/* ]]> */\n</script>";
				break;
				case "Course_description":
					$s=api_get_path('WEB_CODE_PATH')."course_description?origin=learnpath";
					echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('$s','content',$properties);",
					"\n/* ]]> */\n</script>";
				break;
				case "Groups":
					echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('../group/group.php?origin=learnpath','content',$properties);",
					"\n/* ]]> */\n</script>";
				break;
				case "Users":
					echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
					"zwindow=open('../user/user.php?origin=learnpath','content',$properties);",
					"\n/* ]]> */\n</script>";
				break;
			}
	
		}
	}
}

/*=========================
  IF RESTART WAS CLICKED...
  =========================*/

if ($menu=='restart') { //Restart clicked
	$_SESSION['cur_open']='restarted';
	echo "<script type='text/javascript'>\n/* <![CDATA[ */\n xwindow=open('blank.php?display_msg=1','content');\n/* ]]> */\n</script>";
	$sql = "UPDATE $tbl_learnpath_user SET score='0', status='incomplete', time='00:00' WHERE (user_id='$_uid' and learnpath_id='$learnpath_id')";
	$result = api_sql_query($sql,__FILE__,__LINE__);

	message("<table><tr><td><img src=\"../img/restart.jpg\"></td><td>".get_lang('LearnpathRestarted')."</td></tr></table>",'refresh');

}

/*=========================
  MESSAGE FUNCTION
  =========================*/

function message($text, $refresh) {  //Javascript and php functions after each other as in mixed salad; cool, huh ?
	if ($refresh=='refresh') { $r="onload=\"javascript:parent.toc.document.location=parent.toc.document.location\""; } 
	else { $r=''; }
	
	echo "<script type='text/javascript'>\n/* <![CDATA[ */\n",
		"zwindow=window.open('','message');",
		"s='<html><head><link rel=\"stylesheet\" type=\"text/css\" href=\"../css/scorm.css\"></head><body $r>",
		"<div class=\"message\">".addslashes($text)."</div></body></html>';",
		"z=zwindow.document;",
		"z.write(s);",
		"z.close();",
		"\n/* ]]> */\n</script>";
}

?>


</head>
<body>

<?php

/*===========================
  IF MY STATUS WAS CLICKED...
  ===========================*/

if ($menu=='my_status') {
		$w=$tablewidth-20;
		echo "<br />";
		
		//if display is fullscreen
		if (strcmp($_GET["fs"],"true")==0)
			{ echo "<table align='center'>"; }
	
		else
			{ echo "<table class='margin_table'>"; }
		echo ""
			."<tr>"
				."<td>"
					."<div class='title'>".get_lang('LearnpathMystatus')."</div>"
				."</td>"
			."</tr>\n"
			."<tr>"
				."<td>&nbsp;</td>"
			."</tr>\n"
			."<tr>"
				."<td>"
					."<table border='0' class='data_table'>"
						."<tr>"
							."<td>"
								."<div class='mystatusfirstrow'>".get_lang('LearnpathLessonTitle')."</div>"
							."</td>"
							."<td>"
								."<div 	class='mystatusfirstrow'>".get_lang('LearnpathStatus')."</div>"
							."</td>"
							."<td>"
								."<div class='mystatusfirstrow'>".get_lang('LearnpathScore')."</div>"
							."</td>";
		//<td><div class='mystatusfirstrow'>".get_lang('LearnpathTime')."</div></td>
		echo                "</tr>";
		get_tracking_table($learnpath_id, $_SESSION['_uid']);
	
		echo "</table></td></tr>\n</table></body></html>\n";
		exit();
}


/*=================
  TABLE OF CONTENTS 
  ================*/

// display learnpath title

$sql="SELECT * FROM $tbl_learnpath_main WHERE learnpath_id='".$_GET['learnpath_id']."'";
$result=api_sql_query($sql,__FILE__,__LINE__);
$row=mysql_fetch_array($result); 

$t=$row['learnpath_name'];
$t=str_replace(' ','&nbsp;',$t);
if ($wrap) { echo "<div class='title'><br />{$row['learnpath_name']}</div><br />"; }
	else { echo "<table border='0' cellspacing='0' cellpadding='0' width='$tablewidth'><tr><td><div class='title'>$t</div></td></tr></table><br /><table border='0' cellspacing='0' cellpadding='0' width='$tablewidth'>"; }


//for fixed width : width=$tablewidth (in two tables) and enable nbsp substitution in two places


//check if the content (each item) was ever opened or not, if not, add new lines to learnpath_user

$sql2="SELECT * FROM $tbl_learnpath_chapter WHERE (learnpath_id='".$_GET['learnpath_id']."')";
$result2=api_sql_query($sql2,__FILE__,__LINE__);
while ($row2=mysql_fetch_array($result2)) { 
	$id=$row2['id'];
	$sql3="SELECT * FROM $tbl_learnpath_item WHERE (chapter_id=$id)"; 
	$result3=api_sql_query($sql3,__FILE__,__LINE__);
	while ($row3=mysql_fetch_array($result3)) {
		$numrows=0;
		$sql0 = "SELECT * FROM $tbl_learnpath_user WHERE (user_id='".$_uid."' and 	learnpath_item_id='".$row3['id']."')";
		$result0=api_sql_query($sql0,__FILE__,__LINE__);
		$row0=mysql_fetch_array($result0);
		$numrows = mysql_num_rows($result0);
		if ($numrows==0) {
			$sql4 = "INSERT INTO $tbl_learnpath_user VALUES 	('$_uid','$learnpath_id','".$row3['id']."','".get_lang('LearnpathIncomplete')."','0','00:00')";
			$result4 = api_sql_query($sql4,__FILE__,__LINE__);
		}  //otherwise, the given item is already in the database
	}
}

/*====================================
  TABLE OF CONTENTS LISTING ROW BY ROW
  ====================================*/
// now display the real items
if (is_empty($_GET['learnpath_id'])) { echo get_lang('empty'); exit(); }

$num_of_completed=0;
$items[0]='items_init';
/*
$sql2="SELECT * FROM $tbl_learnpath_chapter WHERE (learnpath_id=$learnpath_id) ORDER BY display_order"; 
$result2=api_sql_query($sql2,__FILE__,__LINE__);
$pieces=0;
while ($row2=mysql_fetch_array($result2)) { 
    $id=$row2['id'];
    $sql3="SELECT * FROM $tbl_learnpath_item WHERE (chapter_id=$id) ORDER BY display_order"; 
    $result3=api_sql_query($sql3,__FILE__,__LINE__);

    if ($wrap) { echo "&nbsp;<font color=black>{$row2['chapter_name']}</font><br>"; }
      else { echo "<tr><td colspan=3>&nbsp;<font color=black>{$row2['chapter_name']}</font></td></tr>"; }
    
    if ($wrap) {
        if ($row2['chapter_description'] != '')
            { echo "<div class=description>&nbsp;&nbsp;{$row2['chapter_description']}</div>"; }
    }
    else {
        if ($row2['chapter_description'] != '')
            { echo "<tr><td colspan=3><div class=description>&nbsp;&nbsp;{$row2['chapter_description']}</div></td></tr>"; }
    }
    
    while ($row3=mysql_fetch_array($result3)) {
        $sql0 = "SELECT * FROM $tbl_learnpath_user WHERE (user_id='".$_uid."' and learnpath_item_id='".$row3['id']."' and learnpath_id='".$learnpath_id."')";
        $result0=api_sql_query($sql0,__FILE__,__LINE__);
        $row0=mysql_fetch_array($result0);

        $completed='';
        if (($row0['status']=='completed') or ($row0['status']=='passed')) { $completed='completed'; $num_of_completed++; }
        if ($wrap) { echo "<a name={$row3['id']}>&nbsp;&nbsp;&nbsp;"; }
            else { echo "<tr><td><a name={$row3['id']}>&nbsp;&nbsp;&nbsp;"; }
        if ($wrap) { $icon='wrap'; }
        display_addedresource_link_in_learnpath($row3['item_type'], $row3['item_id'], $completed, $row3['id'],'player',$icon);
        if ($wrap) { echo "<br>"; } else { echo "</td></tr>"; }
        
        $pieces++;

    }
}
*/
// Another way to display the TOC (multi-level structure)
$tree = get_learnpath_tree($learnpath_id);
list($pieces,$num_of_completed) = display_toc_chapter_contents($tree, 0, $learnpath_id, $_SESSION['_uid'], $wrap);

$percent=round($num_of_completed/$pieces*100);
$npercent=100-$percent;
if (($menu=='') and ($openfirst=='yes')) { $menu="next"; $_SESSION['cur_open']='restarted'; } 


/*=================
  COMPLETION STATUS
  =================*/

if (!$wrap) { echo "</table>"; }
echo "<br /><a name='statustable'></a>"
    ."<table border='0'>"
        ."<tr>"
            ."<td>".get_lang('LearnpathCompstatus').":<br />"
         
                ."<table border='0' cellpadding='0' cellspacing='0'>"
                ."<tr>"
                    ."<td>"
                        ."<img src='../img/bar_1.gif' width='1' height='12'>"
                        ."<img src='../img/bar_1u.gif' width='$percent' height='12'>"
                        ."<img src='../img/bar_1m.gif' width='1' height='12'>"
                        ."<img src='../img/bar_1r.gif' width='$npercent' height='12'>"
                        ."<img src='../img/bar_1.gif' width='1' height='12'>"
                    ."</td>"
                ."</tr>"
                ."</table>"
            ."</td>"
            ."<td><br />"
                ."<font align='left'>$percent%</font>"
            ."</td>"
        ."</tr>"
    ."</table>";

/*==============================
  IF PREV OR NEXT WAS CLICKED...
  ==============================*/
//attempt to compensate lack of $items definition
$items = get_ordered_items_list($tree, 0, false);


$elem_id = $_SESSION['cur_open'];
$elem_type = '';
$elem_chapter_id = 0;
$elem_item_id = 0;

if (($menu=='prev') or ($menu=='next')) {
    #echo "$menu clicked";
    #echo "<pre>";
    #var_dump($items);echo "</pre>";
    #if (count($items)==2) { // YW: where does $items come from?? So far it's always '' and then 'item_init'
    #    $i=1;
    #} else {
    
    if ($_SESSION['cur_open']=='restarted') { 
        $elem_id = $items[0]['id'];
        $elem_type = $items[0]['item_type'];
        $elem_chapter_id = $items[0]['chapter_id'];
        $elem_item_id = $items[0]['item_id'];        
    }else{
        
        $myorder = 0;
        foreach($items as $key => $item){
            #echo "Analyse de l'elem d'ordre $key: ".$item['id']."<>".$_SESSION['cur_open']." <br />\n";
            if($item['id'] == $_SESSION['cur_open']){
                $myorder = $key;
                break;
            }
        }
        #echo "Element d'ordre $myorder selectionne<br />\n";
        if ($menu=='next') {
            if (!empty($items[$myorder+1]) ) {
                $elem_id = $items[$myorder+1]['id'];
                $elem_type = $items[$myorder+1]['item_type'];
                $elem_chapter_id = $items[$myorder+1]['chapter_id'];
                $elem_item_id = $items[$myorder+1]['item_id'];
            }else{
                message(get_lang('LearnpathNoNext'),'norefresh');
            }
            #if ($_SESSION['cur_open']=='restarted') { 
            #    $i=1; 
            #} else {
            #    $i=0;
            #    do {  //we take the next in the row
            #        $i++;
            #    } while ( (strpos($items[$i],"id_in_path=".$_SESSION['cur_open'])<10) and ($i <= count($items)) );
            #    $i++;
            #    if ($i>=count($items)) { message(get_lang('LearnpathNoNext'),'norefresh'); exit(); }
            #}
        }
        elseif ($menu=='prev') {
            if (!empty($items[$myorder-1]) ) {
                $elem_id = $items[$myorder-1]['id'];
                $elem_type = $items[$myorder-1]['item_type'];
                $elem_chapter_id = $items[$myorder-1]['chapter_id'];
                $elem_item_id = $items[$myorder-1]['item_id'];
            }else{
                message(get_lang('LearnpathNoPrev'),'norefresh');
            }
            #$i=count($items);
            #do {  //we take the previous in the row
            #    $i--;
            #} while ( (strpos($items[$i],"id_in_path=".$_SESSION['cur_open'])<10) and ($i >= 1));
            #$i--;
            #if ($i<1) { message(get_lang('LearnpathNoPrev'),'norefresh'); exit(); }
        }
    }
    #}
    //lesson opening
    $url = get_addedresource_link_in_learnpath($elem_type, $elem_item_id, $elem_id);

    echo "<script type='text/javascript'>\n/* <![CDATA[ */\n"
        .'window.location="'.$url.'"'
        ."\n/* ]]> */\n</script>";
}

/*===========================
  VERSION INFO - NOW NOT SHOWN
  ===========================*/

//echo "<p class=version>[&nbsp;".get_lang('learning_path')."&nbsp;".get_lang('LearnpathVersion')."&nbsp;:&nbsp;$version&nbsp;]</p>";

#session_register('cur_open');

?>
</body></html>
