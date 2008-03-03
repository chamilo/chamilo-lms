<?php //$id: $
/**
 * Script allowing simple edition of learnpath information (title, description, etc)
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

$show_description_field = false; //for now

$nameTools = get_lang("Doc");

event_access_tool(TOOL_LEARNPATH);

if (! $is_allowed_in_course) api_not_allowed();

$interbreadcrumb[]= array ("url"=>"lp_controller.php?action=list", "name"=> get_lang("_learning_path"));
$interbreadcrumb[]= array ("url"=>api_get_self()."?action=admin_view&lp_id=$learnpath_id", "name" => $_SESSION['oLP']->get_name());

Display::display_header(null,'Path');

//Page subtitle
echo '<h4>'.get_lang('_edit_learnpath').'</h4>';

$defaults=array();
$form = new FormValidator('form1', 'post', 'lp_controller.php');

//Title
$form -> addElement('text', 'lp_name', ucfirst(get_lang('_title')));

//Ecoding
$encoding_select = &$form->addElement('select', 'lp_encoding', get_lang('Charset'));
$encodings = array('UTF-8','ISO-8859-1','ISO-8859-15','cp1251','cp1252','KOI8-R','BIG5','GB2312','Shift_JIS','EUC-JP');
foreach($encodings as $encoding){
	if($encoding == $_SESSION['oLP']->encoding){
  		$s_selected_encoding = $encoding;
  	}
  	$encoding_select->addOption($encoding,$encoding);
}


//Origin
$origin_select = &$form->addElement('select', 'lp_maker', get_lang('Origin'));
$lp_orig = $_SESSION['oLP']->get_maker();
include('content_makers.inc.php');
foreach($content_origins as $origin){
	if($lp_orig == $origin){
		$s_selected_origin = $origin;
	}
	$origin_select->addOption($origin,$origin);
}


//Content proximity
$content_proximity_select = &$form->addElement('select', 'lp_proximity', get_lang('ContentProximity'));
$lp_prox = $_SESSION['oLP']->get_proximity();
if($lp_prox != 'local'){
	$s_selected_proximity = 'remote';
}else{
	$s_selected_proximity = 'local';
}
$content_proximity_select->addOption(get_lang('Local'), 'local');
$content_proximity_select->addOption(get_lang('Remote'), 'remote');


if (api_get_setting('allow_course_theme') == 'true')
{		
	$mycourselptheme=api_get_course_setting('allow_learning_path_theme');
	if (!empty($mycourselptheme) && $mycourselptheme!=-1 && $mycourselptheme== 1) 
	{			
		//LP theme picker				
		$theme_select = &$form->addElement('select_theme', 'lp_theme', get_lang('Theme'));
		$form->applyFilter('lp_theme', 'trim');
		
		$s_theme = $_SESSION['oLP']->get_theme();
		$theme_select ->setSelected($s_theme); //default	
	}	
}


//default values
$content_proximity_select -> setSelected($s_selected_proximity);
$origin_select -> setSelected($s_selected_origin);
$encoding_select -> setSelected($s_selected_encoding);
$defaults["lp_name"]=$_SESSION['oLP']->get_name();

//Submit button
$form->addElement('submit', 'Submit', get_lang('Ok'));


//Hidden fields
$form->addElement('hidden', 'action', 'update_lp');
$form->addElement('hidden', 'lp_id', $_SESSION['oLP']->get_id());


$form->setDefaults($defaults);
$form -> display();

Display::display_footer();

?>