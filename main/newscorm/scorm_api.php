<?php // $Id: scorm_api.php 18737 2009-02-27 15:40:32Z juliomontoya $ 
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) Denes Nagy (darkden@freemail.hu)
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
*	API event handler functions for Scorm 1.1 and 1.2 and 1.3
*
*	@author   Denes Nagy <darkden@freemail.hu>
*   @author   Yannick Warnier <ywarnier@beeznest.org>
*	@version  v 1.0
*	@access   public
*	@package dokeos.learnpath
============================================================================== 
*/
/**
 * This script is divided into three sections. 
 * The first section (below) is the initialisation part.
 * The second section is the SCORM object part
 * The third section defines the event handlers for Dokeos' internal messaging
 * and frames refresh
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

//flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;

require_once('back_compat.inc.php');
require_once('learnpath.class.php');
require_once('learnpathItem.class.php');
require_once('scorm.class.php');

// Is this needed? This is probabaly done in the header file
//$_user							= $_SESSION['_user'];
$file							= (empty($_SESSION['file'])?'':$_SESSION['file']);
$oLP							= unserialize($_SESSION['lpobject']);
$oItem 							= $oLP->items[$oLP->current];
if(!is_object($oItem)){
	error_log('New LP - scorm_api - Could not load oItem item',0);
	exit;
}
$autocomplete_when_80pct = 0;

/*
============================================================================== 
		JavaScript Functions
============================================================================== 
*/ 
?>var scorm_logs='<?php echo ((empty($oLP->scorm_debug) or !api_is_course_admin())?'0':'3');?>'; //debug log level for SCORM. 0 = none, 1=light, 2=a lot, 3=all - displays logs in log frame
var lms_logs=0; //debug log level for LMS actions. 0=none, 1=light, 2=a lot, 3=all
//logit_lms('scormfunctions.php included',0);

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
  this.save_asset = dokeos_save_asset;
  this.void_save_asset = dokeos_void_save_asset;
}

//it is not sure that the scos use the above declarations

API = new APIobject(); //for scorm 1.2
api = API;
//api = new APIobject(); //for scorm 1.2
API_1484_11 = new APIobject();  //for scorm 1.3
api_1484_11 = API_1484_11;
//api_1484_11 = new APIobject();  //for scorm 1.3

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
var G_LastErrorMessage = 'No error';
//this is not necessary and is only provided to make bad Articulate contents shut up (and not trigger useless JS messages)
var G_LastErrorString = 'No error';

var commit = false ;

//Strictly scorm variables
var score=<?php echo $oItem->get_score();?>;
var max='<?php echo $oItem->get_max();?>';
var min='<?php echo $oItem->get_min();?>';
var lesson_status='<?php echo $oItem->get_status();?>';
var session_time='<?php echo $oItem->get_scorm_time('js');?>';
var suspend_data = '<?php echo $oItem->get_suspend_data();?>';
var lesson_location = '<?php echo $oItem->get_lesson_location();?>';
var total_time = '<?php echo $oItem->get_scorm_time('js');?>';
var mastery_score = '<?php echo $oItem->get_mastery_score();?>';
var launch_data = '<?php echo $oItem->get_launch_data();?>';
var max_time_allowed = '<?php echo $oItem->get_max_time_allowed();?>';
var interactions = new Array(<?php echo $oItem->get_interactions_js_array();?>);
item_objectives = new Array();

//Dokeos internal variables
var saved_lesson_status = 'not attempted';
var lms_lp_id = <?php echo $oLP->get_id();?>;
var lms_item_id = <?php echo $oItem->get_id();?>;
//var lms_new_item_id = 0; //temporary value (only there between a load_item() and a LMSInitialize())
var lms_been_synchronized = 0;
var lms_initialized = 0;
var lms_total_lessons = <?php echo $oLP->get_total_items_count(); ?>;
var lms_complete_lessons = <?php echo $oLP->get_complete_items_count();?>;
var lms_progress_bar_mode = '<?php echo $oLP->progress_bar_mode;?>';
if(lms_progress_bar_mode == ''){lms_progress_bar_mode='%';}
var lms_view_id = '<?php echo $oLP->get_view();?>';
if(lms_view_id == ''){ lms_view_id = 1;}
var lms_user_id = '<?php echo $_user['user_id'];?>';
var lms_next_item = '<?php echo $oLP->get_next_item_id();?>';
var lms_previous_item = '<?php echo $oLP->get_previous_item_id();?>';
var lms_lp_type = '<?php echo $oLP->get_type();?>';
var lms_item_type = '<?php echo $oItem->get_type();?>';
var lms_item_credit = '<?php echo $oItem->get_credit();?>';
var lms_item_lesson_mode = '<?php echo $oItem->get_lesson_mode();?>';
var lms_item_launch_data = '<?php echo $oItem->get_launch_data();?>';
var lms_item_core_exit = '<?php echo $oItem->get_core_exit();?>';
var asset_timer = 0;

//Backup for old values
var old_score = 0;
var old_max = 0;
var old_min = 0;
var old_lesson_status = '';
var old_session_time = '';
var old_suspend_data = '';
var lms_old_item_id = 0;

/**
 * Function called mandatorily by the SCORM content to start the SCORM communication
 */
function LMSInitialize() {  //this is the initialize function of all APIobjects
	logit_scorm('LMSInitialise()',0);

	/* load info for this new item by calling the js_api_refresh command in
	 * the message frame. The message frame will update the JS variables by 
	 * itself, in JS, by doing things like top.lesson_status = 'not attempted' 
	 * and that kind of stuff, so when the content loads in the content frame
	 * it will have all the correct variables set 
	 */
	G_LastError = G_NoError ;
	G_LastErrorMessage = 'No error';	
	lms_initialized=1;
	return('true');
}

function Initialize() {  //this is the initialize function of all APIobjects
  return LMSInitialize();
}


function LMSGetValue(param) {
	//logit_scorm("LMSGetValue('"+param+"')",1);
	G_LastError = G_NoError ;
	G_LastErrorMessage = 'No error';	
	var result='';
	// ---- cmi.core._children
	if(param=='cmi.core._children' || param=='cmi.core_children'){
		result='entry, exit, lesson_status, student_id, student_name, lesson_location, total_time, credit, lesson_mode, score, session_time';
	}else if(param == 'cmi.core.entry'){
	// ---- cmi.core.entry
		if(lms_item_core_exit=='none')
		{
			result='ab-initio';
		}
		else if(lms_item_core_exit=='suspend')
		{
			result='resume';
		}
		else
		{
			result='';
		}
	}else if(param == 'cmi.core.exit'){
	// ---- cmi.core.exit
		result='';
		G_LastError = G_ElementIsWriteOnly;
	}else if(param == 'cmi.core.lesson_status'){
	// ---- cmi.core.lesson_status
	    if(lesson_status != '') {
	    	result=lesson_status;
	    }
	    else{
	    	result='not attempted';
	    }
	}else if(param == 'cmi.core.student_id'){
	// ---- cmi.core.student_id
		result='<?php echo $_user['user_id']; ?>';
	}else if(param == 'cmi.core.student_name'){
	// ---- cmi.core.student_name
		  <?php
			$who=addslashes($_user['lastName'].", ".$_user['firstName']);
		    echo "result='$who';"; 
		  ?>
	}else if(param == 'cmi.core.lesson_location'){
	// ---- cmi.core.lesson_location
		result=lesson_location;
	}else if(param == 'cmi.core.total_time'){
	// ---- cmi.core.total_time
		result=total_time;
	}else if(param == 'cmi.core.score._children'){
	// ---- cmi.core.score._children
		result='raw,min,max';
	}else if(param == 'cmi.core.score.raw'){
	// ---- cmi.core.score.raw
		result=score;
	}else if(param == 'cmi.core.score.max'){
	// ---- cmi.core.score.max
		result=max;
	}else if(param == 'cmi.core.score.min'){
	// ---- cmi.core.score.min
		result=min;
	}else if(param == 'cmi.core.score'){
	// ---- cmi.core.score -- non-standard item, provided as cmi.core.score.raw just in case
		result=score;
	}else if(param == 'cmi.core.credit'){
	// ---- cmi.core.credit
		result = lms_item_credit;
	}else if(param == 'cmi.core.lesson_mode'){
	// ---- cmi.core.lesson_mode
		result = lms_item_lesson_mode;
	}else if(param == 'cmi.suspend_data'){
	// ---- cmi.suspend_data
		result = suspend_data;
	}else if(param == 'cmi.launch_data'){
	// ---- cmi.launch_data
		result = lms_item_launch_data;
	}else if(param == 'cmi.objectives._children'){
	// ---- cmi.objectives._children
		result = 'id,score,status';
	}else if(param == 'cmi.objectives._count'){
	// ---- cmi.objectives._count
		//result='<?php echo $oItem->get_view_count();?>';
		result = item_objectives.length;
	}else if(param.substring(0,15)== 'cmi.objectives.'){
		var myres = '';
		if(myres = param.match(/cmi.objectives.(\d+).(id|score|status|_children)(.*)/))
		{
			var obj_id = myres[1];
			var req_type = myres[2];
			if(item_objectives[obj_id]==null)
			{
				if(req_type == 'id')
				{
					result = '';
				}else if(req_type == '_children'){
					result = 'id,score,status';
				}else if(req_type == 'score'){
					if(myres[3]==null)
					{
						result = '';
						G_lastError = G_NotImplementedError;
						G_lastErrorString = 'Not implemented yet';
					}else if (myres[3] == '._children'){
						result = 'raw,min,max'; //non-standard, added for NetG
					}else if (myres[3] == '.raw'){
						result = '';
					}else if (myres[3] == '.max'){
						result = '';
					}else if (myres[3] == '.min'){
						result = '';
					}else{
						result = '';
						G_lastError = G_NotImplementedError;
						G_lastErrorString = 'Not implemented yet';
					}
				}else if(req_type == 'status'){
					result = 'not attempted';
				}
			}
			else
			{
				//the object is not null
				if(req_type == 'id')
				{
					result = item_objectives[obj_id][0];
				}else if(req_type == '_children'){
					result = 'id,score,status';
				}else if(req_type == 'score'){
					if(myres[3]==null)
					{
						result = '';
						G_lastError = G_NotImplementedError;
						G_lastErrorString = 'Not implemented yet';
					}else if (myres[3] == '._children'){
						result = 'raw,min,max'; //non-standard, added for NetG
					}else if (myres[3] == '.raw'){
						if(item_objectives[obj_id][2] != null)
						{
							result = item_objectives[obj_id][2];
						}else{
							result = '';
						}
					}else if (myres[3] == '.max'){
						if(item_objectives[obj_id][3] != null)
						{
							result = item_objectives[obj_id][3];
						}else{
							result = '';
						}
					}else if (myres[3] == '.min'){
						if(item_objectives[obj_id][4] != null)
						{
							result = item_objectives[obj_id][4];
						}else{
							result = '';
						}
					}else{
						result = '';
						G_lastError = G_NotImplementedError;
						G_lastErrorString = 'Not implemented yet';
					}
				}else if(req_type == 'status'){
					if(item_objectives[obj_id][1] != null)
					{
						result = item_objectives[obj_id][1];
					}else{
						result = 'not attempted';
					}
				}
			}
		}
	}else if(param == 'cmi.student_data._children'){
	// ---- cmi.student_data._children
		result = 'mastery_score,max_time_allowed';
	}else if(param == 'cmi.student_data.mastery_score'){
	// ---- cmi.student_data.mastery_score
		result = mastery_score;
	}else if(param == 'cmi.student_data.max_time_allowed'){
	// ---- cmi.student_data.max_time_allowed
		result = max_time_allowed;
	}else if(param == 'cmi.interactions._count'){
	// ---- cmi.interactions._count
		result = interactions.length;
	}else if(param == 'cmi.interactions._children'){
	// ---- cmi.interactions._children
		result = 'id,time,type,correct_responses,weighting,student_response,result,latency';
	}else{
	// ---- anything else
		result = '';
		G_lastError = G_NotImplementedError;
		G_lastErrorString = 'Not implemented yet';
	}
	logit_scorm("LMSGetValue\n\t('"+param+"') returned '"+result+"'",1);
	return result;
}

function GetValue(param) {
	return LMSGetValue(param);
}

function LMSSetValue(param, val) {
	logit_scorm("LMSSetValue\n\t('"+param+"','"+val+"')",0);
	commit = true; //value has changed, need to re-commit
	G_LastError = G_NoError ;
	G_LastErrorMessage = 'No error';	
	return_value = 'false';
	if( param == "cmi.core.score.raw" )
	{
		score= val; return_value='true';
	} else if ( param == "cmi.core.score.max" ) {
		max = val;return_value='true';
	} else if ( param == "cmi.core.score.min" ) {
		min = val;return_value='true';
	} else if ( param == "cmi.core.lesson_location" ) {
		lesson_location = val;return_value='true';
	} else if ( param == "cmi.core.lesson_status" ) {
		saved_lesson_status = lesson_status;
		lesson_status = val;
	    return_value='true';
	} else if ( param == "cmi.completion_status" ) {
		lesson_status = val;return_value='true'; //1.3
	} else if ( param == "cmi.core.session_time" ) {
		session_time = val;return_value='true';
	} else if ( param == "cmi.score.scaled") { //1.3
		if(val<=1 && val>=-1)
		{ 
			score = val ;
			return_value='true';
		}
		else
		{
			return_value='false';
		}
	} else if ( param == "cmi.success_status" ) {
		success_status = val;return_value='true'; //1.3
	} else if ( param == "cmi.suspend_data" ) {
		suspend_data = val;return_value='true';
	} else if ( param == "cmi.core.exit" ) {
		lms_item_core_exit = val;return_value='true';
	} else if ( param == "cmi.core.entry" ) {
		G_LastError = G_ElementIsReadOnly
	} else if ( param == "cmi.student_data.mastery_score" ) {
		G_LastError = G_ElementIsReadOnly;
	} else if ( param == "cmi.student_data.max_time_allowed" ) {
		G_LastError = G_ElementIsReadOnly;
	} else if ( param == "cmi.launch_data" ) {
		G_LastError = G_ElementIsReadOnly;
	} else {
		var myres = new Array();
		if(myres = param.match(/cmi.interactions.(\d+).(id|time|type|correct_responses|weighting|student_response|result|latency)(.*)/))
		{
			elem_id = myres[1];
			if(elem_id > interactions.length) //interactions setting should start at 0
			{
				/*
                G_LastError = G_InvalidArgumentError;
                G_LastErrorString = 'Invalid argument (interactions)';
				return_value = false;
                */
                interactions[0] = ['0','','','','','','',''];
			}
			
			if(interactions[elem_id] == null){
					interactions[elem_id] = ['','','','','','','',''];
					//id(0), type(1), time(2), weighting(3),correct_responses(4),student_response(5),result(6),latency(7)
					interactions[elem_id][4] = new Array();
			}
			elem_attrib = myres[2];
			switch(elem_attrib) {
					case "id":
						interactions[elem_id][0] = val;
						logit_scorm("Interaction "+elem_id+"'s id updated",2);
						return_value='true';
						break;
					case "time":
						interactions[elem_id][2] = val;
						logit_scorm("Interaction "+elem_id+"'s time updated",2);
						return_value='true';
						break;
					case "type":
						interactions[elem_id][1] = val;
						logit_scorm("Interaction "+elem_id+"'s type updated",2);
						return_value='true';
						break;
					case "correct_responses":
						//do nothing yet
						interactions[elem_id][4].push(val);
						logit_scorm("Interaction "+elem_id+"'s correct_responses not updated",2);
						return_value='true';
						break;
					case "weighting":
						interactions[elem_id][3] = val;
						logit_scorm("Interaction "+elem_id+"'s weighting updated",2);
						return_value='true';
						break;
					case "student_response":
						interactions[elem_id][5] = val;
						logit_scorm("Interaction "+elem_id+"'s student_response updated",2);
						return_value='true';
						break;
					case "result":
						interactions[elem_id][6] = val;
						logit_scorm("Interaction "+elem_id+"'s result updated",2);
						return_value='true';
						break;
					case "latency":
						interactions[elem_id][7] = val;
						logit_scorm("Interaction "+elem_id+"'s latency updated",2);
						return_value='true';
						break;
					default:
							G_lastError = G_NotImplementedError;
							G_lastErrorString = 'Not implemented yet';
			}
		}else if(param.substring(0,15)== 'cmi.objectives.'){
			var myres = '';
			if(myres = param.match(/cmi.objectives.(\d+).(id|score|status)(.*)/))
			{
				obj_id = myres[1];
				if(obj_id > item_objectives.length) //objectives setting should start at 0
				{
					G_LastError = G_InvalidArgumentError;
                    G_LastErrorString = 'Invalid argument (objectives)';
					return_value = false;
				}
				else
				{
					req_type = myres[2];
					if(obj_id == null || obj_id == '')
					{
						;//do nothing
					}
					else
					{
						if(item_objectives[obj_id]==null)
						{
							item_objectives[obj_id] = ['','','','',''];
						}
						if( req_type == "id" ) {
								//item_objectives[obj_id][0] = val.substring(51,57);
								item_objectives[obj_id][0] = val;
								logit_scorm("Objective "+obj_id+"'s id updated",2);
								return_value = 'true';
						} else if ( req_type == "score" ) {
								if (myres[3] == '._children'){
									return_value = '';
									G_lastError = G_InvalidSetValue;
									G_lastErrorString = 'Invalid set value, element is a keyword';
								}else if (myres[3] == '.raw'){
									item_objectives[obj_id][2] = val;
									logit_scorm("Objective "+obj_id+"'s score raw updated",2);
									return_value = 'true';
								}else if (myres[3] == '.max'){
									item_objectives[obj_id][3] = val;
									logit_scorm("Objective "+obj_id+"'s score max updated",2);
									return_value = 'true';
								}else if (myres[3] == '.min'){
									item_objectives[obj_id][4] = val;
									logit_scorm("Objective "+obj_id+"'s score min updated",2);
									return_value = 'true';
								}else{
									return_value = '';
									G_lastError = G_NotImplementedError;
									G_lastErrorString = 'Not implemented yet';
								}
						} else if ( req_type == "status" ) {
								item_objectives[obj_id][1] = val;
								logit_scorm("Objective "+obj_id+"'s status updated",2);
								return_value = 'true';
						} else {
								G_lastError = G_NotImplementedError;
								G_lastErrorString = 'Not implemented yet';
						}
					}
				}
			}
		}else{
			G_lastError = G_NotImplementedError;
			G_lastErrorString = 'Not implemented yet';
		}
	}
	<?php 
	if ($oLP->force_commit == 1){
		echo "    var mycommit = LMSCommit('force');";
	}
	?>
	return(return_value);
}

function SetValue(param, val) {
	return LMSSetValue(param, val);
}

function savedata(origin) { //origin can be 'commit', 'finish' or 'terminate'
    if ((lesson_status != 'completed') && (lesson_status != 'passed') 
    	&& (mastery_score >=0) && (score >= mastery_score))
    {
      lesson_status = 'passed';
    }
    else if( (mastery_score < 0) && (lms_lp_type != '2') && ( lesson_status == 'incomplete') && (score >= (0.8*max) ) )
    { //the status cannot be modified automatically by the LMS under SCORM 1.2's rules
    <?php if ($autocomplete_when_80pct){ ?>
    	      lesson_status = 'completed';
    <?php }?>
    ;
    }
	logit_lms('saving data (status='+lesson_status+' - interactions: '+ interactions.length +')',1);
	xajax_save_item(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, score, max, min, lesson_status, session_time, suspend_data, lesson_location, interactions, lms_item_core_exit);
	if(item_objectives.length>0)
	{
		xajax_save_objectives(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,item_objectives);
	}
}

function LMSCommit(val) {
	logit_scorm('LMSCommit()',0);
	G_LastError = G_NoError ;
	G_LastErrorMessage = 'No error';	
	savedata('commit');
    commit = false ; //now changes have been commited, no need to update until next SetValue()
	return('true');
}

function Commit(val) {
	return LMSCommit(val);
}

function LMSFinish(val) {
	G_LastError = G_NoError ;
	G_LastErrorMessage = 'No error';	
	if (( commit == false )) { 	
		logit_scorm('LMSFinish() (no LMSCommit())',1); 
	}
	if ( commit == true ) {
		logit_scorm('LMSFinish() called',1);		
		savedata('finish');
	    commit = false ;
	}
	return('true');
}

function Finish(val) {
	return LMSFinish(val);
}

function LMSGetLastError() {
	logit_scorm('LMSGetLastError()',1);
	return(G_LastError.toString());
}

function GetLastError() {
	return LMSGetLastError();
}

function LMSGetErrorString(errCode){
	logit_scorm('LMSGetErrorString()',1);
	return(G_LastErrorString);
}

function GetErrorString(errCode){
	return LMSGetErrorString(errCode);
}

function LMSGetDiagnostic(errCode){
	logit_scorm('LMSGetDiagnostic()',1);
	return(API.LMSGetLastError());
}

function GetDiagnostic(errCode){
	return LMSGetDiagnostic(errCode);
}

function Terminate(){
	logit_scorm('Terminate()',0);
	G_LastError = G_NoError ;
	G_LastErrorMessage = 'No error';	
	commit = true;
	savedata('terminate');
	return (true);
}
<?php
//--------------------------------------------------------------------//
/**
 * Dokeos-specific code that deals with event handling and inter-frames
 * messaging/refreshing.
 * Note that from now on, the Dokeos JS code in this library will act as
 * a controller, of the MVC pattern, and receive all requests for frame
 * updates, then redispatch to any frame concerned.
 */
?>
/**
 * Defining the AJAX-object class to be made available from other frames
 */
function XAJAXobject() {
  this.xajax_switch_item_details=xajax_switch_item_details;
  this.switch_item=switch_item;
  this.xajax_save_objectives=xajax_save_objectives;
  this.xajax_save_item = xajax_save_item;
}

//it is not sure that the scos use the above declarations

oXAJAX = new XAJAXobject();
oxajax = new XAJAXobject();

/**
 * Cross-browser event handling by Scott Andrew
 * @param	element	Element that needs an event attached
 * @param   string	Event type (load, unload, click, keyDown, ...)
 * @param   string	Function name (the event handler)
 * @param   string	used in addEventListener
 */
function addEvent(elm, evType, fn, useCapture){
	if(elm.addEventListener){
		elm.addEventListener(evType, fn, useCapture);
		return true;
	}else if (elm.attachEvent){
		var r = elm.attachEvent('on' + evType, fn);
	}else{
		elm['on'+evType] = fn;
	}
}
/**
 * Add listeners to the page objects. This has to be defined for
 * the current context as it acts on objects that should exist
 * on the page 
 * possibly deprecated
 */
function addListeners(){
	//exit if the browser doesn't support ID or tag retrieval
	logit_lms('Entering addListeners()',2);
	if(!document.getElementsByTagName){
		logit_lms("getElementsByTagName not available",2);
		return;
	}
	if(!document.getElementById){
		logit_lms("getElementById not available",2);
		return;
	}
	//assign event handlers to objects
	if(lms_lp_type==1 || lms_item_type=='asset'){
		logit_lms('Dokeos LP or asset',2);
		//if this path is a Dokeos learnpath, then start manual save
		//when something is loaded in there
		addEvent(window,'unload',dokeos_save_asset,false);
		logit_lms('Added event listener on content_id for unload',2);
	}
	logit_lms('Quitting addListeners()',2);
}

/**
 * Load an item into the content frame:
 * - making sure the previous item status have been saved
 * - first updating the current item ID (to save the right item) 
 * - updating the frame src
 * possibly deprecated
 */
function load_item(item_id,url){
	if(document.getElementById('content_id'))
	{
		logit_lms('Loading item '+item_id,2);
		var cont_f = document.getElementById('content_id');
		if(cont_f.src){
			lms_old_item_id = lms_item_id;
			var lms_new_item_id = item_id;
			//load new content page into content frame
			if(lms_lp_type==1 || lms_item_type=='asset'){
				dokeos_save_asset();
			}
			cont_f.src = url;
			update_toc('unhighlight',lms_old_item_id);
			update_toc('highlight',item_id);
			return true;
		}
		logit_lms('cont_f.src has no properties',0);
	}
	logit_lms('content_id has no properties',0);
	return false;
}
/**
 * Save a Dokeos learnpath item's time and mark as completed upon
 * leaving it
 */
function dokeos_save_asset(){
	logit_lms('dokeos_save_asset',2);
    xajax_save_item(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, score, max, min, lesson_status, session_time, suspend_data, lesson_location,interactions, lms_item_core_exit);
    if(item_objectives.length>0)
	{
		xajax_save_objectives(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,item_objectives);
	}
}
/**
 * Save a Dokeos learnpath item's time and mark as completed upon leaving it.
 * Same function as dokeos_save_asset() but saves it with empty params
 * to use values set from another side in the database. Only used by Dokeos quizzes.
 * Also save the score locally because it hasn't been done through SetValue().
 * Saving the status will be dealt with by the XAJAX function.
 */
function dokeos_void_save_asset(myscore,mymax)
{
	logit_lms('dokeos_save_asset',2);
	score = myscore;
	if((mymax == null) || (mymax == '')){mymax = 100;} //assume a default of 100, otherwise the score will not get saved (see lpi->set_score())
    xajax_save_item(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, myscore, mymax);
}
/**
 * Logs information about SCORM messages into the log frame
 * @param	string	Message to log
 * @param	integer Priority (0 for top priority, 3 for lowest)
 */
function logit_scorm(message,priority){
	if(scorm_logs>priority){
		if(frames["lp_log_name"] && frames["lp_log_name"].document && frames["lp_log_name"].document.getElementById && frames["lp_log_name"].document.getElementById('log_content')){
			frames["lp_log_name"].document.getElementById("log_content").innerHTML += "SCORM: " + message + "<br/>";
		}
	}
}
/**
 * Logs information about LMS activity into the log frame
 * @param	string	Message to log
 * @param	integer Priority (0 for top priority, 3 for lowest)
 */
function logit_lms(message,priority){
	if(lms_logs>priority){
		if(frames["lp_log_name"] && frames["lp_log_name"].document && frames["lp_log_name"].document.getElementById && frames["lp_log_name"].document.getElementById('log_content')){
			frames["lp_log_name"].document.getElementById("log_content").innerHTML += "LMS: " + message + "<br/>";
		}
	}
}

/**
 * update the Table Of Contents frame, by changing CSS styles, mostly
 * @param	string	Action to be taken
 * @param	integer	Item id to update
 */
function update_toc(update_action,update_id)
{
	<?php //if($oLP->mode != 'fullscreen'){ ?>
		var myframe = frames["toc_name"];
		var myelem = myframe.document.getElementById("toc_"+update_id);
		var myelemimg = myframe.document.getElementById("toc_img_"+update_id);
		logit_lms('update_toc("'+update_action+'",'+update_id+')',2);
		
		if(update_id != 0)
		{
			switch(update_action)
			{
				case 'unhighlight':
					if (update_id%2==0)
					{
						myelem.className = "scorm_item_2";
					}
					else
					{
						myelem.className = "scorm_item_1";
					}
					break;
				case 'highlight':
					myelem.className = "scorm_item_highlight";
					break;
				case 'not attempted':
					if(myelemimg.src != '../img/notattempted.gif'){
						myelemimg.src = "../img/notattempted.gif";
						myelemimg.alt = "n";
					}
					break;
				case 'incomplete':
					if(myelemimg.src != '../img/incomplete.gif'){
						myelemimg.src = "../img/incomplete.gif";
						myelemimg.alt = "i";
					}
					break;
				case 'completed':
					if(myelemimg.src != '../img/completed.gif'){
						myelemimg.src = "../img/completed.gif";
						myelemimg.alt = "c";
					}
					break;
				case 'failed':
					if(myelemimg.src != '../img/failed.gif'){
						myelemimg.src = "../img/failed.gif";
						myelemimg.alt = "f";
					}
					break;
				case 'passed':
					if(myelemimg.src != '../img/completed.gif' && myelemimg.alt != 'passed'){
						myelemimg.src = "../img/completed.gif";
						myelemimg.alt = "p";
					}
					break;
				case 'browsed':
					if(myelemimg.src != '../img/completed.gif' && myelemimg.alt != 'browsed'){
						myelemimg.src = "../img/completed.gif";
						myelemimg.alt = "b";
					}
					break;
				default:
					logit_lms('Update action unknown',2);
					break;
			}
		}
		return true;
    <?php //} ?>
	return true;
}
/**
 * Updates the progress bar with the new status. Prevents the need of a page refresh and flickering
 * @param	integer	Number of completed items
 * @param	integer	Number of items in total
 * @param	string  Display mode (absolute 'abs' or percentage '%').Defaults to %
 */
function update_progress_bar(nbr_complete, nbr_total, mode)
{
	logit_lms('update_progress_bar('+nbr_complete+','+nbr_total+','+mode+')',2);
	logit_lms('could update with data: '+lms_lp_id+','+lms_view_id+','+lms_user_id,2);
	var myframe = frames["nav_name"];
	if(myframe){
		if(mode == ''){mode='%';}
		if(nbr_total == 0){nbr_total=1;}
		var percentage = (nbr_complete/nbr_total)*100;
		percentage = Math.round(percentage);
		var pr_text  = myframe.document.getElementById('progress_text');
		var pr_full  = myframe.document.getElementById('progress_img_full');
		
		var pr_empty = myframe.document.getElementById('progress_img_empty');
		pr_full.width = percentage*1.2;
		pr_empty.width = (100-percentage)*1.2;
		var mytext = '';
		switch(mode){
			case 'abs':
				mytext = nbr_complete + '/' + nbr_total;
				break;
			case '%':
			default:
				mytext = percentage + '%'; 
				break;
		}
		pr_text.innerHTML = mytext;
	}
	return true;
}
function update_stats_page()
{
	var myframe = document.getElementById('content_id');
	var mysrc = myframe.location.href;
	if(mysrc == 'lp_controller.php?action=stats'){
		if(myframe && myframe.src){
			var mysrc = myframe.src;
			myframe.src = mysrc;
		}
		// = mysrc; //refresh page
	}
	return true;
}
/**
 * Updates the message frame with the given string
 */
function update_message_frame(msg_msg)
{
	if(msg_msg==null){msg_msg='';}
	var msg_f = frames["message_name"];
	if(!msg_f.document || !msg_f.document.getElementById('msg_div_id')){
		logit_lms('In update_message_frame() - message frame has no document property',0);
	}else{
		logit_lms('In update_message_frame() - updating frame',0);
		msg_f.document.getElementById('msg_div_id').innerHTML= msg_msg;
	}
}
/**
 * Function that handles the saving of an item and switching from an item to another.
 * Once called, this function should be able to do the whole process of (1) saving the
 * current item, (2) refresh all the values inside the SCORM API object, (3) open the 
 * new item into the content_id frame, (4) refresh the table of contents, (5) refresh 
 * the progress bar (completion), (6) refresh the message frame
 * @param	integer		Dokeos ID for the current item
 * @param	string		This parameter can be a string specifying the next 
 *						item (like 'next', 'previous', 'first' or 'last') or the id to the next item  
 */
function switch_item(current_item, next_item){
	//(1) save the current item
	logit_lms('Called switch_item with params '+lms_item_id+' and '+next_item+'',0);
	if(lms_lp_type==1 || lms_item_type=='asset' || session_time == '0' || session_time == '0:00:00'){
        xajax_save_item(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, score, max, min, lesson_status, asset_timer, suspend_data, lesson_location,interactions, lms_item_core_exit);
	}else{
        xajax_save_item(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, score, max, min, lesson_status, session_time, suspend_data, lesson_location,interactions, lms_item_core_exit);
	}
	if(item_objectives.length>0)
	{
		xajax_save_objectives(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,item_objectives);
	}
	//(2) Refresh all the values inside this SCORM API object - use AJAX
	xajax_switch_item_details(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,next_item);
	
	//(3) open the new item in the content_id frame
	switch(next_item){
		case 'next':
			next_item = lms_next_item;
			break;
		case 'previous':
			next_item = lms_previous_item;
			break;
		default:
			break;
	}
	var mysrc = 'lp_controller.php?action=content&lp_id='+lms_lp_id+'&item_id='+next_item;
	var cont_f = document.getElementById('content_id');
	if(!cont_f){
		logit_lms('In switch - content frame not found',0);
		<?php if($oLP->mode == 'fullscreen'){ ?>
		cont_f = window.open(''+mysrc,'content_name','toolbar=0,location=0,status=0,scrollbars=1,resizable=1');
		<?php } else { ?>
			return false;
		<?php } ?>
	}
	else
	{
		cont_f.src = mysrc;
	}
	if(lms_lp_type==1 || lms_item_type=='asset'){
		xajax_start_timer();
	}	
		
	//(4) refresh the audio player if needed	
	if(frames['nav_name']){
		frames['nav_name'].location.reload();
	}

	return true;
}
/**
 * Save a specific item (with its interactions, if any) into the LMS through
 * an AJAX call. Originally, we used the xajax library. Now we use jQuery.
 * Because of the need to pass an array, we have to build the parameters
 * manually into GET[]
 */
function xajax_save_item(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, score, max, min, lesson_status, session_time, suspend_data, lesson_location, interactions, lms_item_core_exit) {
        params='';
        params += 'lid='+lms_lp_id+'&uid='+lms_user_id+'&vid='+lms_view_id;
        params += '&iid='+lms_item_id+'&s='+score+'&max='+max+'&min='+min;
        params += '&status='+lesson_status+'&t='+session_time;
        params += '&suspend='+suspend_data+'&loc='+lesson_location;
        params += '&core_exit='+lms_item_core_exit;
        interact_string = '';
        for (i in interactions){
        	interact_string += '&interact['+i+']=';
            interact_temp = '[';
            for (j in interactions[i]) {
            	interact_temp += interactions[i][j]+',';
            }
            interact_temp = interact_temp.substr(0,(interact_temp.length-2)) + ']';
            interact_string += encodeURIComponent(interact_temp);
        }
        //interact_string = encodeURIComponent(interact_string.substr(0,(interact_string.length-1)));
        params += interact_string;
        /*params = {
            'lid': lms_lp_id,
            'uid': lms_user_id,
            'vid': lms_view_id,
            'iid': lms_item_id,
            's': score,
            'max': max,
            'min': min,
            'status': lesson_status, 
            't': session_time, 
            'suspend': suspend_data,
            'loc': lesson_location,
            'interact': interac_string, 
            'core_exit': lms_item_core_exit
        }
        */
        $.ajax({
            type:"GET",
            data: params,
            url: "lp_ajax_save_item.php", 
            dataType: "script",
            async: false
            }
        );
}
/**
 * Starts the timer with the server clock time.
 * Originally, we used the xajax library. Now we use jQuery
 */
function xajax_start_timer() {
    $.ajax({
        type: "GET",
        url: "lp_ajax_start_timer.php",
        dataType: "script",
        async: false
    });	
}
/**
 * Save a specific item's objectives into the LMS through
 * an AJAX call. Originally, we used the xajax library. Now we use jQuery
 */
function xajax_save_objectives(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,item_objectives) {
        params='';
        params += 'lid='+lms_lp_id+'&uid='+lms_user_id+'&vid='+lms_view_id;
        params += '&iid='+lms_item_id;
        obj_string = '';
        for (i in item_objectives){
            obj_string += '&objectives['+i+']=';
            obj_temp = '[';
            for (j in item_objectives[i]) {
                obj_temp += item_objectives[i][j]+',';
            }
            obj_temp = obj_temp.substr(0,(obj_temp.length-2)) + ']';
            obj_string += encodeURIComponent(obj_temp);
        }
        params += obj_string;
        $.ajax({
            type: "GET",
            data: params,
            url: "lp_ajax_save_objectives.php",
            dataType: "script",
            async: false
        });
}
/**
 * Switch between two items through
 * an AJAX call. Originally, we used the xajax library. Now we use jQuery
 */
function xajax_switch_item_details(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,next_item) {
    params = {
        'lid': lms_lp_id,
        'uid': lms_user_id,
        'vid': lms_view_id,
        'iid': lms_item_id,
        'next': next_item
    }
    $.ajax({
        type: "GET",
        data: params,
        url: "lp_ajax_switch_item.php",
        dataType: "script",
        async: false
    });
}
addEvent(window,'load',addListeners,false);
if(lms_lp_type==1 || lms_item_type=='asset'){
	xajax_start_timer();
}