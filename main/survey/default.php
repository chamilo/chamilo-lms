<?php
require_once ('../inc/global.inc.php');
$surveyid = $_GET['surveyid'];
$groupid = $_GET['groupid'];
$cidReq = $_GET['cidReq'];
$qtype = $_GET['qtype'];
$qid = $_GET['qid'];
$db_name = $_REQUEST['db_name'];
switch($qtype)
{
case "Yes/No":
	include("question.php");
	break;
case "Multiple Choice (single answer)":
	include("mcsa_view.php");	
	break;
case "Multiple Choice (multiple answer)":
    include("mcma_view.php");	
	break;
case "Open Answer":
	include("open_view.php");	
	break;
case "Numbered":
	include("numbered_view.php");
	break;
}
?>