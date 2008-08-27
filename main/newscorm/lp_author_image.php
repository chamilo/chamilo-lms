<?php //$id: $
/**
 * Script that displays the header frame for lp_view.php
 * @package dokeos.learnpath
 * @author 
 */
/**
 * Script
 */

$use_anonymous = true;	
require_once('back_compat.inc.php');
require_once('learnpath.class.php');
require_once('scorm.class.php');
require_once('aicc.class.php');

if(isset($_SESSION['lpobject']))
{
	$oLP = unserialize($_SESSION['lpobject']);
	if(is_object($oLP)){
		$_SESSION['oLP'] = $oLP;
	}else{
		die('Could not instanciate lp object');
	}
} /*
$charset = $_SESSION['oLP']->encoding;
$lp_theme_css=$_SESSION['oLP']->get_theme();

*/
$scorm_css_header=true;
include_once('../inc/reduced_header.inc.php');

echo '<html>
		<body>';
echo '<div id="preview_image">';		
if ($_SESSION['oLP']->get_preview_image()!='')
	echo '<img alt="'.$_SESSION['oLP']->get_author().'" src="'.api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image().'">';
else 
	 echo Display::display_icon('unknown.jpg',$_SESSION['oLP']->get_author());
echo '</div>';

?>
</body>
</html>