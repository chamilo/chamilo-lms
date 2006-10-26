<?php // $Id: $ 
/**
============================================================================== 
*	API event handler functions for AICC / CMIv4 in HACP communication mode
*
*	@author   Denes Nagy <darkden@freemail.hu>
*   @author   Yannick Warnier <ywarnier@beeznest.org>
*	@version  v 1.0
*	@access   public
*	@package  dokeos.learnpath
* 	@license	GNU/GPL - See Dokeos license directory for details
============================================================================== 
*/
/**
 * This script is divided into three sections. 
 * The first section (below) is the initialisation part.
 * The second section is the AICC object part
 * The third section defines the event handlers for Dokeos' internal messaging
 * and frames refresh
 * 
 * This script implements the HACP messaging for AICC. The API messaging is
 * made by another set of scripts.
 */
/*
============================================================================== 
	   INIT SECTION
============================================================================== 
*/ 
$debug = 0;
//Use session ID as provided by the request
if(!empty($_REQUEST['aicc_sid']))
{
	session_id($_REQUEST['aicc_sid']);
	if($debug>1){error_log('New LP - '.__FILE__.','.__LINE__.' - reusing session ID '.$_REQUEST['aicc_sid'],0);}
}
//Load common libraries using a compatibility script to bridge between 1.6 and 1.8
require_once('back_compat.inc.php');  
if($debug>2){error_log('New LP - '.__FILE__.','.__LINE__.' - Current session ID: '.session_id(),0);}
//Load learning path libraries so we can use the objects to define the initial values
//of the API
require_once('learnpath.class.php');
require_once('learnpathItem.class.php');
require_once('aicc.class.php');

$_uid							= $_SESSION['_uid'];
$_user							= $_SESSION['_user'];
$file							= $_SESSION['file'];
$oLP							= unserialize($_SESSION['lpobject']);
$oItem 							= $oLP->items[$oLP->current];
if(!is_object($oItem)){
	error_log('New LP - scorm_api - Could not load oItem item',0);
	exit;
}
$autocomplete_when_80pct = 0;

$result = array(
	'core'=>array(),
	'core_lesson'=>array(),
	'core_vendor'=>array(),
	'evaluation'=>array(),
	'student_data'=>array(),
);
$error_code = 0;
$error_text = '';
$aicc_data = '';
//GET REQUEST
if(!empty($_REQUEST['command']))
{
	switch(strtolower($_REQUEST['command']))
	{
		case 'getparam':
			foreach($_REQUEST as $name => $value){
				switch(strtolower($name)){
					case 'student_id':
						break;
					case 'student_name':
						break;
					case 'lesson_location':
						break;
					case 'credit':
						break;
					case 'lesson_status':
						break;
					case 'entry':
						break;
					case 'score':
						break;
					case 'time': //total time
						break;
					case 'lesson_mode':
						break;
					case 'core_lesson':
						break;
					case 'core_vendor':
						break;
				}
			}
			break;
		case 'putparam':
			foreach($_REQUEST as $name => $value)
			{
				switch(strtolower($name))
				{
					case 'lesson_location':
						break;
					case 'lesson_status':
						break;
					case 'exit':
						break;
					case 'score':
						break;
					case 'time': //session time
						break;
					case 'core_lesson':
						break;
				}

			}
			break;
		case 'putcomments':
			break;
		case 'putobjectives':
			break;
		case 'putpath':
			break;
		case 'putinteractions':
			break;
		case 'putperformance':
			break;
		default:
			$error_code = 1;
	}
}
echo $result;
?>