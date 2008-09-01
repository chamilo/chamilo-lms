<?php //$id: $
/**
 * Script that displays the author name and image of a LP
 * @package dokeos.learnpath
 * @author Julio Montoya Armas <gugli100@gmail.com>
 */

$use_anonymous = true;	
require_once('back_compat.inc.php');
require_once('learnpath.class.php');
require_once('scorm.class.php');
require_once('aicc.class.php');
//Getting the LP
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
// scorm.css loaded
include_once('../inc/reduced_header.inc.php');

echo '<html>
		<body>';
		$image = '../img/lp_author_background.gif';
		echo '<div id="image_preview">';
		echo '<table STYLE="width:250px;height:110px;background-image: url('.$image.');">';
			echo '<tr><td align="center">';		
			if ($_SESSION['oLP']->get_preview_image()!='')
				echo '<img alt="" src="'.api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image().'">';
			else
				echo Display::display_icon('unknown_250_100.jpg',' ');	
			echo '</td></tr>';	
			echo '</table>';
			echo '<div id="author_name">';			
			echo $_SESSION['oLP']->get_author();
			echo '</div>';	
		echo '</div>';		
?>
</body>
</html>