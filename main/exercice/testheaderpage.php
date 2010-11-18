<?php //$id: $
/* For licensing terms, see /license.txt */
/**
*	Code library for HotPotatoes integration.
*	@package dokeos.exercise
* 	@author Istvan Mandak
*/
// name of the language file that needs to be included
$language_file='exercice';

require '../inc/global.inc.php';

require_once(api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php');
$documentPath= api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
$my_file = Security::remove_XSS($_GET['file']);

$my_file=str_replace(array('../','\\..','\\0','..\\'),array('','','',''),urldecode($my_file));

$title = GetQuizName($my_file,$documentPath);
if ($title =='') {
	$title = basename($my_file);
}
$nameTools = $title;
$noPHP_SELF=true;
if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('ToolGradebook')
		);
}
$interbreadcrumb[]= array ("url"=>"./exercice.php", "name"=> get_lang('Exercices'));
Display::display_header($nameTools,"Exercise");
echo "<a name='TOP'></a>";