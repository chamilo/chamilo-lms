<?php //$id: $
// This file is part of the Dokeos software - See license and credits in the documentation/ directory
/**
 * Created on 27.09.2006
 * Include the lazlo file necessary to use the audiorecorder  
 * @author Sebastian Wagner <seba.wagner@gmail.com>
 * @author Eric Marguin <e.marguin@elixir-interactive.com>
 * @author Arnaud Ligot <arnaud@cblue.be>
 */
global $_configuration;
$audio_recorder_studentview = false;
$web_path = api_get_path(WEB_CODE_PATH);
if($audio_recorder_studentview)
{
	$width = 295;
	$height = 24;
	$player = 'player2.swf';
	$cp = api_get_course_path();
	$docs = Database::get_main_table(TABLE_MAIN_DOCUMENT);
	$select = "SELECT * FROM $docs " .
			" WHERE path like BINARY '/audio/".Database::escape_string($mylpid).'-'.Database::escape_string($mylpitemid)."%' AND filetype='file' " .
			" ORDER BY path DESC";
	$res = api_sql_query($select);
	if(Database::num_rows($res)>0)
	{  
		$row = Database::fetch_array($res);
		$filepath = api_get_path(WEB_COURSE_PATH).$cp.'/document'.$row['path'];
		$path_to_lzx = $web_path.'conference/'.$player.'?uri='.$filepath.'&autostart=true';
	}	
}
else
{
	$width = 295;
	$height= 90;
	$player = 'recorder2.swf';
	$server = (api_get_setting('service_visio','visio_use_rtmpt')=='true'?'rtmpt://':'rtmp://').api_get_setting('service_visio','visio_host').':'.(api_get_setting('service_visio','visio_use_rtmpt')=='true'?'80':api_get_setting('service_visio','visio_port')).'/recorder';
	$post_uri = urlencode($web_path.'conference/audiopost.php?course_code='.api_get_course_id().'&user_id='.api_get_user_id().'&checker='.md5(api_get_course_id().api_get_user_id().gmdate('Ymd').$_configuration['security_key']));
	//$filename = str_replace('.','dot',substr($web_path,strpos($web_path,'://')+3,-1)).'-z-'.api_get_course_id().'-z-'.api_get_user_id().'-z-'.gmdate('YmdHis').'.flv';//using -z- as fields splitter
	$filename = $mylpid.'-'.$mylpitemid.'-'.gmdate('YmdHis').api_get_user_id().'.flv';//using -z- as fields splitter
	$path_to_lzx = $web_path.'conference/'.$player.'?server='.urlencode($server).'&postURI='.$post_uri.'&filename='.$filename;
}

if(!empty($path_to_lzx)){
	printf("<object type=\"application/x-shockwave-flash\" data=\"%s\" 
			width='$width' height='$height'>
	         <param name=\"movie\" value=\"%s\">
		 <param name=\"quality\" value=\"high\">
		 <param name=\"scale\" value=\"noscale\">
		 <param name=\"salign\" value=\"LT\">
		 <param name=\"menu\" value=\"false\"></object>",$path_to_lzx,$path_to_lzx);
}
?>