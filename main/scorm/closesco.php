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
============================================================================== 
*/

// incoming data from scormfunctions.js : $score - $max - $min - $lesson_status - $time
$langFile = "scorm"; 
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

//include('XMLencode.php');
//$charset = GetXMLEncode($_GET['file']);
header('Content-Type: text/html; charset='. $charset);
//error_log($_SERVER['REQUEST_URI'],0);
//error_log("Starting closesco",0);

$lesson_status 	= $_GET['lesson_status'];
$score								= $_GET['score'];
$time									= $_GET['time'];
$my_sco_identifier			= $_GET['sco_identifier'];
$contentId					= $_SESSION['contentId'];
$items								= $_SESSION['items'];
$origin							= $_GET['origin'];
$max									= $_GET['max'];
$min									= $_GET['min'];
$file									= $_GET['file'];
$_uid									= $_SESSION['_uid'];

$charset_lang = 'ISO-8859-15';

$array_status=array('completed' => htmlentities(get_lang('ScormCompstatus'),ENT_QUOTES,$charset_lang),
					'passed' => htmlentities(get_lang('ScormPassed'),ENT_QUOTES,$charset_lang),
					'failed' => htmlentities(get_lang('ScormFailed'),ENT_QUOTES,$charset_lang),
					'incomplete' => htmlentities(get_lang('ScormIncomplete'),ENT_QUOTES,$charset_lang),
					'not attempted' => htmlentities(get_lang('ScormNotAttempted'),ENT_QUOTES,$charset_lang));
//although we defined 'not attempted' here as a common status, it shouldn't be used in
// the context of this script, which is only for content closing purposes

//save this sco-id in session so that we can check what last sco has been closed
$_SESSION['last_sco_closed'] = $my_sco_identifier;

if ($lesson_status=='') { $lesson_status='incomplete'; }
	
$TBL_SCORM_SCO_DATA = Database::get_scorm_sco_data_table();

//prepare a small reverse-index of "clusterinfo"=>"item index"
$items_clusterinfo_dictionary = array();
foreach($items as $key=>$content){
	$items_clusterinfo_dictionary[$content['clusterinfo']]=$key;
}

if($_SESSION['dont_save_last']!=true){
	//error_log("Element $my_sco_identifier can be saved (no conditions preventing it)",0);
	//check if there are dependent clusters
	$sub_cluster_completed = true;
	$base_child_cluster = $items[$_SESSION['items_dictionary'][$my_sco_identifier]]['clusterinfo']*10;
	for ($i = $base_child_cluster; $i<($base_child_cluster+10);$i++){
		$j = $items_clusterinfo_dictionary[$i];
		if(is_array($items[$j])){
			//error_log('items['.$j.'] is an array',0);
			$sql = "SELECT status FROM $TBL_SCORM_SCO_DATA 
				WHERE (contentId='$contentId' and studentId='$_uid' and scoIdentifier='".$items[$j]['identifier']."')";
			$result = api_sql_query($sql,__FILE__,__LINE__);
			$ar=mysql_fetch_array($result);
			$sub_lesson_status=$ar['status'];
			//error_log('status: '.$sub_lesson_status,0);
			if ((($sub_lesson_status)<>'completed') and ($sub_lesson_status <> 'passed' )) { $sub_cluster_completed=false; }
		}
	}
	if($sub_cluster_completed){
		//error_log("Element $my_sco_identifier has no incomplete children, change status to $lesson_status",0);
		$sql="UPDATE $TBL_SCORM_SCO_DATA SET score='$score', status='$lesson_status', time='$time' WHERE (studentId='$_uid' and scoIdentifier='$my_sco_identifier' and contentId='$contentId')";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		//error_log($sql,0);
	}else{
		//error_log("Element $my_sco_identifier has incomplete children, set status to incomplete)",0);
		$sql="UPDATE $TBL_SCORM_SCO_DATA SET score='$score', status='incomplete', time='$time' WHERE (studentId='$_uid' and scoIdentifier='$my_sco_identifier' and contentId='$contentId')";
		$result = api_sql_query($sql,__FILE__,__LINE__);
	}
}else{
	//error_log("Element $my_sco_identifier cannot be saved (restart conditions preventing it)",0);
}
/*==================================================================================
  SEARCHING FOR COMPLETE CLUSTERS AND IF ANY, UPDATE THEM TO COMPLETED IF NECCESSARY
  ==================================================================================*/

//if ($lesson_status=='completed') {
$clustercompleted=true;
//$i=0; //get to the current element
$i = $_SESSION['items_dictionary'][$my_sco_identifier];
//do {
//  $i++;
//} while (($items[$i]['identifier'] != $my_sco_identifier) and ($i <= count($items)));
// now $i is the index of the current element
//$items[$i]['status'] = $lesson_status;
//api_session_unregister($items);
//api_session_register($items);


//now check if the current element completes the cluster
//a. get the parent's clusterinfo (if clusterinfo = 23, parent is 2)
$startcluster=floor(($items[$i]['clusterinfo'])/10);
//b. get through all elements of the same cluster (all elements with clusterinfo of 2* - max 10 elems)
for ($cluster=($startcluster*10); (($cluster<=($startcluster*10+9)) && ($clustercompleted==true)); $cluster++) {
	//$i=0;
	//do {
	//  $i++;
	//} while (($items[$i]['clusterinfo'] != $cluster) and ($i <= count($items)));
	//get index of parent cluster
	$i = (!empty($items_clusterinfo_dictionary[$cluster])?$items_clusterinfo_dictionary[$cluster]:0);
	$id=$items[$i]['identifier'];
	$sql = "SELECT status FROM $TBL_SCORM_SCO_DATA 
		WHERE (contentId='$contentId' and studentId='$_uid' and scoIdentifier='$id')";
	$result = api_sql_query($sql,__FILE__,__LINE__);
	$ar=mysql_fetch_array($result);
	$cluster_lesson_status=$ar['status'];
	if ((is_array($items[$i])) and ((($cluster_lesson_status)<>'completed') and ($cluster_lesson_status <> 'passed' ))) { $clustercompleted=false; }
}

if ($clustercompleted) { //if every sub-element of this cluster was completed
	//$i=0;
	//do {
	//  $i++;
	//} while (($items[$i]['clusterinfo'] != $startcluster) and ($i <= count($items)));
	$i = (!empty($items_clusterinfo_dictionary[$startcluster])?$items_clusterinfo_dictionary[$startcluster]:0);	
	$my_sco_identifier=$items[$i]['identifier'];
	$sql="UPDATE $TBL_SCORM_SCO_DATA SET status='completed' WHERE (studentId='$_uid' and scoIdentifier='$my_sco_identifier' and contentId='$contentId')";
	//echo $sql;
	$result = api_sql_query($sql,__FILE__,__LINE__);
} else { // if at least one element was not completed
	//$i=0;
	//do {
	//  $i++;
	//} while (($items[$i]['clusterinfo'] != $startcluster) and ($i <= count($items)));
	$i = (!empty($items_clusterinfo_dictionary[$startcluster])?$items_clusterinfo_dictionary[$startcluster]:0);	
	$my_sco_identifier=$items[$i]['identifier'];
	$sql="UPDATE $TBL_SCORM_SCO_DATA SET status='incomplete' WHERE (studentId='$_uid' and scoIdentifier='$my_sco_identifier' and contentId='$contentId')";
	//echo $sql;
	$result = api_sql_query($sql,__FILE__,__LINE__);
}
//}

//$origin can be 'terminate', 'finish' or 'commit'

/*=============================================
  SENDING MESSAGE ABOUT STATUS TO MESSAGE FRAME
  =============================================*/
?>
<html><head><link rel='stylesheet' type='text/css' href='../css/scorm.css'>
<script type='text/javascript'>
/* <![CDATA[ */
function reloadcontents() {
	newloc=addtime(parent.contents.document.location);
	top.contents.document.location=newloc; //in order to refresh
}
function addtime(input) {
	rnd=Math.random()*100;
	if (input.toString().indexOf("#")==-1) {
		newstring=input+'&rnd='+rnd;
		return(newstring);
	} else {
		t=input.toString().indexOf("#");
		sub1=input.toString().substr(0,t);
		sub2=input.toString().substr(t,input.length);
		newstring=sub1+'&rnd='+rnd+sub2;
		return(newstring);
	}
}
/* ]]> */
</script>
</head><body onload='javascript:reloadcontents();'>

<?php
if (($lesson_status=='completed') or ($lesson_status=='passed'))
{ 
	//echo "<img src='../img/right.gif' alt='right'>";
	echo "<div class='message'>"
				.htmlentities(get_lang('ScormThisStatus'),ENT_QUOTES,$charset_lang)." : ".$array_status[$lesson_status]
			."	</div>"; 
}
else 
{ 
	//echo "<img src='../img/wrong.gif' alt='wrong'>";
	echo "<div class='message'>"
				.htmlentities(get_lang('ScormThisStatus'),ENT_QUOTES,$charset_lang)." : ".$array_status[$lesson_status]
			."</div>";
}
echo "</body></html>";
?>