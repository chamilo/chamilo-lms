<?php //$id: $
/**
 * Script that displays the header frame for lp_view.php
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */
$langFile[] = "scormdocument";
require_once('back_compat.inc.php');
require_once('learnpath.class.php');
require_once('scorm.class.php');
if(isset($_SESSION['lpobject'])){
	$temp = $_SESSION['lpobject'];
	$_SESSION['oLP'] = unserialize($temp);
}
$nameTools = $_SESSION['oLP']->get_name();
$header_hide_main_div = true;
$interbreadcrumb[]= array ("url"=>"./lp_controller.php?action=list", "name"=> get_lang('Doc'));
//$interbreadcrumb[]= array ("url"=>"./lp_controller.php?action=view&lp_id=".$_SESSION['oLP']->get_id(), "name"=> $nameTools);
$noPHP_SELF = true;
Display::display_header($nameTools,"Path");
?>
</div>
</body>
</html>