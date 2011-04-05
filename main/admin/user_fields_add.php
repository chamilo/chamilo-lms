<?php // $Id: user_fields_add.php 20845 2009-05-19 17:27:22Z cfasanando $
/* For licensing terms, see /dokeos_license.txt */
/**
*	@package chamilo.admin
*/
// name of the language file that needs to be included
$language_file = array('admin','registration');
$cidReset = true;

// including necessary libraries
require ('../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'usermanager.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';

// section for the tabs
$this_section=SECTION_PLATFORM_ADMIN;

// user permissions
api_protect_admin_script();
$htmlHeadXtra[] = '<script type="text/javascript">
function change_image_user_field (image_value) {
	
	if (image_value==1) {
		document.getElementById(\'options\').style.display = \'none\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
		$("div#id_image_user_field").html("&nbsp;");
		$("div#id_image_user_field").html('."'<br />".Display::return_icon('userfield_text.png', get_lang('AddUserFields'))."'".');

	} else if (image_value==2) {
		document.getElementById(\'options\').style.display = \'none\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
		$("div#id_image_user_field").html("&nbsp;");
		$("div#id_image_user_field").html('."'<br />".Display::return_icon('userfield_text_area.png', get_lang('AddUserFields'))."'".');

	} else if (image_value==3) {
		document.getElementById(\'options\').style.display = \'block\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';				
		$("div#id_image_user_field").html("&nbsp;");
		$("div#id_image_user_field").html('."'<br />".Display::return_icon('add_user_field_howto.png', get_lang('AddUserFields'))."'".');

	} else if (image_value==4) {
		document.getElementById(\'options\').style.display = \'block\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';		
		$("div#id_image_user_field").html("&nbsp;");
		$("div#id_image_user_field").html('."'<br />".Display::return_icon('userfield_drop_down.png', get_lang('AddUserFields'))."'".');

	} else if (image_value==5) {
		document.getElementById(\'options\').style.display = \'block\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';		
		$("div#id_image_user_field").html("&nbsp;");
		$("div#id_image_user_field").html('."'<br />".Display::return_icon('userfield_multidropdown.png', get_lang('AddUserFields'))."'".');

	} else if (image_value==6) {
		document.getElementById(\'options\').style.display = \'none\';
		$("div#id_image_user_field").html("&nbsp;");
		$("div#id_image_user_field").html('."'<br />".Display::return_icon('userfield_data.png', get_lang('AddUserFields'))."'".');

	} else if (image_value==7) {
		document.getElementById(\'options\').style.display = \'none\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';		
		$("div#id_image_user_field").html("&nbsp;");
		$("div#id_image_user_field").html('."'<br />".Display::return_icon('userfield_date_time.png', get_lang('AddUserFields'))."'".');

	} else if (image_value==8) {
		document.getElementById(\'options\').style.display = \'block\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';			
		$("div#id_image_user_field").html("&nbsp;");
		$("div#id_image_user_field").html('."'<br />".Display::return_icon('userfield_doubleselect.png', get_lang('AddUserFields'))."'".');

	} else if (image_value==9) {
		document.getElementById(\'options\').style.display = \'none\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
		$("div#id_image_user_field").html("&nbsp;");
		$("div#id_image_user_field").html('."'<br />".Display::return_icon('userfield_divider.png', get_lang('AddUserFields'))."'".');

	} else if (image_value==10) {
		document.getElementById(\'options\').style.display = \'none\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
		$("div#id_image_user_field").html("&nbsp;");
		$("div#id_image_user_field").html('."'<br />".Display::return_icon('userfield_user_tag.png', get_lang('UserTag'))."'".');

	}
}

function advanced_parameters() {			
	if(document.getElementById(\'options\').style.display == \'none\') {
		document.getElementById(\'options\').style.display = \'block\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';

	} else {
		document.getElementById(\'options\').style.display = \'none\';
		document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
	}
}

</script>';
// Database table definitions
$table_admin	= Database :: get_main_table(TABLE_MAIN_ADMIN);
$table_user 	= Database :: get_main_table(TABLE_MAIN_USER);
$table_uf	 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD);
$table_uf_opt 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
$table_uf_val 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'user_fields.php', 'name' => get_lang('UserFields'));
if ($_GET['action']<>'edit')
{
	$tool_name = get_lang('AddUserFields');
}
else
{
	$tool_name = get_lang('EditUserFields');
}
// Create the form
$form = new FormValidator('user_fields_add');
$form->addElement('header', '', $tool_name);

// Field display name
$form->addElement('text','fieldtitle',get_lang('FieldTitle'));
$form->applyFilter('fieldtitle','html_filter');
$form->applyFilter('fieldtitle','trim');
$form->addRule('fieldtitle', get_lang('ThisFieldIsRequired'), 'required');

// Field type
$types = array();
$types[USER_FIELD_TYPE_TEXT]  = get_lang('FieldTypeText');
$types[USER_FIELD_TYPE_TEXTAREA] = get_lang('FieldTypeTextarea');
$types[USER_FIELD_TYPE_RADIO] = get_lang('FieldTypeRadio');
$types[USER_FIELD_TYPE_SELECT] = get_lang('FieldTypeSelect');
$types[USER_FIELD_TYPE_SELECT_MULTIPLE] = get_lang('FieldTypeSelectMultiple');
$types[USER_FIELD_TYPE_DATE] = get_lang('FieldTypeDate');
$types[USER_FIELD_TYPE_DATETIME] = get_lang('FieldTypeDatetime');
$types[USER_FIELD_TYPE_DOUBLE_SELECT] 	= get_lang('FieldTypeDoubleSelect');
$types[USER_FIELD_TYPE_DIVIDER] 		= get_lang('FieldTypeDivider');
$types[USER_FIELD_TYPE_TAG] 		= get_lang('FieldTypeTag');
$types[USER_FIELD_TYPE_TIMEZONE]	= get_lang('FieldTypeTimezone');
$types[USER_FIELD_TYPE_SOCIAL_PROFILE] = get_lang('FieldTypeSocialProfile');

$form->addElement('select','fieldtype',get_lang('FieldType'),$types,array('onchange'=>'change_image_user_field(this.value)'));
$form->addRule('fieldtype', get_lang('ThisFieldIsRequired'), 'required');

//Advanced parameters
$form -> addElement('html','<div class="row">
			<div class="label">&nbsp;</div>
			<div class="formw">
				<a href="javascript://" onclick=" return advanced_parameters()"><span id="img_plus_and_minus"><div style="vertical-align:top;" ><img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'</div></span></a>
			</div>
			</div>');
//When edit, the combobox displey the field type displeyed else none 	
if ( (isset($_GET['action']) && $_GET['action'] == 'edit') && in_array($_GET['field_type'],array(3,4,5,8))) {
	$form -> addElement('html','<div id="options" style="display:block">');
} else {
	$form -> addElement('html','<div id="options" style="display:none">');
}

//field label
$form->addElement('hidden','fieldid',Security::remove_XSS($_GET['field_id']));
$form->addElement('text','fieldlabel',get_lang('FieldLabel'));
$form->applyFilter('fieldlabel','html_filter');
$form->addRule('fieldlabel', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
$form->addRule('fieldlabel', '', 'maxlength',60);
//$form->addRule('fieldlabel', get_lang('FieldTaken'), 'fieldlabel_available');

// Field options possible
$form->addElement('text','fieldoptions',get_lang('FieldPossibleValues').Display::return_icon('info3.gif', get_lang('FieldPossibleValuesComment'), array('align' => 'absmiddle', 'hspace' => '3px')));
$form->applyFilter('fieldoptions','trim');

if (is_numeric($_GET['field_id'])) {
	$form->addElement('static', 'option_reorder', '', '<a href="user_fields_options.php?field_id='.Security::remove_XSS($_GET['field_id']).'">'.get_lang('ReorderOptions').'</a>');
}

// Field default value
$form->addElement('text','fielddefaultvalue',get_lang('FieldDefaultValue'));
$form->applyFilter('fielddefaultvalue','trim');

// Set default values (only not empty when editing)
$defaults = array();
if (is_numeric($_GET['field_id'])) {
	$form_information = UserManager::get_extra_field_information((int)$_GET['field_id']);
	$defaults['fieldtitle'] = $form_information['field_display_text'];
	$defaults['fieldlabel'] = $form_information['field_variable'];
	$defaults['fieldtype'] = $form_information['field_type'];
	$defaults['fielddefaultvalue'] = $form_information['field_default_value'];

	$count = 0;
	// we have to concatenate the options
	if (count($form_information['options'])>0) {
		foreach ($form_information['options'] as $option_id=>$option) {
			if ($count<>0) {
				$defaults['fieldoptions'] = $defaults['fieldoptions'].'; '.$option['option_display_text'];
			} else {
				$defaults['fieldoptions'] = $option['option_display_text'];
			}
			$count++;
		}
	}
}

$form->setDefaults($defaults);

if(isset($_GET['field_id']) && !empty($_GET['field_id'])) {
	$class="save";
	$text=get_lang('buttonEditUserField');
} else { 
	$class="add";
	$text=get_lang('buttonAddUserField');
}
$form->addElement('html','</div>');

// Submit button
$form->addElement('style_submit_button', 'submit',$text, 'class='.$class.'');
// Validate form
if( $form->validate()) {
	$check = Security::check_token('post'); 
	if($check) {
		$field = $form->exportValues();
		$fieldlabel = empty($field['fieldlabel'])?$field['fieldtitle']:$field['fieldlabel'];		
		$fieldlabel = trim(strtolower(str_replace(" ","_",$fieldlabel)));	
		$fieldtype = $field['fieldtype'];
		$fieldtitle = $field['fieldtitle'];
		$fielddefault = $field['fielddefaultvalue'];
		$fieldoptions = $field['fieldoptions']; //comma-separated list of options

		if (is_numeric($field['fieldid']) AND !empty($field['fieldid']))
		{
			UserManager:: save_extra_field_changes($field['fieldid'],$fieldlabel,$fieldtype,$fieldtitle,$fielddefault,$fieldoptions);
			$message = get_lang('FieldEdited');
		}
		else
		{
			$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault,$fieldoptions);
			$message = get_lang('FieldAdded');
		}
		Security::clear_token();
		header('Location: user_fields.php?action=show_message&message='.urlencode(get_lang('FieldAdded')));
		exit ();
	}
} else {
	if(isset($_POST['submit'])){
		Security::clear_token();
	}
	$token = Security::get_token();
	$form->addElement('hidden','sec_token');
	$form->setConstants(array('sec_token' => $token));
}
// Display form
Display::display_header($tool_name);
//api_display_tool_title($tool_name);
if(!empty($_GET['message'])) {
	Display::display_normal_message($_GET['message']);
}
//else
//{
	//Display::display_normal_message(get_lang('UserFieldsAddHelp'),false);
//}
$form->display();

echo '<div id="id_image_user_field">';
if(!empty($defaults['fieldtype'])) {
	$image_value = $defaults['fieldtype'];
	if ($image_value==1) {
		echo '<br />'.Display::return_icon('userfield_text.png', get_lang('AddUserFields'));
	} else if ($image_value==2) {
		echo '<br />'.Display::return_icon('userfield_text_area.png', get_lang('AddUserFields'));
	} else if ($image_value==3) {
		echo '<br />'.Display::return_icon('add_user_field_howto.png', get_lang('AddUserFields'));
	} else if ($image_value==4) {
		echo '<br />'.Display::return_icon('userfield_drop_down.png', get_lang('AddUserFields'));
	} else if ($image_value==5) {
		echo '<br />'.Display::return_icon('userfield_multidropdown.png', get_lang('AddUserFields'));
	} else if ($image_value==6) {
		echo '<br />'.Display::return_icon('userfield_data.png', get_lang('AddUserFields'));
	} else if ($image_value==7) {
		echo '<br />'.Display::return_icon('userfield_date_time.png', get_lang('AddUserFields'));
	} else if ($image_value==8) {
		echo '<br />'.Display::return_icon('userfield_doubleselect.png', get_lang('AddUserFields'));
	} else if ($image_value==9) {
		echo '<br />'.Display::return_icon('userfield_divider.png', get_lang('AddUserFields'));
	} else if ($image_value==10) {
		echo '<br />'.Display::return_icon('userfield_user_tag.png', get_lang('UserTag'));
	}
} else {
	echo '<br />'.Display::return_icon('userfield_text.png', get_lang('AddUserFields'));
}
echo '</div>';

// footer
Display::display_footer();