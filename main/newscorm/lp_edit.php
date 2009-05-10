<?php //$id: $
/**
 * Script allowing simple edition of learnpath information (title, description, etc)
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
*/

require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');

$show_description_field = false; //for now
$nameTools = get_lang("Doc");
event_access_tool(TOOL_LEARNPATH);
if (! $is_allowed_in_course) api_not_allowed();

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {	
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}
$interbreadcrumb[]= array ("url"=>"lp_controller.php?action=list", "name"=> get_lang("_learning_path"));
$interbreadcrumb[]= array ("url"=>api_get_self()."?action=admin_view&lp_id=$learnpath_id", "name" => $_SESSION['oLP']->get_name());

Display::display_header(null,'Path');

//Page subtitle
echo '<h4>'.get_lang('EditLPSettings').'</h4>';

$fck_attribute['Width'] = '400px';
$fck_attribute['Height'] = '150px';
$fck_attribute['ToolbarSet'] = 'CommentLearningPath';

$defaults=array();
$form = new FormValidator('form1', 'post', 'lp_controller.php');

//Title
$form -> addElement('text', 'lp_name', api_ucfirst(get_lang('_title')),array('size'=>43));
$form-> applyFilter('lp_name', 'html_filter');
//Encoding
$encoding_select = &$form->addElement('select', 'lp_encoding', get_lang('Charset'));
$encodings = array('UTF-8','ISO-8859-1','ISO-8859-15','cp1251','cp1252','KOI8-R','BIG5','GB2312','Shift_JIS','EUC-JP');
foreach($encodings as $encoding){
	if (api_equal_encodings($encoding, $_SESSION['oLP']->encoding)) {
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

//Author
//$form -> addElement('text', 'lp_author', ucfirst(get_lang('Author')));
//$form->add_html_editor('lp_author', get_lang('Author')); 

$form->addElement('html_editor','lp_author',get_lang('Author'),array('size'=>80) ); 
$form->applyFilter('lp_author', 'html_filter');
// LP image	
$form->add_progress_bar();
if( strlen($_SESSION['oLP']->get_preview_image() ) > 0)
{
	$show_preview_image='<img src='.api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image().'>';
	$div = '<div class="row">
	<div class="label">'.get_lang('ImagePreview').'</div>
	<div class="formw">	
	'.$show_preview_image.'
	</div>
	</div>';	
	$form->addElement('html', $div .'<br/>');	
	$form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));	
}

$form->addElement('file', 'lp_preview_image', ($_SESSION['oLP']->get_preview_image() != '' ? get_lang('UpdateImage') : get_lang('AddImage')));

	$div = '<div class="row">
	<div class="label"></div>
	<div class="formw">	'.get_lang('ImageWillResizeMsg').'
	</div>
	</div>';
$form->addElement('html', $div);

/*
$form->addRule('lp_preview_image', get_lang('OnlyImagesAllowed'), 'mimetype', array('image/gif', 'image/jpeg', 'image/png'));
*/
$form->addRule('lp_preview_image', get_lang('OnlyImagesAllowed'), 'filetype', array ('jpg', 'jpeg', 'png', 'gif'));

// Search terms (only if search is activated)
if (api_get_setting('search_enabled') === 'true')
{
	$specific_fields = get_specific_field_list();
	foreach ($specific_fields as $specific_field) {
		$form -> addElement ('text', $specific_field['code'], $specific_field['name']);
		$filter = array('course_code'=> "'". api_get_course_id() ."'", 'field_id' => $specific_field['id'], 'ref_id' => $_SESSION['oLP']->lp_id, 'tool_id' => '\''. TOOL_LEARNPATH .'\'');
		$values = get_specific_field_values_list($filter, array('value'));
		if ( !empty($values) ) {
			$arr_str_values = array();
			foreach ($values as $value) {
				$arr_str_values[] = $value['value'];
			}
			$defaults[$specific_field['code']] = implode(', ', $arr_str_values);
		}
	}
}

//default values
$content_proximity_select -> setSelected($s_selected_proximity);
$origin_select -> setSelected($s_selected_origin);
$encoding_select -> setSelected($s_selected_encoding);
$defaults['lp_name']=$_SESSION['oLP']->get_name();
$defaults['lp_author'] = $_SESSION['oLP']->get_author();

//Submit button
$form->addElement('style_submit_button', 'Submit',get_lang('SaveLPSettings'),'class="save"');
//'<img src="'.api_get_path(WEB_IMG_PATH).'accept.png'.'" alt=""/>'

//Hidden fields
$form->addElement('hidden', 'action', 'update_lp');
$form->addElement('hidden', 'lp_id', $_SESSION['oLP']->get_id());

$form->setDefaults($defaults);
echo '<table><tr><td width="500px">';
	$form -> display();
echo '</td><td valign="top"><img src="../img/course_setting_layout.png" /></td></tr></table>';

Display::display_footer();
?>
