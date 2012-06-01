<?php
	error_reporting(E_ERROR);
	
	ob_start();
	
	require_once '../global.inc.php';
	require_once '../lib/usermanager.lib.php';
	
	$id = isset($_REQUEST['id'])?$_REQUEST['id']:null;
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$actionEventName = isset($_REQUEST['eventName'])?$_REQUEST['eventName']:null;
	
	api_protect_admin_script();
	
	ob_end_clean();
	
	header('content-type: text/json');
	
	if($action == 'getEventTypes') {
		$events = get_all_event_types();
		
		print json_encode($events);
	}
	elseif($action == 'getUsers') {
		$users = UserManager::get_user_list();
		
		print json_encode($users);
	}
	elseif($action == 'get_event_users') {
		$users = get_event_users($actionEventName);
		
		print json_encode($users);
	}
	
	
