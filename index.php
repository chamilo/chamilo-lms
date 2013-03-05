<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.main
 */
define('CHAMILO_HOMEPAGE', true);

$language_file = array('courses', 'index');

// Flag forcing the 'current course' reset, as we're not inside a course anymore.
// Maybe we should change this into an api function? an example: CourseManager::unset();
$cidReset = true;

$app = require_once 'main/inc/global.inc.php';
require_once 'main/chat/chat_functions.lib.php';

// The section (for the tabs).
$this_section = SECTION_CAMPUS;

$htmlHeadXtra[] = api_get_jquery_libraries_js(array('bxslider'));
$htmlHeadXtra[] = '
<script>
	$(document).ready(function(){
		$("#slider").bxSlider({
			infiniteLoop	: true,
			auto			: true,
			pager			: true,
			autoHover		: true,
		pause			: 10000
		});
	});
</script>';

//set cookie for check if client browser are cookies enabled
//setcookie('TestCookie', 'cookies_yes', time()+3600*24*31*12);
//use Symfony\Component\HttpFoundation\Cookie;
//$cookie = new Cookie('TestCookie', 'cookies_yes', time()+3600*24*31*12);
//$response->headers->setCookie($cookie);


$app->run();