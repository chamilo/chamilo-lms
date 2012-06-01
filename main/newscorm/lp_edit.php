<?php
/* For licensing terms, see /license.txt */
/**
 * Script allowing simple edition of learnpath information (title, description, etc)
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
*/

require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

global $charset;

$show_description_field = false; //for now
$nameTools = get_lang('Doc');
$this_section = SECTION_COURSES;
event_access_tool(TOOL_LEARNPATH);

api_protect_course_script();
//if (! $is_allowed_in_course) api_not_allowed();

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}
$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));
$interbreadcrumb[] = array('url' => api_get_self()."?action=build&lp_id=".$_SESSION['oLP']->get_id(), 'name' => $_SESSION['oLP']->get_name());
//$interbreadcrumb[] = array('url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id", 'name' => get_lang('NewStep'));

$htmlHeadXtra[] = '<script type="text/javascript">        
function activate_start_date() {
	if(document.getElementById(\'start_date_div\').style.display == \'none\') {
		document.getElementById(\'start_date_div\').style.display = \'block\';
	} else {
		document.getElementById(\'start_date_div\').style.display = \'none\';
	}
}

function activate_end_date() {
    if(document.getElementById(\'end_date_div\').style.display == \'none\') {
        document.getElementById(\'end_date_div\').style.display = \'block\';
    } else {
        document.getElementById(\'end_date_div\').style.display = \'none\';
    }
}
       
</script>';


Display::display_header(get_lang('CourseSettings'), 'Path');

echo $_SESSION['oLP']->build_action_menu();


// Action links
//echo '<div class="actions">';
$gradebook = isset($_GET['gradebook']) ? Security::remove_XSS($_GET['gradebook']) : null;
//echo '<a href="lp_controller.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;gradebook='.$gradebook.'&amp;action=build&amp;lp_id=' . Security::remove_XSS($_GET['lp_id']) . '" title="'.get_lang('Build').'">'.Display::return_icon('build_learnpath.png', get_lang('Build'),'',ICON_SIZE_MEDIUM).'</a>';
//echo '<a href="lp_controller.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;gradebook='.$gradebook.'&amp;action=admin_view&amp;lp_id=' . Security::remove_XSS($_GET['lp_id']) . '" title="'.get_lang('BasicOverview').'">'.Display::return_icon('move_learnpath.png', get_lang('BasicOverview'),'',ICON_SIZE_MEDIUM).'</a>';
//echo '<a href="lp_controller.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;gradebook='.$gradebook.'&amp;action=view&lp_id='.Security::remove_XSS($_GET['lp_id']).'">'.Display::return_icon('view_remove.png', get_lang('Display'),'',ICON_SIZE_MEDIUM).'</a>';
//echo ' '.Display::return_icon('i.gif');
/*echo '<a href="lp_controller.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;gradebook='.$gradebook.'&amp;action=add_item&amp;type=step&amp;lp_id=' . Security::remove_XSS($_GET['lp_id']) . '" title="'.get_lang('NewStep').'">
'.Display::return_icon('add.png', get_lang('NewStep'),'',ICON_SIZE_MEDIUM).'</a>';*/

/*echo '<a href="lp_controller.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;gradebook='.$gradebook.'&amp;action=add_item&amp;type=chapter&amp;lp_id=' . Security::remove_XSS($_GET['lp_id']) . '" title="'.get_lang('NewChapter').'">
'.Display::return_icon('add_learnpath_section.png', get_lang('NewChapter'),'',ICON_SIZE_MEDIUM).'</a>';*/

//echo '<a href="lp_controller.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;gradebook='.$gradebook.'&amp;action=admin_view&amp;lp_id='.Security::remove_XSS($_GET['lp_id']).'&amp;updateaudio=true">'.Display::return_icon('upload_audio.png', get_lang('UpdateAllAudioFragments'),'',ICON_SIZE_MEDIUM).'</a>';

//echo Display::url(Display::return_icon('settings_na.png', get_lang('CourseSettings'),'',ICON_SIZE_MEDIUM), '#');
//echo '<a href="../newscorm/lp_controller.php?cidReq='.$_course['sysCode'].'">'.Display::return_icon('scorms_na.png',get_lang('ReturnToLearningPaths'),'',ICON_SIZE_MEDIUM).'</a>';

//echo '</div>';

$defaults=array();
$form = new FormValidator('form1', 'post', 'lp_controller.php');

// Form title
$form->addElement('header', null, get_lang('EditLPSettings'));

// Title
$form->addElement('text', 'lp_name', api_ucfirst(get_lang('_title')), array('size' => 43));
$form->applyFilter('lp_name', 'html_filter');
$form->addRule('lp_name', get_lang('ThisFieldIsRequired'), 'required');

// Metadata
//$clean_scorm_id=Security::remove_XSS($_GET['lp_id']);
//$metadata_link = '<a href="../metadata/index.php?eid='.urlencode('Scorm.'.$clean_scorm_id).'">'.get_lang('AddMetadata').'</a>';
//$form->addElement('static', null, get_lang('Metadata'), $metadata_link);

// Encoding
/* // Chamilo 1.8.8: Deprecated code.
$encoding_select = &$form->addElement('select', 'lp_encoding', get_lang('Charset'));
$encodings = array('UTF-8','ISO-8859-1','ISO-8859-15','cp1251','cp1252','KOI8-R','BIG5','GB2312','Shift_JIS','EUC-JP');
foreach ($encodings as $encoding) {
    if (api_equal_encodings($encoding, $_SESSION['oLP']->encoding)) {
          $s_selected_encoding = $encoding;
      }
      $encoding_select->addOption($encoding,$encoding);
}
*/
$form->addElement('hidden', 'lp_encoding');

// Origin
/*
$origin_select = &$form->addElement('select', 'lp_maker', get_lang('Origin'));
$lp_orig = $_SESSION['oLP']->get_maker();

include 'content_makers.inc.php';
foreach ($content_origins as $origin) {
    if ($lp_orig == $origin) {
        $s_selected_origin = $origin;
    }
    $origin_select->addOption($origin, $origin);
}

// Content proximity
$content_proximity_select = &$form->addElement('select', 'lp_proximity', get_lang('ContentProximity'));
$lp_prox = $_SESSION['oLP']->get_proximity();
if ($lp_prox != 'local') {
    $s_selected_proximity = 'remote';
} else {
    $s_selected_proximity = 'local';
}
$content_proximity_select->addOption(get_lang('Local'), 'local');
$content_proximity_select->addOption(get_lang('Remote'), 'remote');
*/
//Hide toc frame
$hide_toc_frame = &$form->addElement('checkbox', 'hide_toc_frame', null, get_lang('HideTocFrame'),array('onclick' => '$("#lp_layout_column").toggle()' ));
if (api_get_setting('allow_course_theme') == 'true') {
    $mycourselptheme = api_get_course_setting('allow_learning_path_theme');
    if (!empty($mycourselptheme) && $mycourselptheme!=-1 && $mycourselptheme== 1) {
        //LP theme picker
        $theme_select = &$form->addElement('select_theme', 'lp_theme', get_lang('Theme'));
        $form->applyFilter('lp_theme', 'trim');

        $s_theme = $_SESSION['oLP']->get_theme();
        $theme_select ->setSelected($s_theme); //default
    }
}

// Author
$form->addElement('html_editor', 'lp_author', get_lang('Author'), array('size' => 80), array('ToolbarSet' => 'LearningPathAuthor', 'Width' => '100%', 'Height' => '150px') );
$form->applyFilter('lp_author', 'html_filter');

// LP image
$form->add_progress_bar();
if (strlen($_SESSION['oLP']->get_preview_image()) > 0) {
    $show_preview_image='<img src='.api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image().'>';    
    $form->addElement('label', get_lang('ImagePreview'), $show_preview_image);
    $form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
}
$label = ($_SESSION['oLP']->get_preview_image() != '' ? get_lang('UpdateImage') : get_lang('AddImage'));
$form->addElement('file', 'lp_preview_image', array($label, get_lang('ImageWillResizeMsg')));

$form->addRule('lp_preview_image', get_lang('OnlyImagesAllowed'), 'filetype', array ('jpg', 'jpeg', 'png', 'gif'));

// Search terms (only if search is activated).
if (api_get_setting('search_enabled') === 'true') {
    $specific_fields = get_specific_field_list();
    foreach ($specific_fields as $specific_field) {
        $form -> addElement ('text', $specific_field['code'], $specific_field['name']);
        $filter = array('c_id'=> "'". api_get_course_int_id() ."'", 'field_id' => $specific_field['id'], 'ref_id' => $_SESSION['oLP']->lp_id, 'tool_id' => '\''. TOOL_LEARNPATH .'\'');
        $values = get_specific_field_values_list($filter, array('value'));
        if (!empty($values)) {
            $arr_str_values = array();
            foreach ($values as $value) {
                $arr_str_values[] = $value['value'];
            }
            $defaults[$specific_field['code']] = implode(', ', $arr_str_values);
        }
    }
}

$defaults['lp_encoding']    = Security::remove_XSS($_SESSION['oLP']->encoding);
$defaults['lp_name']        = Security::remove_XSS($_SESSION['oLP']->get_name());
$defaults['lp_author']      = Security::remove_XSS($_SESSION['oLP']->get_author());
$defaults['hide_toc_frame'] = Security::remove_XSS($_SESSION['oLP']->get_hide_toc_frame());

$expired_on     = $_SESSION['oLP'] ->expired_on;
$publicated_on  = $_SESSION['oLP'] ->publicated_on;

// Prerequisites
$form->addElement('html', '<div class="control-group"><label class="control-label">'.get_lang('Prerequisites').'</label>
<div class="controls">'.$_SESSION['oLP']->display_lp_prerequisites_list().' <span class="help-block">'.get_lang('LpPrerequisiteDescription').'</span></div></div>');

//Start date
$form->addElement('checkbox', 'activate_start_date_check', null,get_lang('EnableStartTime'), array('onclick' => 'activate_start_date()'));
$display_date = 'none';
if ($publicated_on!='0000-00-00 00:00:00' && !empty($publicated_on)) {
	$display_date = 'block';
	$defaults['activate_start_date_check'] = 1;
}

$form->addElement('html','<div id="start_date_div" style="display:'.$display_date.';">');
$form->addElement('datepicker', 'publicated_on', get_lang('PublicationDate'), array('form_name'=>'exercise_admin'), 5);
$form->addElement('html','</div>');

//End date
$form->addElement('checkbox', 'activate_end_date_check',  null, get_lang('EnableEndTime'),  array('onclick' => 'activate_end_date()'));
$display_date = 'none';
if ($expired_on!='0000-00-00 00:00:00' && !empty($expired_on)) {
	$display_date = 'block';
	$defaults['activate_end_date_check'] = 1;
}

$form->addElement('html','<div id="end_date_div" style="display:'.$display_date.';">');
$form->addElement('datepicker', 'expired_on', get_lang('ExpirationDate'), array('form_name'=>'exercise_admin'), 5);
$form->addElement('html','</div>');

if (api_is_platform_admin()) {
    $form->addElement('checkbox', 'use_max_score', null, get_lang('UseMaxScore100'));    
    $defaults['use_max_score'] = $_SESSION['oLP']->use_max_score;
}


//Submit button
$form->addElement('style_submit_button', 'Submit',get_lang('SaveLPSettings'),'class="save"');

// Hidden fields
$form->addElement('hidden', 'action', 'update_lp');
$form->addElement('hidden', 'lp_id', $_SESSION['oLP']->get_id());


$defaults['publicated_on']  = ($publicated_on!='0000-00-00 00:00:00' && !empty($publicated_on))? api_get_local_time($publicated_on) : date('Y-m-d 12:00:00');
$defaults['expired_on']     = ($expired_on   !='0000-00-00 00:00:00' && !empty($expired_on) )? api_get_local_time($expired_on): date('Y-m-d 12:00:00',time()+84600);

$form->setDefaults($defaults);
echo '<table><tr><td width="550px">';
$form -> display();
if ($_SESSION['oLP']->get_hide_toc_frame() == 1  ){
  $display_img='none';
}
else{
  $display_img='block';
}
echo '</td><td id="lp_layout_column" style="display:'.$display_img.'"valign="top"><img src="../img/course_setting_layout.png" /></td></tr></table>';

Display::display_footer();