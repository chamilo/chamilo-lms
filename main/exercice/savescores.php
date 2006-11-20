<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Istvan Mandak
	
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
*	Code library for HotPotatoes integration.
*
*	@author Istvan Mandak
*	@package dokeos.exercise
============================================================================== 
*/
$langFile = 'learnpath';
include ('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');

$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
$full_file_path = $documentPath.$test;

my_delete($full_file_path.$_user['user_id'].".t.html");

$TABLETRACK_HOTPOTATOES = $statsDbName."`.`track_e_hotpotatoes";
$tbl_learnpath_user = Database::get_course_table(LEARNPATH_USER_TABLE);
//$_course['dbNameGlu']."learnpath_user";
$_cid = $cid;
$test = mysql_real_escape_string($_REQUEST['test']);
$score = mysql_real_escape_string($_REQUEST['score']);
$origin = $_REQUEST['origin'];

function save_scores($file, $score)
{
	global $_configuration, $origin, $tbl_learnpath_user, 
		$learnpath_id, $learnpath_item_id, $_user, $_cid, 
		$TABLETRACK_HOTPOTATOES;
	// if tracking is disabled record nothing
	$weighting = 100; // 100%
	$reallyNow = time();
	$date = date("Y-m-d H:i:s", $reallyNow);

	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	if ($_user['user_id'])
	{
		$user_id = "'".$_user['user_id']."'";
	}
	else // anonymous
		{
		$user_id = "NULL";
	}
	$sql = "INSERT INTO `".$TABLETRACK_HOTPOTATOES."`
			  (`exe_name`,
			   `exe_user_id`,
			   `exe_date`,		   
			   `exe_cours_id`,
			   `exe_result`,
			   `exe_weighting`		   
			  )
	
			  VALUES
			  (
			   '".$file."',
			   ".$user_id.",		   
				 '".$date."',	
			   '".$_cid."',
			   '".$score."',
			   '".$weighting."'
			  )";
	error_log($sql,0);
	if ($origin == 'learnpath')
	{
		if ($user_id == "NULL")
		{
			$user_id = '0';
		}
		$sql2 = "update $tbl_learnpath_user 
			set score='$score' 
			where (user_id=$user_id and learnpath_id='$learnpath_id' and learnpath_item_id='$learnpath_item_id')";
		error_log($sql,0);
		$res2 = api_sql_query($sql2,__FILE__,__LINE__);
	}
	$res = api_sql_query($sql,__FILE__,__LINE__);
}

// Save the Scores
save_scores($test, $score);
// Back
if ($origin != 'learnpath')
{
	// $url = "Hpdownload.php?doc_url=".$test."&cid=".$cid; // back to the test
	$url = "exercice.php"; // back to exercices
	echo "<SCRIPT LANGUAGE='JavaScript' type='text/javascript'>";
	echo "window.open('$url', '_top', '')"; // back to exercices
	echo "</SCRIPT> ";
}
else
{
?>
		<html>
		<head>
		<link rel='stylesheet' type='text/css' href='../css/scorm.css' />
		</head>
		<body>
		<br /><div class='message'>
		<?php echo get_lang('HotPotatoesFinished'); ?>
		</div>
		</body>
		</html>
		<?php

}
?>