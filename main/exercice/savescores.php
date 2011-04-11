<?php
/* For licensing terms, see /license.txt */

/**
*	Saving the scores.
*	@package chamilo.exercise
* 	@author
* 	@version $Id: savescores.php 15602 2008-06-18 08:52:24Z pcool $
*/

// name of the language file that needs to be included
$language_file = 'learnpath';

if ($_GET['origin'] == 'learnpath') {
	require_once '../newscorm/learnpath.class.php';
	require_once '../newscorm/learnpathItem.class.php';
	require_once '../newscorm/scorm.class.php';
	require_once '../newscorm/scormItem.class.php';
	require_once '../newscorm/aicc.class.php';
	require_once '../newscorm/aiccItem.class.php';
}

require_once '../inc/global.inc.php';
$this_section=SECTION_COURSES;

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
$full_file_path = $documentPath.$test;

my_delete($full_file_path.$_user['user_id'].".t.html");

$TABLETRACK_HOTPOTATOES = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
$tbl_learnpath_user 	= Database::get_course_table(TABLE_LEARNPATH_USER);
$TABLE_LP_ITEM_VIEW 	= Database::get_course_table(TABLE_LP_ITEM_VIEW);

$_cid = api_get_course_id();
$test = $_REQUEST['test'];
$score = $_REQUEST['score'];
$origin = $_REQUEST['origin'];
$jscript2run = '';

/**
 * Save the score for a HP quiz. Can be used by the learnpath tool as well
 * for HotPotatoes quizzes. When coming from the learning path, we
 * use the session variables telling us which item of the learning path has to
 * be updated (score-wise)
 * @param	string	File is the exercise name (the file name for a HP)
 * @param	integer	Score to save inside the tracking tables (HP and learnpath)
 * @return	void
 */
function save_scores($file, $score) {
	global $origin, $_user, $_cid,
		$TABLETRACK_HOTPOTATOES;
	// if tracking is disabled record nothing
	$weighting = 100; // 100%
	$reallyNow = time();
	$date = date("Y-m-d H:i:s", $reallyNow);

	if ($_user['user_id']) {
		$user_id = $_user['user_id'];
	} else {
		// anonymous
		$user_id = "NULL";
	}
	$sql = "INSERT INTO $TABLETRACK_HOTPOTATOES ".
			"(exe_name, exe_user_id, exe_date,exe_cours_id,exe_result,exe_weighting)" .
			"VALUES" .
			"(
			'".Database::escape_string($file)."',
			'".Database::escape_string($user_id)."',
			'".Database::escape_string($date)."',
			'".Database::escape_string($_cid)."',
			'".Database::escape_string($score)."',
			'".Database::escape_string($weighting)."')";
	$res = Database::query($sql);

	if ($origin == 'learnpath') {
		//if we are in a learning path, save the score in the corresponding
		//table to get tracking in there as well
	    global $jscript2run;
		//record the results in the learning path, using the SCORM interface (API)
	    $jscript2run .= '<script language="javascript" type="text/javascript">window.parent.API.void_save_asset('.$score.','.$weighting.');</script>';
	}
}

// Save the Scores
save_scores($test, $score);

// Back
if ($origin != 'learnpath') {
	// $url = "Hpdownload.php?doc_url=".$test."&cid=".$cid; // back to the test
	$url = "exercice.php"; // back to exercices
	$jscript2run .= '<script language="javascript" type="text/javascript">'."window.open('$url', '_top', '')".'</script>';
	echo $jscript2run;
} else {
?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<link rel='stylesheet' type='text/css' href='../css/<?php echo api_get_setting('stylesheets');?>/scorm.css' />
<?php echo $jscript2run; ?>
</head>
<body dir="<?php echo api_get_text_direction(); ?>">
<br />
<div class='message'>
<?php echo get_lang('HotPotatoesFinished'); ?>
</div>
</body>
</html>
<?php
}
?>