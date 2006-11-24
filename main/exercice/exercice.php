<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	
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
*	EXERCISE LIST  
*
*	This script shows the list of exercises for administrators and students.
*
*	@author Olivier Brouckaert, original author
*	@author Denes Nagy, HotPotatoes integration
*	@author Wolfgang Schneider, code/html cleanup
*	@package dokeos.exercise
* 	@todo use database library
============================================================================== 
*/

$langFile='exercice';

require_once('../inc/global.inc.php');
$this_section=SECTION_COURSES;
api_protect_course_script();

$show=(isset($_GET['show']) && $_GET['show'] == 'result')?'result':'test'; // moved down to fix bug: http://www.dokeos.com/forum/viewtopic.php?p=18609#18609

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
require_once('exercise.class.php');
require_once('question.class.php');
require_once('answer.class.php');
require_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
require_once('hotpotatoes.lib.php');

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$is_allowedToEdit = is_allowed_to_edit();

$TBL_USER          	    = Database::get_main_table(TABLE_MAIN_USER);
$TBL_DOCUMENT          	= Database::get_course_table(DOCUMENT_TABLE);
$TBL_ITEM_PROPERTY      = Database::get_course_table(ITEM_PROPERTY_TABLE);
$TBL_EXERCICE_QUESTION	= Database::get_course_table(QUIZ_TEST_QUESTION_TABLE);
$TBL_EXERCICES			= Database::get_course_table(QUIZ_TEST_TABLE);
$TBL_QUESTIONS			= Database::get_course_table(QUIZ_QUESTION_TABLE);
$TBL_TRACK_EXERCICES   	= $_configuration['statistics_database']."`.`track_e_exercices";
$TBL_TRACK_HOTPOTATOES  = $_configuration['statistics_database']."`.`track_e_hotpotatoes";
$TABLETRACK_ATTEMPT 	= $_configuration['statistics_database']."`.`track_e_attempt"; 

// document path
$documentPath= api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
// picture path
$picturePath=$documentPath.'/images';
// audio path
$audioPath=$documentPath.'/audio';

// hotpotatoes
$uploadPath = "/HotPotatoes_files";
$exercicePath = $_SERVER['PHP_SELF'];
$exfile = explode('/',$exercicePath);
$exfile = strtolower($exfile[sizeof($exfile)-1]);
$exercicePath = substr($exercicePath,0,strpos($exercicePath,$exfile));
$exercicePath = $exercicePath."exercice.php";

// maximum number of exercises on a same page
$limitExPage=50;

// Clear the exercise session
if(isset($_SESSION['objExercise']))		{ api_session_unregister('objExercise');		}
if(isset($_SESSION['objQuestion']))		{ api_session_unregister('objQuestion');		}
if(isset($_SESSION['objAnswer']))		{ api_session_unregister('objAnswer');		}
if(isset($_SESSION['questionList']))	{ api_session_unregister('questionList');	}
if(isset($_SESSION['exerciseResult']))	{ api_session_unregister('exerciseResult');	}

//general POST/GET/SESSION/COOKIES parameters recovery
if ( empty ( $origin ) ) {
    $origin     = $_REQUEST['origin'];
}
if ( empty ($choice ) ) {
    $choice     = $_REQUEST['choice'];
}
if ( empty ( $hpchoice ) ) {
    $hpchoice   = $_REQUEST['hpchoice'];
}
if ( empty ($exerciseId ) ) {
    $exerciseId = mysql_real_escape_string($_REQUEST['exerciseId']);
}
if ( empty ( $file ) ) {
    $hpchoice   = mysql_real_escape_string($_REQUEST['file']);
}
$learnpath_id = mysql_real_escape_string($_REQUEST['learnpath_id']);
$learnpath_item_id = mysql_real_escape_string($_REQUEST['learnpath_item_id']);
$page = mysql_real_escape_string($_REQUEST['page']);

if($origin == 'learnpath'){
	$show = 'result';
}
$htmlHeadXtra[]='<style type="text/css">
<!--
a.invisible
{
	color: #999999;
}

a.invisible:visited
{
	color: #999999;
}

a.invisible:active
{
	color: #999999;
}

a.invisible:hover
{
	color: #999999;
}
-->
</style>';

if($show!='result'){
	$nameTools=get_lang('Exercices');
}
else {
	if($is_allowedToEdit)
	{
	$nameTools="Student Score";
	$interbreadcrump[]=array("url" => "exercice.php","name" => get_lang('Exercices'));
	}
	else
	{
	$nameTools="Your result";
	$interbreadcrump[]=array("url" => "index.php","name" => get_lang('Exercices'));
	}
}


if ($origin != 'learnpath') { //so we are not in learnpath tool
	Display::display_header($nameTools,"Exercise");
	
} else {
	?> <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH); ?>css/default.css"/> 

<?php
}

// used for stats
include_once(api_get_path(LIBRARY_PATH).'events.lib.inc.php');

event_access_tool(TOOL_QUIZ);

// need functions of statsutils lib to display previous exercices scores
include_once(api_get_path(LIBRARY_PATH).'statsUtils.lib.inc.php');

if($is_allowedToEdit)
{
	include_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
	if(!is_dir($audioPath))
	{
		if(is_file($audioPath))
		{
			@unlink($audioPath);
		}

		@mkdir($audioPath);

		//$query="INSERT INTO $TBL_DOCUMENT (path,filetype) VALUES('".str_replace($documentPath,'',$audioPath)."','folder')";
		//api_sql_query($query,__FILE__,__LINE__);
		//$id = Database::get_last_insert_id();
		$id = add_document($_course,str_replace($documentPath,'',$audioPath),'folder',0,'Audio');
		//$time = time();
		//$time = date("Y-m-d H:i:s", $time);
		
		//$query = "INSERT INTO $TBL_ITEM_PROPERTY (tool, ref, insert_user_id, insert_date, lastedit_type) VALUES ('".TOOL_DOCUMENT."', $id, $_user['user_id'], '$time', 'DocumentAdded' )";
		//api_sql_query($query,__FILE__,__LINE__);
		api_item_property_update($_course,TOOL_DOCUMENT,$id,'FolderCreated',$_user['user_id']);
	}

	if(!is_dir($picturePath))
	{
		if(is_file($picturePath))
		{
			@unlink($picturePath);
		}

		@mkdir($picturePath);

		//$query="INSERT INTO $TBL_DOCUMENT (path, filetype) VALUES('".str_replace($documentPath,'',$picturePath)."','folder')";
		//api_sql_query($query,__FILE__,__LINE__);
		//$id = Database::get_last_insert_id();
		$id = add_document($_course,str_replace($documentPath,'',$picturePath),'folder',0,'Pictures');
		//$time = time();
		//$time = date("Y-m-d H:i:s", $time);
		
		//$query = "INSERT INTO $TBL_ITEM_PROPERTY (tool, ref, insert_user_id, insert_date, lastedit_type) VALUES ('".TOOL_DOCUMENT."', $id, $_user['user_id'], '$time', 'DocumentAdded' )";
		//api_sql_query($query,__FILE__,__LINE__);
		api_item_property_update($_course,TOOL_DOCUMENT,$id,'FolderCreated',$_user['user_id']);
	}
}
if($origin != 'learnpath'){
	//api_display_tool_title($nameTools);
}
/*
-----------------------------------------------------------
	Introduction section
	(editable by course admins)
-----------------------------------------------------------
*/
//Display::display_introduction_section(TOOL_QUIZ);


// defines answer type for previous versions of Claroline, may be removed in Claroline 1.5
$sql="UPDATE $TBL_QUESTIONS SET position='1',type='2' WHERE position IS NULL OR position<'1' OR type='0'";
api_sql_query($sql,__FILE__,__LINE__);

// selects $limitExPage exercises at the same time
$from=$page*$limitExPage;
//	$sql="SELECT id,title,type,active FROM $TBL_EXERCICES ORDER BY title LIMIT $from,".($limitExPage+1);
//	$result=api_sql_query($sql,__FILE__,__LINE__);
$sql="SELECT count(id) FROM $TBL_EXERCICES";
$res = api_sql_query($sql,__FILE__,__LINE__);
list($nbrexerc) = mysql_fetch_row($res);

HotPotGCt($documentPath,1,$_user['user_id']);

// only for administrator

if($is_allowedToEdit)
{
	
	if(!empty($choice))
	{
		// construction of Exercise
		$objExerciseTmp=new Exercise();

		if($objExerciseTmp->read($exerciseId))
		{
			switch($choice)
			{
				case 'delete':	// deletes an exercise
								$objExerciseTmp->delete();

								break;
				case 'enable':  // enables an exercise
								$objExerciseTmp->enable();
								$objExerciseTmp->save();

								// "WHAT'S NEW" notification: update table item_property (previously last_tooledit)
								api_item_property_update($_course, TOOL_QUIZ, $exerciseId, "QuizAdded", $_user['user_id']);

								break;
				case 'disable': // disables an exercise
								$objExerciseTmp->disable();
								$objExerciseTmp->save();

								break;
			}
		}

		// destruction of Exercise
		unset($objExerciseTmp);
	}

	//$sql="SELECT id,title,type,active FROM $TBL_EXERCICES ORDER BY title LIMIT $from,".($limitExPage+1);
	//$result=api_sql_query($sql,__FILE__,__LINE__);
	

	if(!empty($hpchoice))
	{
		switch($hpchoice)
		{
				case 'delete':	// deletes an exercise
							$imgparams = array();	
							$imgcount = 0;							
							GetImgParams($file,$documentPath,$imgparams,$imgcount);
							$fld = GetFolderName($file);
							for($i=0;$i < $imgcount;$i++)
							{						
									my_delete($documentPath.$uploadPath."/".$fld."/".$imgparams[$i]);									
									update_db_info("delete", $uploadPath."/".$fld."/".$imgparams[$i]);
							}	
							
							if ( my_delete($documentPath.$file))
							{
								update_db_info("delete", $file);
							}											
							my_delete($documentPath.$uploadPath."/".$fld."/");
							break;
				case 'enable':  // enables an exercise
					$newVisibilityStatus = "1"; //"visible"
                    $query = "SELECT id FROM $TBL_DOCUMENT WHERE path='$file'";
                    $res = api_sql_query($query,__FILE__,__LINE__);
                    $row = Database::fetch_array($res, 'ASSOC');
                    api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'visible', $_user['user_id']);
                    //$dialogBox = get_lang('ViMod');

							break;
				case 'disable': // disables an exercise
					$newVisibilityStatus = "0"; //"invisible"
                    $query = "SELECT id FROM $TBL_DOCUMENT WHERE path='$file'";
                    $res = api_sql_query($query,__FILE__,__LINE__);
                    $row = Database::fetch_array($res, 'ASSOC');
                    api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'invisible', $_user['user_id']);
					#$query = "UPDATE $TBL_DOCUMENT SET visibility='$newVisibilityStatus' WHERE path=\"".$file."\""; //added by Toon
					#api_sql_query($query,__FILE__,__LINE__);
					//$dialogBox = get_lang('ViMod');

								break;
		}
	}

	if($show == 'test')
	{
		$sql="SELECT id,title,type,active,description FROM $TBL_EXERCICES ORDER BY title LIMIT $from,".($limitExPage+1);
		$result=api_sql_query($sql,__FILE__,__LINE__);
	}
}
// only for students
elseif($show == 'test')
{
	$sql="SELECT id,title,type,description FROM $TBL_EXERCICES WHERE active='1' ORDER BY title LIMIT $from,".($limitExPage+1);
	$result=api_sql_query($sql,__FILE__,__LINE__);
}


if($show == 'test'){

	//error_log('Show == test',0);
	$nbrExercises=mysql_num_rows($result);
	
	echo "<table border=\"0\" align=\"center\" cellpadding=\"2\" cellspacing=\"2\" width=\"100%\">",
		"<tr>";
	
	if (($is_allowedToEdit) and ($origin != 'learnpath')) 	
	{
		//error_log('is_allowedToEdit and origin<> learnpath',0);
		echo "<td width=\"50%\" nowrap=\"nowrap\">",
			"<img src=\"../img/quiz.gif\" alt=\"new test\" valign=\"ABSMIDDLE\">&nbsp;<a href=\"admin.php\">".get_lang("NewEx")."</a> | ",
			
			//"<img src=\"../img/quiz_na.gif\" alt=\"new test\" valign=\"ABSMIDDLE\"><a href=\"question_pool.php\">".get_lang("QuestionPool")."</a> | ",
			"<img src=\"../img/jqz.jpg\" alt=\"HotPotatoes\" valign=\"ABSMIDDLE\">&nbsp;<a href=\"hotpotatoes.php\">".get_lang("ImportHotPotatoesQuiz")."</a>",
			"</td>",
			"<td width=\"50%\" align=\"right\">";
	}
	else
	{
		//error_log('!is_allowedToEdit or origin == learnpath ('.$origin.')',0);
		echo "<td align=\"right\">";
	}
	
	//get HotPotatoes files (active and inactive)
	$res = api_sql_query ("SELECT * 
					FROM $TBL_DOCUMENT
					WHERE 
					path LIKE '".$uploadPath."/%/%'",__FILE__,__LINE__);
	$nbrTests = Database::num_rows($res);	
	$res = api_sql_query ("SELECT * 
					FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip 
					WHERE  d.id = ip.ref 
					AND ip.tool = '".TOOL_DOCUMENT."' 
					AND d.path LIKE '".$uploadPath."/%/%' 
					AND ip.visibility='1'", __FILE__,__LINE__);
	$nbrActiveTests = Database::num_rows($res);	  
	//error_log('nbrActiveTests = '.$nbrActiveTests,0);
	
	
	if($is_allowedToEdit)
	{//if user is allowed to edit, also show hidden HP tests
		$nbrHpTests = $nbrTests;
	}else
	{
		$nbrHpTests = $nbrActiveTests;
	}
	$nbrNextTests = $nbrHpTests-(($page*$limitExPage));
	
	
	//show pages navigation link for previous page
	if($page)
	{
		echo "<a href=\"".$_SERVER['PHP_SELF']."?".api_get_cidreq()."&page=".($page-1)."\">&lt;&lt; ",get_lang("PreviousPage")."</a> | ";
	}
	elseif($nbrExercises+$nbrNextTests > $limitExPage)
	{
		echo "&lt;&lt; ",get_lang("PreviousPage")." | ";
	}
	
	//show pages navigation link for previous page
	if($nbrExercises+$nbrNextTests > $limitExPage)
	{
		echo "<a href=\"".$_SERVER['PHP_SELF']."?".api_get_cidreq()."&page=".($page+1)."\">&gt;&gt; ",get_lang("NextPage")."</a>";
	
	}
	elseif($page)
	{
		echo get_lang("NextPage") . " &gt;&gt;";
	}
	
	echo "</td>",
			"</tr>",
			"</table>";
	
?> 
<table border="0" align="center" cellpadding="2" cellspacing="2" width="100%">
  <?php
	if (($is_allowedToEdit) and ($origin != 'learnpath')) 
	{
	?>
  <tr bgcolor="#e6e6e6">
    <td align="center"><?php echo get_lang("ExerciseName");?></td>
     <td align="center"><?php echo get_lang("Description");?></td>
	 <td width="13%" align="center"><b><?php echo get_lang('Addlimits'); ?> </b><br /> 
    (time,attempts)</td>
	 <td align="center"><?php echo get_lang("Modify");?></td>
   
  </tr>
  <?php
	}
  else
	{
	 ?> <tr bgcolor="#e6e6e6">
    <td align="center"><?php echo get_lang("ExerciseName");?></td>
     <td align="center"><?php echo get_lang("Description");?></td>
	 <td align="center"><?php echo get_lang("State");?></td>
   
  </tr>
	<?php }
	
	// show message if no HP test to show
	if(!($nbrExercises+$nbrHpTests) )
	{
	?>
  <tr>
    <td <?php if($is_allowedToEdit) echo 'colspan="4"'; ?>><?php echo get_lang("NoEx"); ?></td>
  </tr>
  <?php
	}
	
	$i=1;
	
	// while list exercises
	
	if ($origin != 'learnpath') {
	
		while($row=mysql_fetch_array($result))
		{

			//error_log($row[0],0);
			echo "<tr>\n";
			
			// prof only
			if($is_allowedToEdit)
			{
				?>
  <td width="27%"><table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td width="30" align="left"><img src="../img/test.gif"></td>
      <td width="15" valign="left" align="center"><?php echo ($i+($page*$limitExPage)).'.'; ?></td>
      <?php $row['title']=api_parse_tex($row['title']); ?>
      <td><a href="exercice_submit.php?<?php echo api_get_cidreq()."&origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id"; ?>&exerciseId=<?php echo $row['id']; ?>" <?php if(!$row['active']) echo 'class="invisible"'; ?>><?php echo $row['title']; ?></a></td>
    </tr>
  </table></td>
 <td width="8%" align="center"> <?php 
 $exid = $row['id'];
 $sqlquery = "SELECT count(*) FROM $TBL_EXERCICE_QUESTION WHERE `exercice_id` = '$exid'";
 $sqlresult =mysql_query($sqlquery);
 $rowi = mysql_result($sqlresult,0);
 echo $rowi.' Questions'; ?> </td>
 <td  width="13%" align="center"><a href="addlimits.php?exercise_id=<?php echo $exid; ?>"> <img src="../img/test_prop.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Addlimits'); ?>" /></a> </td>
      <td width="12%" align="center"><a href="admin.php?exerciseId=<?php echo $row[id]; ?>"> <img src="../img/edit.gif" border="0" alt="<?php echo htmlentities(get_lang('Modify')); ?>" /></a> 
    <a href="exercice.php?choice=delete&exerciseId=<?php echo $row[id]; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('areYouSure'))); echo $row['title']; echo "?"; ?>')) return false;"> <img src="../img/delete.gif" border="0" alt="<?php echo htmlentities(get_lang('Delete')); ?>" /></a> 
    <?php
				// if active
				if($row['active'])
				{
					?>
      <a href="exercice.php?choice=disable&page=<?php echo $page; ?>&exerciseId=<?php echo $row['id']; ?>"> <img src="../img/visible.gif" border="0" alt="<?php echo htmlentities(get_lang('Deactivate')); ?>" /></a> 
    <?php
				}
				// else if not active
				else
				{
					?>
      <a href="exercice.php?choice=enable&page=<?php echo $page; ?>&exerciseId=<?php echo $row['id']; ?>"> <img src="../img/invisible.gif" border="0" alt="<?php echo htmlentities(get_lang('Activate')); ?>" /></a> 
    <?php
				}
				echo "</td></tr>\n";
                
			}
			// student only
			else
			{
				?>
      <td width="40%"><table border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td width="20" valign="top" align="right"><?php echo ($i+($page*$limitExPage)).'.'; ?></td>
            <td width="1">&nbsp;</td>
            <?php $row['title']=api_parse_tex($row['title']);?>
            <td><a href="exercice_submit.php?<?php echo api_get_cidreq()."&origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id"; ?>&exerciseId=<?php echo $row['id']; ?>"><?php echo $row['title']; ?></a></td>
			
			</tr>
    </table></td>
	 <td align='center'> <?php 
 $exid = $row['id'];
 $sqlquery = "SELECT count(*) FROM $TBL_EXERCICE_QUESTION WHERE `exercice_id` = '$exid'";
 $sqlresult =mysql_query($sqlquery);
 $rowi = mysql_result($sqlresult,0);
 echo $rowi.' Question(s)'; ?> </td>
	
	<td align='center'><?php 
		$eid = $row['id'];
	$uid= $_SESSION[_user][user_id];
	$qry = "select * from `".$TBL_TRACK_EXERCICES."` where exe_exo_id = $eid and exe_user_id = $uid";
	$qryres = api_sql_query($qry);
	$num = mysql_num_rows($qryres);
	$row = mysql_fetch_array($qryres);
	$percentage = ($row['exe_result']/$row['exe_weighting'])*100;
	if ($num>0)
		{	
			echo "Attempted (Score:";
			printf("%1.2f\n",$percentage);
			echo " %)";
		}
			else
		echo "Not Attempted"
	?></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <?php
			}
		
			// skips the last exercise, that is only used to know if we have or not to create a link "Next page"
			if($i == $limitExPage)
			{
				break;
			}
		
			$i++;
			echo "<tr><td colspan = '5'><hr></td></tr>";
		}	// end while()
		
		$ind = $i;

		
		if (($from+$limitExPage-1)>$nbrexerc)
		{	
			if($from>$nbrexerc)
			{
				$from = $from - $nbrexerc;
			  $to = $limitExPage;
			}	
			else
			{ 
				$to = $limitExPage-($nbrexerc-$from);
				$from = 0;
			}	
		}	
		if($is_allowedToEdit)
		{
			$sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility 
				FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
							WHERE   d.id = ip.ref AND ip.tool = '".TOOL_DOCUMENT."' AND 
							 (d.path LIKE '%htm%' OR d.path LIKE '%html%')			
							AND   d.path  LIKE '".$uploadPath."/%/%' LIMIT $from,$to"; // only .htm or .html files listed
			$result = api_sql_query ($sql,__FILE__,__LINE__);
			//error_log($sql,0);
		}
		else	
		{
			$sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility 
				FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip 
								WHERE d.id = ip.ref AND ip.tool = '".TOOL_DOCUMENT."' AND 
								 (d.path LIKE '%htm%' OR d.path LIKE '%html%')   
								AND   d.path  LIKE '".$uploadPath."/%/%' AND ip.visibility='1' LIMIT $from,$to";
			$result = api_sql_query($sql, __FILE__, __LINE__); // only .htm or .html files listed
			//error_log($sql,0);
		}	
		//error_log(mysql_num_rows($result),0);
		while($row = Database::fetch_array($result, 'ASSOC'))
		{	
			//error_log('hop',0);
			$attribute['path'      ][] = $row['path'      ];
			$attribute['visibility'][] = $row['visibility'];
			$attribute['comment'   ][] = $row['comment'   ];
		}
		$nbrActiveTests = 0;
		if(is_array($attribute['path']))
		{
			while(list($key,$path) = each($attribute['path']))
			{
				list($a,$vis)=each($attribute['visibility']);
				if (strcmp($vis,"1")==0)
				{ $active=1;}
				else
				{ $active=0;}
				echo "<tr>\n";
				
				$title = GetQuizName($path,$documentPath);		
				if ($title =='')
				{
					$title = GetFileName($path);		
				}
				// prof only
				if($is_allowedToEdit)
				{
					/************/
					?>
  <td width="27%"><table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td width="20" align="right"><?php echo ($ind+($page*$limitExPage)).'.'; ?><!--<img src="../img/jqz.jpg" alt="HotPotatoes" />--></td>
	   <td width="1">&nbsp;</td>
           <td><a href="showinframes.php?file=<?php echo $path?>&cid=<?php echo $_course['official_code'];?>&uid=<?php echo $_user['user_id'];?>" <?php if(!$active) echo 'class="invisible"'; ?>><?php echo $title?></a></td>
    </tr>
  </table></td>
  <td>
 </td>
  <td></td>
      <td width="12%" align="center"><a href="adminhp.php?hotpotatoesName=<?php echo $path; ?>"> <img src="../img/edit.gif" border="0" alt="<?php echo htmlentities(get_lang('Modify')); ?>" /></a> 
  <a href="<?php echo $exercicePath; ?>?hpchoice=delete&file=<?php echo $path; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('areYouSure')).$title."?"); ?>')) return false;"><img src="../img/delete.gif" border="0" alt="<?php echo htmlentities(get_lang('Delete')); ?>" /></a>
    <?php
					// if active
					if($active)
					{
						$nbrActiveTests = $nbrActiveTests + 1;
						?>
      <a href="<?php echo $exercicePath; ?>?hpchoice=disable&page=<?php echo $page; ?>&file=<?php echo $path; ?>"><img src="../img/visible.gif" border="0" alt="<?php echo htmlentities(get_lang('Deactivate')); ?>" /></a>
    <?php
					}
					// else if not active
					else
					{
						?>
    <a href="<?php echo $exercicePath; ?>?hpchoice=enable&page=<?php echo $page; ?>&file=<?php echo $path; ?>"><img src="../img/invisible.gif" border="0" alt="<?php echo htmlentities(get_lang('Activate')); ?>" /></a>
    <?php
					}
				/****************/
				?></td>
      <?php }
				// student only
				else
				{
					if ($active==1)
					{	
						$nbrActiveTests = $nbrActiveTests + 1;
						?>
    <td width="40%"><table border="0" cellpadding="0" cellspacing="0" width="100%">
     
        <td width="20" align="right"><?php echo ($ind+($page*$limitExPage)).'.'; ?><!--<img src="../img/jqz.jpg" alt="HotPotatoes" />--></td>
       <td width="1">&nbsp;</td>
        <td><a href="showinframes.php?<?php echo api_get_cidreq()."&file=".$path."&cid=".$_course['official_code']."&uid=".$_user['user_id'].'"'; if(!$active) echo 'class="invisible"'; ?>"><?php echo $title;?></a></td>
      
     </tr>
    </table></td>
  </tr>
  <?php
					}	
				}
				?>
  <?php
				if($ind == $limitExPage)
				{
					break;
				}
				if($is_allowedToEdit)
				{
					$ind++;
				}
				else 
				{
					if ($active==1)
					{
						$ind++;
					}
				}echo "<tr><td colspan = '5'><hr></td></tr>";
			}
		}
		
		
	} //end if ($origin != 'learnpath') {
	?>
</table>
<?php
}else{
	if($origin != 'learnpath'){
		echo '<a href="'.api_add_url_param($_SERVER['REQUEST_URI'],'show=test').'">&lt;&lt; '.get_lang('Back').'</a>';
	}
}// end if($show == 'test')

/*****************************************/
/* Exercise Results (uses tracking tool) */
/*****************************************/

// if tracking is enabled
if($_configuration['tracking_enabled'])
{
	?>
<br>
<br> 
 <h3><?php 
 //add link to breadcrumb
 //$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('StudentScore'));

 echo $is_allowedToEdit?get_lang('StudentResults'):get_lang('YourResults'); ?></h3>

	<?php
	if($show == 'result'){
			if($is_allowedToEdit)
		{
		if ($_REQUEST['comments']=='update')
				{
					
					$id  = $_GET['exeid'];
					$emailid = $_GET['emailid'];
					$test  = $_GET['test'];
					$from = $_SESSION[_user]['mail'];
					$from_name = $_SESSION[_user]['firstName']." ".$_SESSION[_user]['lastName'];
					$url = $_SESSION['checkDokeosURL'].'claroline/exercice/exercice.php?'.api_get_cidreq().'&show=result';
					foreach ($_POST as $key=>$v)
						{
						$keyexp = explode('_',$key);
						if ($keyexp[0] == "marks")
							{
							$sql = "select question from $TBL_QUESTIONS where id = '$keyexp[1]'";
							$result =api_sql_query($sql, __FILE__, __LINE__);
							$ques_name = mysql_result($result,0,"question");
							
							$query = "update `$TABLETRACK_ATTEMPT` set marks = '$v' where question_id = $keyexp[1] and exe_id=$id";
							api_sql_query($query, __FILE__, __LINE__);
							
							$qry = "select sum(marks) as tot from `".$TABLETRACK_ATTEMPT."` where exe_id = $id";
							
							$res = api_sql_query($qry,__FILE__,__LINE__);
							$tot = mysql_result($res,0,'tot');
							
							$totquery = "update `$TBL_TRACK_EXERCICES` set exe_result = $tot where exe_Id=$id";
							api_sql_query($totquery, __FILE__, __LINE__);
							
							}
						else
						  {
						  $query = "update `$TABLETRACK_ATTEMPT` set teacher_comment = '$v' where question_id = $keyexp[1] and exe_id = $id ";
						   api_sql_query($query, __FILE__, __LINE__);
							}
						
						
						}
				$subject = "Examsheet viewed/corrected/commented by teacher";
				/*$message = "<html>
<head>
<style type='text/css'>
<!--
.body{
font-family: Verdana, Arial, Helvetica, sans-serif; 
font-weight: Normal; 
color: #000000; 
}
.style8 {font-family: Verdana, Arial, Helvetica, sans-serif; font-weight: bold; color: #006699; }
.style10 {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
}
.style16 {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; }
-->
</style>
</head>
<body>
<DIV>
  <p>Dear Student, </p>
  <p class='style10'> Your following attempt has been viewed/commented/corrected by teacher </p>
  <table width='417'>
    <tr>
      <td width='229' valign='top' bgcolor='E5EDF8'>&nbsp;&nbsp;<span class='style10'>Question</span></td>
      <td width='469' valign='top' bgcolor='#F3F3F3'><span class='style16'>#ques_name#</span></td>
  
    </tr>
    <tr>
      <td width='229' valign='top' bgcolor='E5EDF8'>&nbsp;&nbsp;<span class='style10'>Test</span></td>
       <td width='469' valign='top' bgcolor='#F3F3F3'><span class='style16'>#test#</span></td>
  
    </tr>
  </table>
  <p>Click the link below to access   your account and view your commented Examsheet. <A href='#url#'>#url#</A><BR>
    <BR>
  Regards </p>
  </DIV>
  </body>
  </html>
";*/
$message = "<p>You attempt for the test #test# has been viewed/commented/corrected by the teacher. Click the link below to access  your account and view your Examsheet. <A href='#url#'>#url#</A></p>
    <BR>";
				$mess= str_replace("#test#",$test,$message);
				//$message= str_replace("#ques_name#",$ques_name,$mess);
				$message = str_replace("#url#",$url,$mess);
				$mess = stripslashes($message);
				$headers  = " MIME-Version: 1.0 \r\n";
				$headers .= "User-Agent: Dokeos/1.6";
				$headers .= "Content-Transfer-Encoding: 7bit";
				$headers .= 'From: '.$from_name.' <'.$from.'>' . "\r\n";
				$headers="From:$from_name\r\nReply-to: $to\r\nContent-type: text/html; charset=iso-8859-15";
			
			//mail($emailid, $subject, $mess,$headers);
			
								
						
				}
				}
		?>

		<table cellpadding="2" cellspacing="2" border="0" width="100%">
		 <tr bgcolor="#E6E6E6" align="center">
		  <?php if($is_allowedToEdit): ?>
			<td width="20%"><?php echo get_lang("User"); ?></td><?php endif; ?>
		  <td width="<?php if($is_allowedToEdit) echo '35'; else echo '55'; ?>%"><?php echo get_lang("Exercice"); ?></td>
		  <td width="30%"><?php echo get_lang("Date"); ?></td>
		  <td width="15%"><?php echo get_lang("Result"); ?></td>
		  <td width="15%"><?php echo $is_allowedToEdit?"Correct Test":"View Test"; ?></td>
		  
		 
		 </tr> 
		
		<?php
		if($is_allowedToEdit)
		{
			//get all results (ourself and the others) as an admin should see them
			//AND exe_user_id <> $_user['user_id']  clause has been removed
			$sql="SELECT CONCAT(`lastname`,' ',`firstname`),`ce`.`title`, `te`.`exe_result` , 
						`te`.`exe_weighting`, UNIX_TIMESTAMP(`te`.`exe_date`),`te`.`exe_Id`,email
				  FROM $TBL_EXERCICES AS ce , `$TBL_TRACK_EXERCICES` AS te, $TBL_USER AS user
				  WHERE `te`.`exe_exo_id` = `ce`.`id` AND `user_id`=`te`.`exe_user_id` AND `te`.`exe_cours_id`='$_cid'
				  ORDER BY `te`.`exe_cours_id` ASC, `ce`.`title` ASC, `te`.`exe_date`ASC";
		
			$hpsql="SELECT CONCAT(tu.lastname,' ',tu.firstname), tth.exe_name, 
						tth.exe_result , tth.exe_weighting, UNIX_TIMESTAMP(tth.exe_date)
					FROM `$TBL_TRACK_HOTPOTATOES` tth, $TBL_USER tu
					WHERE  tu.user_id=tth.exe_user_id AND tth.exe_cours_id = '".$_cid."'
					ORDER BY tth.exe_cours_id ASC, tth.exe_date ASC";
					  
		}
		else
		{ // get only this user's results
			  $sql="SELECT '',`ce`.`title`, `te`.`exe_result` , `te`.`exe_weighting`, UNIX_TIMESTAMP(`te`.`exe_date`),`te`.`exe_Id`
				  FROM $TBL_EXERCICES AS ce , `$TBL_TRACK_EXERCICES` AS te
				  WHERE `te`.`exe_exo_id` = `ce`.`id` AND `te`.`exe_user_id`='".$_user['user_id']."' AND `te`.`exe_cours_id`='$_cid'
				  ORDER BY `te`.`exe_cours_id` ASC, `ce`.`title` ASC, `te`.`exe_date`ASC";
		
			$hpsql="SELECT '',exe_name, exe_result , exe_weighting, UNIX_TIMESTAMP(exe_date)
					FROM `$TBL_TRACK_HOTPOTATOES`
					WHERE exe_user_id = '".$_user['user_id']."' AND exe_cours_id = '".$_cid."'      
					ORDER BY exe_cours_id ASC, exe_date ASC";
		
		}
		
		$results=getManyResultsXCol($sql,7);
		$hpresults=getManyResultsXCol($hpsql,5);
		
		$NoTestRes = 0;
		$NoHPTestRes = 0;
		
		if(is_array($results))
		{
			for($i = 0; $i < sizeof($results); $i++)
			{
				$id = $results[$i][5];
				$mailid = $results[$i][6];
		?>
		 <tr>
		  <?php if($is_allowedToEdit): ?>
			<td class="content"><?php $user = $results[$i][0]; echo $results[$i][0]; ?></td><?php endif; ?>
		  <td class="content"><?php $test = $results[$i][1]; echo $results[$i][1]; ?></td>
		  <td class="content" align="center"><?php $dt = strftime($dateTimeFormatLong,$results[$i][4]); echo strftime($dateTimeFormatLong,$results[$i][4]); ?></td>
		  <td class="content" align="center"><?php $res = $results[$i][2]; echo $results[$i][2]; ?> / <?php echo $results[$i][3]; ?></td>
		 <td class="content" align="center"><?php echo $is_allowedToEdit?"<a href='exercise_show.php?user=$user&test=$test&dt=$dt&res=$res&id=$id&email=$mailid'>Edit</a>":"<a href='exercise_show.php?test=$test&dt=$dt&res=$res&id=$id'>Show</a>"?></td>

		 </tr>
		
		<?php
			
			}
		}
		else
		{
				$NoTestRes = 1;	
		}
		
		// The Result of Tests
		if(is_array($hpresults))
		{
		
			for($i = 0; $i < sizeof($hpresults); $i++)
			{
				$title = GetQuizName($hpresults[$i][1],$documentPath);			
				if ($title =='')
				{
					$title = GetFileName($hpresults[$i][1]);		
				}		
		?>
		<tr>
		<?php if($is_allowedToEdit): ?>
			<td class="content"><?php echo $hpresults[$i][0]; ?></td><?php endif; ?>
		  <td class="content"><?php echo $title; ?></td>  
		  <td class="content" align="center"><?php echo strftime($dateTimeFormatLong,$hpresults[$i][4]); ?></td>
		  <td class="content" align="center"><?php echo $hpresults[$i][2]; ?> / <?php echo $hpresults[$i][3]; ?></td>
		</tr>
		
		<?php
			}
		}
		else
		{
			$NoHPTestRes = 1;
		}
		
		
		
		if ($NoTestRes==1 && $NoHPTestRes==1)
		{
		?>
		
		 <tr>
		  <td colspan="3"><?php echo get_lang("NoResult"); ?></td>
		 </tr>
		
		<?php
		}
		
		?>
		
		</table>
		
		<?php
	}else{

		echo '<p><a href="'.api_add_url_param($_SERVER['REQUEST_URI'],'show=result').'">'.get_lang("Show").' &gt;&gt;</a></p>';

	}// end if($show == 'result')

}// end if tracking is enabled

if ($origin != 'learnpath') { //so we are not in learnpath tool
	Display::display_footer();
} else {
	?> 
	<link rel="stylesheet" type="text/css" href="<?php echo $clarolineRepositoryWeb ?>css/default.css" /> 
	<?php
}
?>