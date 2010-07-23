<?php
/* For licensing terms, see /license.txt */
/**
* With this tool you can easily adjust non critical configuration settings.
* Non critical means that changing them will not result in a broken campus.
*
* @author Patrick Cool
* @author Julio Montoya - Multiple URL site
* @package chamilo.admin
*/

/*		INIT SECTION	*/
// name of the language file that needs to be included
if ($_GET['category']=='Templates') {
	$language_file = array('admin','document');
} else if($_GET['category']=='Gradebook') {
	$language_file = array('admin','gradebook');
} else {
	$language_file = array('admin');
}
// resetting the course id
$cidReset=true;
// including some necessary chamilo files
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'dashboard.lib.php';
require_once 'settings.lib.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
$_SESSION['this_section'] = $this_section;

// Access restrictions
api_protect_admin_script();
/* this code is moved to gradebook_scoring_system file
if($_GET['category'] == 'Gradebook') {
	// Used for the gradebook system
	$htmlHeadXtra[]= '
	  <script language="JavaScript">
	  function plusItem(item)
	  {
			document.getElementById(item).style.display = "inline";
			document.getElementById("plus-"+item).style.display = "none";
			document.getElementById("min-"+(item-1)).style.display = "none";
			document.getElementById("min-"+(item)).style.display = "inline";
			document.getElementById("plus-"+(item+1)).style.display = "inline";
			document.getElementById("txta-"+(item)).value = "100";
			document.getElementById("txta-"+(item-1)).value = "";
	  }

	  function minItem(item)
	   {
		if (item != 1)
		{
		 document.getElementById(item).style.display = "none";
		 document.getElementById("txta-"+item).value = "";
		 document.getElementById("txtb-"+item).value = "";
		 document.getElementById("plus-"+item).style.display = "inline";
		 document.getElementById("min-"+(item-1)).style.display = "inline";
		 document.getElementById("txta-"+(item-1)).value = "100";

		}
		if (item = 1)
		{
			document.getElementById("min-"+(item)).style.display = "none";
		}
	  }
	 </script>';
 }
*/
// Submit Stylesheets
if (isset($_POST['submit_stylesheets'])) {
	$message = store_stylesheets();
	header("Location: ".api_get_self()."?category=stylesheets");
	exit;
}

// Database Table Definitions
$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

// setting breadcrumbs
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

// setting the name of the tool
$tool_name = get_lang('DokeosConfigSettings');

// Build the form
if (!empty($_GET['category']) && !in_array($_GET['category'], array('Plugins', 'stylesheets', 'Search'))) {
	$form = new FormValidator('settings', 'post', 'settings.php?category='.$_GET['category']);
	$renderer = & $form->defaultRenderer();
	$renderer->setHeaderTemplate('<div class="sectiontitle">{header}</div>'."\n");
	$renderer->setElementTemplate('<div class="sectioncomment">{label}</div>'."\n".'<div class="sectionvalue">{element}</div>'."\n");
	$my_category = Database::escape_string($_GET['category']);

	$sqlcountsettings = "SELECT COUNT(*) FROM $table_settings_current WHERE category='".$my_category."' AND type<>'checkbox'";
	$resultcountsettings = Database::query($sqlcountsettings);
	$countsetting = Database::fetch_array($resultcountsettings);

	if ($_configuration['access_url']==1) {
		$settings = api_get_settings($my_category,'group',$_configuration['access_url']);
	} else {
		$url_info = api_get_access_url($_configuration['access_url']);
		if ($url_info['active']==1) {
			//the default settings of Dokeos
			$settings = api_get_settings($my_category,'group',1,0);
			//the settings that are changeable from a particular site
			$settings_by_access = api_get_settings($my_category,'group',$_configuration['access_url'],1);
			//echo '<pre>';
			//print_r($settings_by_access);
			$settings_by_access_list=array();
			foreach($settings_by_access as $row)
			{
				if (empty($row['variable']))
					$row['variable']=0;
				if (empty($row['subkey']))
					$row['subkey']=0;
				if (empty($row['category']))
					$row['category']=0;
				// one more validation if is changeable
				if ($row['access_url_changeable']==1)
					$settings_by_access_list[ $row['variable'] ] [ $row['subkey'] ]	[ $row['category'] ]  = $row;
				else
					$settings_by_access_list[ $row['variable'] ] [ $row['subkey'] ]	[ $row['category'] ]  = array();
			}
		}
	}

	//print_r($settings_by_access_list);echo '</pre>';
	//$sqlsettings = "SELECT DISTINCT * FROM $table_settings_current WHERE category='$my_category' GROUP BY variable ORDER BY id ASC";
	//$resultsettings = Database::query($sqlsettings);
	//while ($row = Database::fetch_array($resultsettings))
	$default_values = array();
	foreach($settings as $row) {
		// Settings to avoid
		$rows_to_avoid = array('search_enabled', 'gradebook_enable');
		if (in_array($row['variable'], $rows_to_avoid)) { continue; }

		$anchor_name = $row['variable'].(!empty($row['subkey']) ? '_'.$row['subkey'] : '');
		$form->addElement('html',"\n<a name=\"$anchor_name\"></a>\n");

		($countsetting['0']%10) < 5 ?$b=$countsetting['0']-10:$b=$countsetting['0'];
		if ($i % 10 == 0 and $i<$b) {
			$form->addElement('html','<div align="right">');
			$form->addElement('style_submit_button', null,get_lang('SaveSettings'), 'class="save"');
			$form->addElement('html','</div>');
		}

		$i++;

		$form->addElement('header', null, get_lang($row['title']));

		if ($row['access_url_changeable'] == '1' && $_configuration['multiple_access_urls']) {
			$form->addElement('html', '<div style="float:right;">'.Display::return_icon('shared_setting.png',get_lang('SharedSettingIconComment')).'</div>');
		}

		$hideme=array();
		$hide_element=false;
		if ($_configuration['access_url']!=1) {
			if ($row['access_url_changeable']==0) {
				//we hide the element in other cases (checkbox, radiobutton) we 'freeze' the element
				$hide_element=true;
				$hideme=array('disabled');
			} elseif($url_info['active']==1) {
				// we show the elements
				if (empty($row['variable']))
					$row['variable']=0;
				if (empty($row['subkey']))
					$row['subkey']=0;
				if (empty($row['category']))
					$row['category']=0;

				if (is_array ($settings_by_access_list[ $row['variable'] ] [ $row['subkey'] ]	[ $row['category'] ])) {
					// we are sure that the other site have a selected value
					if ($settings_by_access_list[ $row['variable'] ] [ $row['subkey'] ]	[ $row['category'] ]['selected_value']!='')
						$row['selected_value']	=$settings_by_access_list[$row['variable']] [$row['subkey']]	[ $row['category'] ]['selected_value'];
				}
				// there is no else because we load the default $row['selected_value'] of the main Dokeos site
			}
		}

		switch ($row['type']) {
			case 'textfield' :
				if ($row['variable']=='account_valid_duration') {
					$form->addElement('text', $row['variable'], get_lang($row['comment']),array('maxlength'=>'5'));
					$form->applyFilter($row['variable'],'html_filter');
					$default_values[$row['variable']] = $row['selected_value'];

				// For platform character set selection: Conversion of the textfield to a select box with valid values.
				} elseif ($row['variable'] == 'platform_charset') {
					$current_system_encoding = api_refine_encoding_id(trim($row['selected_value']));
					$valid_encodings = array_flip(api_get_valid_encodings());
					if (!isset($valid_encodings[$current_system_encoding])) {
						$is_alias_encoding = false;
						foreach ($valid_encodings as $encoding) {
							if (api_equal_encodings($encoding, $current_system_encoding)) {
								$is_alias_encoding = true;
								$current_system_encoding = $encoding;
								break;
							}
						}
						if (!$is_alias_encoding) {
							$valid_encodings[$current_system_encoding] = $current_system_encoding;
						}
					}
					foreach ($valid_encodings as $key => &$encoding) {
						if (api_is_encoding_supported($key) && Database::is_encoding_supported($key)) {
							$encoding = $key;
						} else {
							//$encoding = $key.' (n.a.)';
							unset($valid_encodings[$key]);
						}
					}
					$form->addElement('select', $row['variable'], get_lang($row['comment']), $valid_encodings);
					$default_values[$row['variable']] = $current_system_encoding;
				} else {
					$form->addElement('text', $row['variable'], get_lang($row['comment']),$hideme);
					$form->applyFilter($row['variable'],'html_filter');
					$default_values[$row['variable']] = $row['selected_value'];
				}
				break;
			case 'textarea' :
				$form->addElement('textarea', $row['variable'], get_lang($row['comment']),$hideme);
				$default_values[$row['variable']] = $row['selected_value'];
				break;
			case 'radio' :
				$values = get_settings_options($row['variable']);
				$group = array ();
				if (is_array($values )) {
					foreach ($values as $key => $value) {
						$element = & $form->createElement('radio', $row['variable'], '', get_lang($value['display_text']), $value['value']);
						if ($hide_element) {
							$element->freeze();
						}
						$group[] = $element;
					}
				}
				$form->addGroup($group, $row['variable'], get_lang($row['comment']), '<br />', false);
				$default_values[$row['variable']] = $row['selected_value'];
				break;
			case 'checkbox';
				//1. we collect all the options of this variable
				$sql = "SELECT * FROM settings_current WHERE variable='".$row['variable']."' AND access_url =  1";

				$result = Database::query($sql);
				$group = array ();
				while ($rowkeys = Database::fetch_array($result)) {
 					if ($rowkeys['variable'] == 'course_create_active_tools' && $rowkeys['subkey'] == 'enable_search') {continue;}

 					// profile tab option hide, if the social tool is enabled
 					if (api_get_setting('allow_social_tool') == 'true') {
 						if ($rowkeys['variable'] == 'show_tabs' && $rowkeys['subkey'] == 'my_profile') {continue;}
 					}

 					//hiding the gradebook option
 					if ($rowkeys['variable'] == 'show_tabs' && $rowkeys['subkey'] == 'my_gradebook') {continue;}

					$element = & $form->createElement('checkbox', $rowkeys['subkey'], '', get_lang($rowkeys['subkeytext']));
					if ($row['access_url_changeable']==1) {
						//2. we look into the DB if there is a setting for a specific access_url
						$access_url = $_configuration['access_url'];
						if(empty($access_url )) $access_url =1;
						$sql = "SELECT selected_value FROM settings_current WHERE variable='".$rowkeys['variable']."' AND subkey='".$rowkeys['subkey']."'  AND  subkeytext='".$rowkeys['subkeytext']."' AND access_url =  $access_url";
						$result_access = Database::query($sql);
						$row_access = Database::fetch_array($result_access);
						if ($row_access['selected_value'] == 'true' && ! $form->isSubmitted()) {
							$element->setChecked(true);
						}
					} else {
						if ($rowkeys['selected_value'] == 'true' && ! $form->isSubmitted()) {
							$element->setChecked(true);
						}
					}
					if ($hide_element) {
						$element->freeze();
					}
					$group[] = $element;
				}
				$form->addGroup($group, $row['variable'], get_lang($row['comment']), '<br />'."\n");
				break;
			case "link" :
				$form->addElement('static', null, get_lang($row['comment']), get_lang('CurrentValue').' : '.$row['selected_value'], $hideme);
				break;
			/*
			 * To populate its list of options, the select type dynamically calls a function that must be called select_ + the name of the variable being displayed
			 * The functions being called must be added to the file settings.lib.php
			 */
			case "select":
				$form->addElement('select', $row['variable'], get_lang($row['comment']), call_user_func('select_'.$row['variable']), $hideme);
				$default_values[$row['variable']] = $row['selected_value'];
				break;
			/*
			 * Used to display custom values for the gradebook score display
			 */
                        /* this configuration is moved now inside gradebook tool
			case "gradebook_score_display_custom":
				if(api_get_setting('gradebook_score_display_custom', 'my_display_custom') == 'false') {
					$form->addElement('static', null, null, get_lang('GradebookActivateScoreDisplayCustom'));
				} else {
					// Get score displays
					require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/scoredisplay.class.php';
					$scoredisplay = ScoreDisplay::instance();
					$customdisplays = $scoredisplay->get_custom_score_display_settings();
					$nr_items =(count($customdisplays)!='0')?count($customdisplays):'1';
					$form->addElement('hidden', 'gradebook_score_display_custom_values_maxvalue', '100');
					$form->addElement('hidden', 'gradebook_score_display_custom_values_minvalue', '0');
					$form->addElement('static', null, null, get_lang('ScoreInfo'));
					$scorenull[]= $form->CreateElement('static', null, null, get_lang('Between'));
					$form->setDefaults(array (
						'beginscore' => '0'
					));
					$scorenull[]= $form->CreateElement('text', 'beginscore', null, array (
						'size' => 5,
						'maxlength' => 5,
						'disabled' => 'disabled'
					));
					$scorenull[]= $form->CreateElement('static', null, null, ' %');
					$form->addGroup($scorenull, '', '', ' ');
					for ($counter= 1; $counter <= 20; $counter++) {
						$renderer = $form->defaultRenderer();
						$elementTemplateTwoLabel =
						'<div id=' . $counter . ' style="display: '.(($counter<=$nr_items)?'inline':'none').';">
						<p><!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}
						<div class="formw"><!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->	<b>'.get_lang('And').'</b>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp{element} % =';

						$elementTemplateTwoLabel2 =
						'<!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->&nbsp{element}
						<a href="javascript:minItem(' . ($counter) . ')"><img style="display: '.(($counter>=$nr_items && $counter!=1)?'inline':'none').';" id="min-' . $counter . '" src="../img/gradebook_remove.gif" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'"></img></a>
						<a href="javascript:plusItem(' . ($counter+1) . ')"><img style="display: '.(($counter>=$nr_items)?'inline':'none').';" id="plus-' . ($counter+1) . '" src="../img/gradebook_add.gif" alt="'.get_lang('Add').'" title="'.get_lang('Add').'"></img></a>
						</div></p></div>';

						$scorebetw= array ();
						$form->addElement('text', 'gradebook_score_display_custom_values_endscore[' . $counter . ']', null, array (
							'size' => 5,
							'maxlength' => 5,
							'id' => 'txta-'.$counter
						));
						$form->addElement('text', 'gradebook_score_display_custom_values_displaytext[' . $counter . ']', null,array (
							'size' => 40,
							'maxlength' => 40,
							'id' => 'txtb-'.$counter
						));
						$renderer->setElementTemplate($elementTemplateTwoLabel,'gradebook_score_display_custom_values_endscore[' . $counter . ']');
						$renderer->setElementTemplate($elementTemplateTwoLabel2,'gradebook_score_display_custom_values_displaytext[' . $counter . ']');
						$form->addRule('gradebook_score_display_custom_values_endscore[' . $counter . ']', get_lang('OnlyNumbers'), 'numeric');
						$form->addRule(array ('gradebook_score_display_custom_values_endscore[' . $counter . ']', 'gradebook_score_display_custom_values_maxvalue'), get_lang('Over100'), 'compare', '<=');
						$form->addRule(array ('gradebook_score_display_custom_values_endscore[' . $counter . ']', 'gradebook_score_display_custom_values_minvalue'), get_lang('UnderMin'), 'compare', '>');
						if($customdisplays[$counter-1]) {
							$default_values['gradebook_score_display_custom_values_endscore['.$counter.']'] = $customdisplays[$counter-1]['score'];
							$default_values['gradebook_score_display_custom_values_displaytext['.$counter.']'] = $customdisplays[$counter-1]['display'];
						}
					}
				}
				break;

                         */
		}
	}

	$form->addElement('html','<div style="text-align: right; clear: both;">');
	$form->addElement('style_submit_button', null,get_lang('SaveSettings'), 'class="save"');
	$form->addElement('html','</div>');

	$form->setDefaults($default_values);
	if ($form->validate()) {
		$values = $form->exportValues();

		// set true for allow_message_tool variable if social tool is actived
		if ($values['allow_social_tool'] == 'true') {
			$values['allow_message_tool'] = 'true';
		}

		// the first step is to set all the variables that have type=checkbox of the category
		// to false as the checkbox that is unchecked is not in the $_POST data and can
		// therefore not be set to false.
		// This, however, also means that if the process breaks on the third of five checkboxes, the others
		// will be set to false.
		$r = api_set_settings_category($my_category,'false',$_configuration['access_url'],array('checkbox','radio'));
		//$sql = "UPDATE $table_settings_current SET selected_value='false' WHERE category='$my_category' AND type='checkbox'";
		//$result = Database::query($sql);
		// Save the settings
		$keys = array();
		//$gradebook_score_display_custom_values = array();
		foreach ($values as $key => $value) {
			// Treat gradebook values in separate function
			//if(strpos($key, 'gradebook_score_display_custom_values') === false) {
				if (!is_array($value)) {
					//$sql = "UPDATE $table_settings_current SET selected_value='".Database::escape_string($value)."' WHERE variable='$key'";
					//$result = Database::query($sql);

					if (api_get_setting($key) != $value) $keys[] = $key;

					$result = api_set_setting($key,$value,null,null,$_configuration['access_url']);

				} else {

					$sql = "SELECT subkey FROM $table_settings_current WHERE variable = '$key'";
					$res = Database::query($sql);
					$subkeys = array();
					while ($row_subkeys = Database::fetch_array($res)) {
						// if subkey is changed
						if ( (isset($value[$row_subkeys['subkey']]) && api_get_setting($key,$row_subkeys['subkey']) == 'false') ||
							 (!isset($value[$row_subkeys['subkey']]) && api_get_setting($key,$row_subkeys['subkey']) == 'true')) {
							$keys[] = $key;
							break;
						}
					}

					foreach ($value as $subkey => $subvalue)
					{

						//$sql = "UPDATE $table_settings_current SET selected_value='true' WHERE variable='$key' AND subkey = '$subkey'";
						//$result = Database::query($sql);

						$result = api_set_setting($key,'true',$subkey,null,$_configuration['access_url']);

					}
				}
			//} else {
			//	$gradebook_score_display_custom_values[$key] = $value;
			//}
		}

                /*
		if(count($gradebook_score_display_custom_values) > 0) {
			update_gradebook_score_display_custom_values($gradebook_score_display_custom_values);
		}
                 */

		// add event configuration settings category to system log
		$time = time();
		$user_id = api_get_user_id();
		$category = $_GET['category'];
		event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_CATEGORY, $category, $time, $user_id);


		// add event configuration settings variable to system log
		if (is_array($keys) && count($keys) > 0) {
			foreach($keys as $variable) {
					event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_VARIABLE, $variable, $time, $user_id);
			}
		}

		header('Location: settings.php?action=stored&category='.Security::remove_XSS($_GET['category']));
		exit;
	}
}

// including the header (banner)
Display :: display_header($tool_name);
//api_display_tool_title($tool_name);

// displaying the message that the settings have been stored
if (!empty($_GET['action']) && $_GET['action'] == 'stored') {
	Display :: display_confirmation_message(get_lang('SettingsStored'));
}

// the action images
$action_images['platform'] 		= 'logo.gif';
$action_images['course'] 		= 'course.gif';
$action_images['tools'] 		= 'reference.gif';
$action_images['user'] 			= 'students.gif';
$action_images['gradebook']		= 'gradebook_eval_not_empty.gif';
$action_images['ldap'] 			= 'loginmanager.gif';
$action_images['security'] 		= 'passwordprotected.gif';
$action_images['languages']		= 'forum.gif';
$action_images['tuning'] 		= 'tuning.gif';
$action_images['plugins'] 		= 'plugin.gif';
$action_images['stylesheets'] 	= 'theme.gif';
$action_images['templates'] 	= 'template.gif';
$action_images['search']        = 'search.gif';
$action_images['editor']		= 'html.png';
$action_images['timezones']		= 'timezones.png';

// grabbing the categories
//$selectcategories = "SELECT DISTINCT category FROM ".$table_settings_current." WHERE category NOT IN ('stylesheets','Plugins')";
//$resultcategories = Database::query($selectcategories);
$resultcategories = api_get_settings_categories(array('stylesheets','Plugins', 'Templates', 'Search'));
echo "\n<div class=\"actions\">";
//while ($row = Database::fetch_array($resultcategories))
foreach($resultcategories as $row) {
	echo "\n\t<a href=\"".api_get_self()."?category=".$row['category']."\">".Display::return_icon($action_images[strtolower($row['category'])], api_ucfirst(get_lang($row['category']))).api_ucfirst(get_lang($row['category']))."</a>";
}
echo "\n\t<a href=\"".api_get_self()."?category=Plugins\">".Display::return_icon($action_images['plugins'], api_ucfirst(get_lang('Plugins'))).api_ucfirst(get_lang('Plugins'))."</a>";
echo "\n\t<a href=\"".api_get_self()."?category=stylesheets\">".Display::return_icon($action_images['stylesheets'], api_ucfirst(get_lang('Stylesheets'))).api_ucfirst(get_lang('Stylesheets'))."</a>";
echo "\n\t<a href=\"".api_get_self()."?category=Templates\">".Display::return_icon($action_images['templates'], api_ucfirst(get_lang('Templates'))).api_ucfirst(get_lang('Templates'))."</a>";
echo "\n\t<a href=\"".api_get_self()."?category=Search\">".Display::return_icon($action_images['search'], api_ucfirst(get_lang('Search'))).api_ucfirst(get_lang('Search'))."</a>";
echo "\n</div>";

if (!empty($_GET['category'])) {
	switch ($_GET['category']) {
		// displaying the extensions: plugins
		// this will be available to all the sites (access_urls)
		case 'Plugins' :

			if (isset($_POST['submit_dashboard_plugins'])) {
				$affected_rows = DashboardManager::store_dashboard_plugins($_POST);
				if ($affected_rows) {
					// add event to system log
					$time = time();
					$user_id = api_get_user_id();
					$category = $_GET['category'];
					event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_CATEGORY, $category, $time, $user_id);
					Display :: display_confirmation_message(get_lang('DashboardPluginsHaveBeenUpdatedSucesslly'));
				}
			}

			handle_plugins();
			DashboardManager::handle_dashboard_plugins();

			break;
			// displaying the extensions: Stylesheets
		case 'stylesheets' :
			handle_stylesheets();
			break;
        case 'Search' :
            handle_search();
            break;
		case 'Templates' :
			handle_templates();
			break;
		default :
			$form->display();
	}
}

/*
		FOOTER
*/
Display :: display_footer();
