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
*	API event handler functions for Scorm 1.1 and 1.2 and 1.3
*
*	@author   Denes Nagy <darkden@freemail.hu>
*	@version  v 1.0
*	@access   public
*	@package dokeos.scorm
============================================================================== 
*/

/*
============================================================================== 
	   INIT SECTION
============================================================================== 
*/ 

// if you open the imsmanifest.xml via local machine (f.ex.: file://c:/...), then the Apiwrapper.js
// of Maritime Navigation when trying to execute this row
//    var result = api.LMSInitialize("");
// get the error response : you are not authorized to call this function


include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$TBL_SCORM_SCO_DATA=$scormDbName.".scorm_sco_data";
$old_s_identifier	= $_SESSION['old_sco_identifier'];
$my_s_identifier	= $_SESSION['s_identifier'];
$file							= $_SESSION['file'];

//in some cases (manual clicks), there is no "old" s_identifier because there is no "new" one.
if(empty($old_s_identifier)){
	$old_s_identifier = $my_s_identifier;
}

/*
============================================================================== 
		JavaScript Functions
============================================================================== 
*/ 
?>
<html><head><script type='text/javascript'>
/* <![CDATA[ */
var alerts=0; //debug output level. 0 = none, 1=light, 2=a lot, 3(not implemented)=all
if (alerts>1) { alert('scormfunctions.php included'); }

function APIobject() {
  this.LMSInitialize=LMSInitialize;  //for Scorm 1.2
  this.Initialize=LMSInitialize;     //for Scorm 1.3
  this.LMSGetValue=LMSGetValue;
  this.GetValue=LMSGetValue;
  this.LMSSetValue=LMSSetValue;
  this.SetValue=LMSSetValue;
  this.LMSCommit=LMSCommit;
  this.Commit=LMSCommit;
  this.LMSFinish=LMSFinish;
  this.Finish=LMSFinish;
  this.LMSGetLastError=LMSGetLastError;
  this.GetLastError=LMSGetLastError;
  this.LMSGetErrorString=LMSGetErrorString;
  this.GetErrorString=LMSGetErrorString;
  this.LMSGetDiagnostic=LMSGetDiagnostic;
  this.GetDiagnostic=LMSGetDiagnostic;
  this.Terminate=Terminate;  //only in Scorm 1.3
}

//it is not sure that the scos use the above declarations

API = new APIobject(); //for scrom 1.2
api = new APIobject(); //for scrom 1.2
API_1484_11 = new APIobject();  //for scrom 1.3
api_1484_11 = new APIobject();  //for scrom 1.3

var G_NoError = 0;
var G_GeneralException = 101;
var G_ServerBusy = 102;
var G_InvalidArgumentError = 201;
var G_ElementCannotHaveChildren = 202;
var G_ElementIsNotAnArray = 203;
var G_NotInitialized = 301;
var G_NotImplementedError = 401;
var G_InvalidSetValue = 402;
var G_ElementIsReadOnly = 403;
var G_ElementIsWriteOnly = 404;
var G_IncorrectDataType = 405;

var G_LastError = G_NoError ;

var commit = false ;

var score=0;
var max=0;
var min=0;
var lesson_status='';
var session_time=0;

function LMSInitialize() {  //this is the initialize function of all APIobjects
  if (alerts>0) { alert('LMSInitialise() called (by SCORM content)'); }
  //initialise the lesson status between two lessons, to avoid status override
  lesson_status = '';
  return('true');
}

function Initialize() {  //this is the initialize function of all APIobjects
  return LMSInitialize();
}


function LMSGetValue(param) {
	var result;
	switch(param) {
	case 'cmi.core._children'		:
	case 'cmi.core_children'		:
result='entry, exit, lesson_status, student_id, student_name, lesson_location, total_time, credit, lesson_mode, score, session_time';		break;
	case 'cmi.core.entry'			: result='';		break;
	case 'cmi.core.exit'			: result='';		break;
	case 'cmi.core.lesson_status'	: 
    if(lesson_status != '') {result=lesson_status;}
    else{<?php
        $result = api_sql_query("SELECT status FROM $TBL_SCORM_SCO_DATA WHERE (studentId='".$_user['user_id']."' and scoIdentifier='$my_s_identifier')");
        $ar=mysql_fetch_array($result);
        $status=$ar['status'];
        if(empty($ar['status'])){$status = "not attempted";}
        #echo "{ if (alerts>1) { alert('Status of $s_identifier : $status'); } 
        # TODO: implement this better thanks to the runtime environment doc of SCORM
        echo " result='$status';";?>
        } 
    break;
	case 'cmi.core.student_id'	   : <?php echo "result='".$_user['user_id']."';"; ?> break;
	case 'cmi.core.student_name'	: 
	  <?php
		$who=$_user ['firstName']." ".$_user ['lastName'];
	    	echo "{ result='$who'; }"; 
	  ?>	break;
	case 'cmi.core.lesson_location'	: result='';		break;
	case 'cmi.core.total_time'	: result='0000:00:00.00';break;
	case 'cmi.core.score._children'	: result='raw,min,max';	break;
	case 'cmi.core.score.raw'	: result=score;		break;
	case 'cmi.core.score.max'	: result='100';		break;
	case 'cmi.core.score.min'	: result='0';		break;
	case 'cmi.core.score'		: result='0';		break;
	case 'cmi.core.credit'		: result='credit';	break;
	case 'cmi.core.lesson_mode'	: result='normal';	break;
	case 'cmi.suspend_data'		: result='';		break;
	case 'cmi.launch_data'		: result='';		break;
	case 'cmi.objectives._count'	: result='0';		break;
	default : 			  result='';		break;
	}
    	if (alerts>0) { alert("SCORM calls LMSGetValue('"+param+"')\nReturned '"+result+"'"); }
	return result;
}

function GetValue(param) {
	return LMSGetValue(param);
}

function LMSSetValue(param, val) {
    if (alerts>0) { alert("SCORM calls LMSSetValue('"+param+"','"+val+"')"); }
	switch(param) {
	case 'cmi.core.score.raw'		: score= val ;			break;
	case 'cmi.core.score.max'		: max = val;			break;
	case 'cmi.core.score.min'		: min = val;			break;
	case 'cmi.core.lesson_status'	: lesson_status = val;	break;
	case 'cmi.completion_status'	: lesson_status = val;	break; //1.3
	case 'cmi.core.session_time'	: session_time = val;	break;
	case 'cmi.score.scaled'			: score = val ;			break; //1.3
	case 'cmi.success_status'		: success_status = val; break; //1.3
	}
	return(true);
}

function SetValue(param, val) {
	return LMSSetValue(param, val);
}

function savedata(origin) { //origin can be 'commit', 'finish' or 'terminate'
    //if( ( lesson_status == 'incomplete') && (score >= (0.8*max) ) ){
    //  lesson_status = 'completed';
    //}
    param = 'origin='+origin+'&score='+score+'&max='+max+'&min='+min+'&lesson_status='+lesson_status+'&time='+session_time;
	
    url="http://<?php
    $self=$_SERVER['PHP_SELF'];
    $url=$_SERVER['HTTP_HOST'].$self;
    $url=substr($url,0,-19);//19 is the length of this file's name (/scormfunctions.php)
    echo $url;
    ?>/closesco.php?<?php echo "sco_identifier=$old_s_identifier&file=$file&"; ?>" + param + "";
    scowindow=open(url,'message');
    //the window.location command does not work here !!!!
    //and for some reason if I just call closesco.php without http//..., it does not work either
    if (alerts>1) { alert('saving data : '+url); }
}

function LMSCommit(val) {
    if (alerts>0) { alert('LMSCommit() called'); }
	commit = true ;
	savedata('commit');
	return('true');
}

function Commit(val) {
	return LMSCommit(val);
}

function LMSFinish(val) {
  if (( commit == false ) && (alerts>0)) { alert('LMSFinish() called without LMSCommit()'); }
	if ( commit == true ) {
   if(alerts>0) { alert('LMSFinish() called');}
		savedata('finish');
	}
	return('true');
}

function Finish(val) {
	return LMSFinish(val);
}

function LMSGetLastError() {
    if (alerts>1) { alert('LMSGetLastError() called'); }
	return(G_LastError);
}

function GetLastError() {
	return LMSGetLastError();
}

function LMSGetErrorString(errCode){
    if (alerts>1) { alert('LMSGetErrorString() called'); }
	return('No error !');
}

function GetErrorString(errCode){
	return LMSGetErrorString(errCode);
}

function LMSGetDiagnostic(errCode){
    if (alerts>1) { alert('LMSGetDiagnostic() called'); }
	return(API.LMSGetLastError());
}

function GetDiagnostic(errCode){
	return LMSGetDiagnostic(errCode);
}

function Terminate(){
	if (alerts>0) { alert('Terminate() called'); }
	commit = true;
	savedata('terminate');
	return (true);
}
/* ]]> */
</script>
</head>
<body><i><b>This is an API / API_1484_11 system window. Nothing to be worried about!</b></i></body></html>
