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
$language_file[] = "scormdocument";
$language_file[] = "scorm";
$language_file[] = "learnpath";
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
$htmlHeadXtra[] = '<script language="JavaScript" type="text/javascript">
  var dokeos_xajax_handler = window.parent.oxajax;
</script>';
$scorm_css_header=true;
$display_mode='';
$lp_theme_css=$_SESSION['oLP']->get_theme();
include_once('../inc/reduced_header.inc.php');
?>
<body>
  <?php  echo $_SESSION['oLP']->get_html_toc();?><br/>
</body>
</html>
<?php
if(!empty($_SESSION['oLP'])){
	$_SESSION['lpobject'] = serialize($_SESSION['oLP']);
}
?>