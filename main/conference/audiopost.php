<?php
/* See license terms in /license.txt */

//file_put_contents("result.txt", print_r($_POST, true));
//file_put_contents("result3.txt", print_r($_FILES, true));
//file_put_contents("result2.txt", print_r($_GET, true));
require_once '../inc/global.inc.php';

// check the request comes from our red5 server
$ips = gethostbynamel(api_get_setting('service_visio','visio_host'));
$is_our_server = false;

// ignoring null file
if ($_FILES["file"]["size"] == 0)
	exit(0);

if(is_array($ips))
{
	foreach($ips as $ip)
	{
		//get 255 range for known server address
		$split = split('.',$ip);
		$ip_range_server = $split[0].'.'.$split[1].'.'.$split[2];
		//get 255 range for request source address
		$split = split('.',$_SERVER['REMOTE_ADDR']);
		$ip_range_request = $split[0].'.'.$split[1].'.'.$split[2];
		if($ip_range_server == $ip_range_request){$is_our_server = true;}
	}
}
if($is_our_server)
{
	if(api_get_setting('service_visio','active')=='true')
	{
		//check encryption key
		$string1 = $_GET['course_code'].$_GET['user_id'].gmdate('Ymd').$_configuration['security_key'];
		$string2 = $_GET['course_code'].$_GET['user_id'].(gmdate('Ymd')-1).$_configuration['security_key'];
		if(md5($string1) == $_GET['checker'] or md5($string2) == $_GET['checker'])
		{
			$course_info = api_get_course_info($_GET['course_code']);
			$target = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document/audio/';
			$basename = basename( $_FILES['file']['name']);
			$target = $target . $basename ;
			if(!move_uploaded_file($_FILES['file']['tmp_name'], $target))
			{
				error_log(__FILE__.':'.__LINE__.': File upload to '.$target.' failed',0);
			}
			else
			{
				require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
				$id = add_document($course_info,'/audio/'.$basename,'file',filesize($target),$basename);
				if($id !== false)
				{
					$res = api_item_property_update($course_info,TOOL_DOCUMENT,$id,'DocumentAdded',$_GET['user_id']);
					if($res === false)
					{
						error_log(__FILE__.':'.__LINE__.': Something went wrong with item properties update of '.$target,0);
					}
					else
					{//make sound invisible?
						//$res = api_item_property_update($course_info,TOOL_DOCUMENT,$id,'invisible',$_GET['user_id']);
					}
				}
				else
				{
					error_log(__FILE__.':'.__LINE__.': Could not create document record for document '.$target,0);
				}
			}
		}
		else
		{
			error_log(__FILE__.':'.__LINE__.': Attempting to save file but hash check did not suceed (hacking attempt?)',0);
		}
	}
	else
	{
		error_log(__FILE__.':'.__LINE__.': Attempting to save file but videoconf is not enabled',0);
	}
}
else
{
	error_log(__FILE__.':'.__LINE__.': Attempting to save file but coming from unknown source',0);
}
?>
