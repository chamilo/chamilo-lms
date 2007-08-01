<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/


/**
*	Saving the scores.
*	@package dokeos.exercise
* 	@author
* 	@version $Id: savescores.php 12830 2007-08-01 17:22:06Z yannoo $
*/

// name of the language file that needs to be included
$language_file = 'learnpath';
include ('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');

$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
$full_file_path = $documentPath.$test;

my_delete($full_file_path.$_user['user_id'].".t.html");



$TABLETRACK_HOTPOTATOES = $_configuration['statistics_database']."`.`track_e_hotpotatoes";
$tbl_learnpath_user = Database::get_course_table(TABLE_LEARNPATH_USER);
$TABLE_LP_ITEM_VIEW = Database::get_course_table(TABLE_LP_ITEM_VIEW);
$_cid = api_get_course_id();
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
	
	

	
 	//error_log($sql,0);
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
//score dans lp_item_view

				$user_id = (!empty($_SESSION['_user']['id'])?$_SESSION['_user']['id']:0);
               // $lp_item = Database::get_course_table('lp_item');
                $lp_item_view = Database::get_course_table('lp_item_view');
                $sql2 = "UPDATE $lp_item_view SET score = '$score'
                        WHERE lp_view_id = '".$_SESSION['scorm_view_id']."'";
                api_sql_query($sql2,__FILE__,__LINE__);
              

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
