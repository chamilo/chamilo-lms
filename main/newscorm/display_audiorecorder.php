<?php //$id: $
/**
 * Script opened in an iframe and containing the learning path's table of contents
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 * @license	GNU/GPL - See Dokeos license directory for details
 */
/**
 * Script
 */

//flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;

require_once('back_compat.inc.php');
require_once('learnpath.class.php');
require_once('scorm.class.php');
require_once('aicc.class.php');

if(isset($_SESSION['lpobject']))
{
	//if($debug>0) error_log('New LP - in lp_toc.php - SESSION[lpobject] is defined',0);
	$oLP = unserialize($_SESSION['lpobject']);
	if(is_object($oLP)){
		$_SESSION['oLP'] = $oLP;
	}else{
		//error_log('New LP - in lp_toc.php - SESSION[lpobject] is not object - dying',0);
		die('Could not instanciate lp object');
	}
}
$charset = $_SESSION['oLP']->encoding;

echo '<html>
		<body>';

echo '<div id="audiorecorder">	';
	

$audio_recorder_studentview = 'true';


$audio_recorder_item_id = $_SESSION['oLP']->current;
if(api_get_setting('service_ppt2lp','active')=='true' && api_get_setting('service_ppt2lp','path_to_lzx')!=''){
	include('audiorecorder.inc.php');
}
// end of audiorecorder include
	
echo '</div></body></html>';


?>
