<?php // $Id: settings.php 19832 2009-04-17 14:05:16Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University
	Copyright (c) Patrick Cool, Ghent University
	Copyright (c) Julio Montoya, Dokeos
	Copyright (c) Roan Embrechts, Vrije Universiteit Brussel
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
* With this tool you can easily adjust non critical configuration settings.
* Non critical means that changing them will not result in a broken campus.
*
* @author Patrick Cool
* @since Dokeos 1.6
* @author Julio Montoya - Multiple URL site
* @package dokeos.admin
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = 'admin';
// resetting the course id
$cidReset=true;
// including some necessary dokeos files
include_once ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
require_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
$_SESSION['this_section'] = $this_section;

// Access restrictions
api_protect_admin_script();

// Submit Stylesheets
if (isset($_POST['submit_stylesheets']))
{
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

// setting for templates
$fck_attribute['ToolbarSet'] = "AdminTemplates";
$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '400';

// Build the form
if (!empty($_GET['category']) and !in_array($_GET['category'], array('Plugins', 'stylesheets', 'Search')))
{
	$form = new FormValidator('settings', 'post', 'settings.php?category='.$_GET['category']);
	$renderer = & $form->defaultRenderer();
	$renderer->setHeaderTemplate('<div class="sectiontitle">{header}</div>'."\n");
	$renderer->setElementTemplate('<div class="sectioncomment">{label}</div>'."\n".'<div class="sectionvalue">{element}</div>'."\n");
	$my_category = mysql_real_escape_string($_GET['category']);
	
	$sqlcountsettings = "SELECT COUNT(*) FROM $table_settings_current WHERE category='".$my_category."' AND type<>'checkbox'";
	$resultcountsettings = api_sql_query($sqlcountsettings, __FILE__, __LINE__);
	$countsetting = mysql_fetch_array($resultcountsettings);
	 
	if ($_configuration['access_url']==1)
	{
		$settings = api_get_settings($my_category,'group',$_configuration['access_url']);
	}
	else
	{
		$url_info = api_get_access_url($_configuration['access_url']);
		if ($url_info['active']==1)
		{
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
	//$resultsettings = api_sql_query($sqlsettings, __FILE__, __LINE__);
	//while ($row = mysql_fetch_array($resultsettings))
	$default_values = array();
	foreach($settings as $row) {			
		($countsetting['0']%10) < 5 ?$b=$countsetting['0']-10:$b=$countsetting['0'];		
		if ($i % 10 == 0 and $i<$b){
			if ($_GET['category'] <> "Languages"){
				$form->addElement('html','<div align="right">');
				$form->addElement('style_submit_button', null,get_lang('SaveSettings'), 'class="save"');
				$form->addElement('html','</div>');
			}		
		}
		$i++;

		$form->addElement('header', null, get_lang($row['title']));
		
		if ($row['access_url_changeable']=='1' && $_configuration['multiple_access_urls']==true) {
			$form->addElement('html', '<div style="float:right;">'.Display::return_icon('shared_setting.png',get_lang('SharedSettingIconComment')).'</div>');						
		}	
		
		$hideme=array();
		$hide_element=false;
		if ($_configuration['access_url']!=1)
		{
			if ($row['access_url_changeable']==0)
			{
				//we hide the element in other cases (checkbox, radiobutton) we 'freeze' the element
				$hide_element=true;
				$hideme=array('disabled');
			}
			elseif($url_info['active']==1)
			{
				// we show the elements 
				if (empty($row['variable']))
					$row['variable']=0;
				if (empty($row['subkey']))
					$row['subkey']=0;
				if (empty($row['category']))
					$row['category']=0;
					
				if (is_array ($settings_by_access_list[ $row['variable'] ] [ $row['subkey'] ]	[ $row['category'] ]))
				{
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
					$default_values[$row['variable']] = $row['selected_value'];	
				} else {
					$form->addElement('text', $row['variable'], get_lang($row['comment']),$hideme);
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
				
				$result = api_sql_query($sql, __FILE__, __LINE__);
				$group = array ();	
				while ($rowkeys = Database::fetch_array($result)) {
					$element = & $form->createElement('checkbox', $rowkeys['subkey'], '', get_lang($rowkeys['subkeytext']));
					if ($row['access_url_changeable']==1) {						
						//2. we look into the DB if there is a setting for a specific access_url
						$access_url = $_configuration['access_url'];
						if(empty($access_url )) $access_url =1;
						$sql = "SELECT selected_value FROM settings_current WHERE variable='".$rowkeys['variable']."' AND subkey='".$rowkeys['subkey']."'  AND  subkeytext='".$rowkeys['subkeytext']."' AND access_url =  $access_url";						
						$result_access = api_sql_query($sql, __FILE__, __LINE__);
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
				$form->addElement('static', null, get_lang($row['comment']), get_lang('CurrentValue').' : '.$row['selected_value'],$hideme);
		}
	}
	if ($_GET['category'] <> "Languages"){
		$form->addElement('html','<div align="right">');
		$form->addElement('style_submit_button', null,get_lang('SaveSettings'), 'class="save"');
		$form->addElement('html','</div>');
	}
	$form->setDefaults($default_values);
	if ($form->validate())
	{
		$values = $form->exportValues();
		// the first step is to set all the variables that have type=checkbox of the category
		// to false as the checkbox that is unchecked is not in the $_POST data and can
		// therefore not be set to false.
		// This, however, also means that if the process breaks on the third of five checkboxes, the others
		// will be set to false.
		$r = api_set_settings_category($my_category,'false',$_configuration['access_url']);
		//$sql = "UPDATE $table_settings_current SET selected_value='false' WHERE category='$my_category' AND type='checkbox'";
		//$result = api_sql_query($sql, __FILE__, __LINE__);
		// Save the settings
		foreach ($values as $key => $value)
		{
			if (!is_array($value))
			{
				//$sql = "UPDATE $table_settings_current SET selected_value='".mysql_real_escape_string($value)."' WHERE variable='$key'";
				//$result = api_sql_query($sql, __FILE__, __LINE__);
				$result = api_set_setting($key,$value,null,null,$_configuration['access_url']);
			}
			else
			{
				foreach ($value as $subkey => $subvalue)
				{
					//$sql = "UPDATE $table_settings_current SET selected_value='true' WHERE variable='$key' AND subkey = '$subkey'";
					//$result = api_sql_query($sql, __FILE__, __LINE__);
					$result = api_set_setting($key,'true',$subkey,null,$_configuration['access_url']);				
				}
			}
		}
		header('Location: settings.php?action=stored&category='.$_GET['category']);
		exit;
	}
}

// including the header (banner)
Display :: display_header($tool_name);
//api_display_tool_title($tool_name);

// displaying the message that the settings have been stored
if (!empty($_GET['action']) && $_GET['action'] == "stored")
{
	Display :: display_normal_message($SettingsStored);
}

// the action images
$action_images['platform'] 		= 'dokeos.gif';
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


// grabbing the categories
//$selectcategories = "SELECT DISTINCT category FROM ".$table_settings_current." WHERE category NOT IN ('stylesheets','Plugins')";
//$resultcategories = api_sql_query($selectcategories, __FILE__, __LINE__);
$resultcategories = api_get_settings_categories(array('stylesheets','Plugins', 'Templates', 'Search'));
echo "\n<div class=\"actions\">";
//while ($row = mysql_fetch_array($resultcategories))
foreach($resultcategories as $row)
{
	echo "\n\t<a href=\"".api_get_self()."?category=".$row['category']."\">".Display::return_icon($action_images[strtolower($row['category'])], ucfirst(get_lang($row['category']))).ucfirst(get_lang($row['category']))."</a>";
}
echo "\n\t<a href=\"".api_get_self()."?category=Plugins\">".Display::return_icon($action_images['plugins'], ucfirst(get_lang('Plugins'))).ucfirst(get_lang('Plugins'))."</a>";
echo "\n\t<a href=\"".api_get_self()."?category=stylesheets\">".Display::return_icon($action_images['stylesheets'], ucfirst(get_lang('Stylesheets'))).ucfirst(get_lang('Stylesheets'))."</a>";
echo "\n\t<a href=\"".api_get_self()."?category=Templates\">".Display::return_icon($action_images['templates'], ucfirst(get_lang('Templates'))).ucfirst(get_lang('Templates'))."</a>";
echo "\n\t<a href=\"".api_get_self()."?category=Search\">".Display::return_icon($action_images['search'], ucfirst(get_lang('Search'))).ucfirst(get_lang('Search'))."</a>";
echo "\n</div>";

if (isset ($_GET['category']))
{
	switch ($_GET['category'])
	{
		// displaying the extensions: plugins
		// this will be available to all the sites (access_urls)
		case 'Plugins' :
			handle_plugins();
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
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();



/*
==============================================================================
		FUNCTIONS
==============================================================================
*/
/**
 * The function that retrieves all the possible settings for a certain config setting
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function get_settings_options($var)
{
	$table_settings_options = Database :: get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
	$sql = "SELECT * FROM $table_settings_options WHERE variable='$var'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_array($result))
	{
		$temp_array = array ('value' => $row['value'], 'display_text' => $row['display_text']);
		$settings_options_array[] = $temp_array;
	}
	return $settings_options_array;
}

/**
 * This function allows easy activating and inactivating of plugins
 * @todo: a similar function needs to be written to activate or inactivate additional tools.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function handle_plugins()
{
	global $SettingsStored;
	$userplugins = array();
	$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

	if (isset($_POST['submit_plugins']))
	{
		store_plugins();
		Display :: display_normal_message($SettingsStored);
	}

	echo get_lang('AvailablePlugins')."<br/><br/>";

	/* We scan the plugin directory. Each folder is a potential plugin. */
	$pluginpath = api_get_path(SYS_PLUGIN_PATH);

	$handle = @opendir($pluginpath);
	while (false !== ($file = readdir($handle)))
	{
		if ($file <> '.' AND $file <> '..' AND is_dir(api_get_path(SYS_PLUGIN_PATH).$file))
		{
			$possibleplugins[] = $file;
		}
	}
	@closedir($handle);

	/* 	for each of the possible plugin dirs we check if a file plugin.php (that contains all the needed information about this plugin)
	 	can be found in the dir.
		this plugin.php file looks like
		$plugin_info['title']='The title of the plugin'; //
		$plugin_info['comment']="Some comment about the plugin";
		$plugin_info['location']=array("loginpage_menu", "campushomepage_menu","banner"); // the possible locations where the plugins can be used
		$plugin_info['version']='0.1 alpha'; // The version number of the plugin
		$plugin_info['author']='Patrick Cool'; // The author of the plugin
	*/
	echo '<form name="plugins" method="post" action="'.api_get_self().'?category='.$_GET['category'].'">';
	echo "<table class=\"data_table\">\n";
	echo "\t<tr>\n";
	echo "\t\t<th>\n";
	echo get_lang('Plugin');
	echo "\t\t</th>\n";
	echo "\t\t<th>\n";
	echo get_lang('LoginPageMainArea');
	echo "\t\t</th>\n";
	echo "\t\t<th>\n";
	echo get_lang('LoginPageMenu');
	echo "\t\t</th>\n";
	echo "\t\t<th>\n";
	echo get_lang('CampusHomepageMainArea');
	echo "\t\t</th>\n";
	echo "\t\t<th>\n";
	echo get_lang('CampusHomepageMenu');
	echo "\t\t</th>\n";
	echo "\t\t<th>\n";
	echo get_lang('MyCoursesMainArea');
	echo "\t\t</th>\n";
	echo "\t\t<th>\n";
	echo get_lang('MyCoursesMenu');
	echo "\t\t</th>\n";
	echo "\t\t<th>\n";
	echo get_lang('Header');
	echo "\t\t</th>\n";
	echo "\t\t<th>\n";
	echo get_lang('Footer');
	echo "\t\t</th>\n";
	echo "\t</tr>\n";

	/* We retrieve all the active plugins. */
	//$sql = "SELECT * FROM $table_settings_current WHERE category='Plugins'";
	//$result = api_sql_query($sql);
	$result = api_get_settings('Plugins');
	//while ($row = mysql_fetch_array($result))
	foreach($result as $row)
	{
		$usedplugins[$row['variable']][] = $row['selected_value'];
	}

	/* We display all the possible plugins and the checkboxes */
	foreach ($possibleplugins as $testplugin)
	{
		$plugin_info_file = api_get_path(SYS_PLUGIN_PATH).$testplugin."/plugin.php";
		if (file_exists($plugin_info_file))
		{
			$plugin_info = array();
			include ($plugin_info_file);

			echo "\t<tr>\n";
			echo "\t\t<td>\n";
			foreach ($plugin_info as $key => $value)
			{
				if ($key <> 'location')
				{
					if ($key == 'title')
					{
						$value = '<strong>'.$value.'</strong>';
					}
					echo get_lang(ucwords($key)).': '.$value.'<br />';
				}
			}
			if (file_exists(api_get_path(SYS_PLUGIN_PATH).$testplugin.'/readme.txt'))
			{
				echo "<a href='".api_get_path(WEB_PLUGIN_PATH).$testplugin."/readme.txt'>readme.txt</a>";
			}
			echo "\t\t</td>\n";

			// column: LoginPageMainArea
			if(empty($usedplugins))
			{
				$usedplugins = array();
			}
			display_plugin_cell('loginpage_main', $plugin_info, $testplugin, $usedplugins);
			display_plugin_cell('loginpage_menu', $plugin_info, $testplugin, $usedplugins);
			display_plugin_cell('campushomepage_main', $plugin_info, $testplugin, $usedplugins);
			display_plugin_cell('campushomepage_menu', $plugin_info, $testplugin, $usedplugins);
			display_plugin_cell('mycourses_main', $plugin_info, $testplugin, $usedplugins);
			display_plugin_cell('mycourses_menu', $plugin_info, $testplugin, $usedplugins);
			display_plugin_cell('header', $plugin_info, $testplugin, $usedplugins);
			display_plugin_cell('footer', $plugin_info, $testplugin, $usedplugins);
			echo "\t</tr>\n";
				}
				}
	echo '</table>';

	echo '<button class="save" type="submit" name="submit_plugins">'.get_lang('EnablePlugins').'</button></form>';
			}


function display_plugin_cell($location, $plugin_info, $current_plugin, $active_plugins)
{
	echo "\t\t<td align=\"center\">\n";
	if (in_array($location, $plugin_info['location']))
	{
		if (isset($active_plugins[$location]) && is_array($active_plugins[$location]) 
			&& in_array($current_plugin, $active_plugins[$location]))
		{
			$checked = "checked";
		}
		else
		{
			$checked = '';
		}
		echo '<input type="checkbox" name="'.$current_plugin.'-'.$location.'" value="true" '.$checked.'/>';
	}
	echo "\t\t</td>\n";
}

/**
 * This function allows the platform admin to choose the default stylesheet
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function handle_stylesheets()
{
	global $_configuration;
	// Current style
	$currentstyle = api_get_setting('stylesheets');
	$is_style_changeable=false;
	
	
	if ($_configuration['access_url']!=1)
	{
		$style_info = api_get_settings('stylesheets','',1,0);
		$url_info = api_get_access_url($_configuration['access_url']);	 
		if ($style_info[0]['access_url_changeable']==1 && $url_info['active']==1)
		{
			$is_style_changeable=true;	
			echo '<div class="actions" id="stylesheetuploadlink">';	
			Display::display_icon('theme_add.gif');	
			echo '<a href="" onclick="document.getElementById(\'newstylesheetform\').style.display = \'block\'; document.getElementById(\'stylesheetuploadlink\').style.display = \'none\';return false; ">'.get_lang('UploadNewStylesheet').'</a>';
			echo '</div>';
		} 
	}
	else
	{
		$is_style_changeable=true;
		echo '<div class="actions" id="stylesheetuploadlink">';	
		Display::display_icon('theme_add.gif');
		echo '<a href="" onclick="document.getElementById(\'newstylesheetform\').style.display = \'block\'; document.getElementById(\'stylesheetuploadlink\').style.display = \'none\';return false; ">'.get_lang('UploadNewStylesheet').'</a>';
		echo '</div>';
	}	
		
	$form = new FormValidator('stylesheet_upload','post','settings.php?category=stylesheets&showuploadform=true');
	$form->addElement('text','name_stylesheet',get_lang('NameStylesheet'),array('size' => '40', 'maxlength' => '40'));	
	$form->addRule('name_stylesheet', get_lang('ThisFieldIsRequired'), 'required');
	$form->addElement('file', 'new_stylesheet', get_lang('UploadNewStylesheet'));
	$allowed_file_types = array ('css');
	$form->addRule('new_stylesheet', get_lang('InvalidExtension').' ('.implode(',', $allowed_file_types).')', 'filetype', $allowed_file_types);
	$form->addRule('new_stylesheet', get_lang('ThisFieldIsRequired'), 'required');
	$form->addElement('style_submit_button', 'stylesheet_upload', get_lang('Ok'), array('class'=>'save'));
	if( $form->validate() AND is_writable(api_get_path(SYS_CODE_PATH).'css/'))
	{
		$values = $form->exportValues();
		$picture_element = & $form->getElement('new_stylesheet');
		$picture = $picture_element->getValue();
		upload_stylesheet($values, $picture);
		Display::display_confirmation_message(get_lang('StylesheetAdded'));
	}
	else 
	{
		if (!is_writable(api_get_path(SYS_CODE_PATH).'css/'))
		{
			Display::display_error_message(api_get_path(SYS_CODE_PATH).'css/'.get_lang('IsNotWritable'));
		}
		else 
		{
			if ($_GET['showuploadform'] == 'true')
			{
				echo '<div id="newstylesheetform">';
			}
			else 
			{
				echo '<div id="newstylesheetform" style="display: none;">';
			}
				// uploading a new stylesheet
			if ($_configuration['access_url']==1)
			{
				$form->display();	
			}
			else
			{
				if ($is_style_changeable)
				{
					$form->display();				
				}
			}
			echo '</div>';
		}
	}

	// Preview of the stylesheet
	echo '<div><iframe src="style_preview.php" width="100%" height="300" name="preview"></iframe></div>';

	echo '<form name="stylesheets" method="post" action="'.api_get_self().'?category='.$_GET['category'].'">';
	if ($handle = @opendir(api_get_path(SYS_PATH).'main/css/'))
	{
		$counter=1;
		while (false !== ($style_dir = readdir($handle)))
		{
			if(substr($style_dir,0,1)=='.') //skip dirs starting with a '.'
			{
				continue;
			}
			$dirpath = api_get_path(SYS_PATH).'main/css/'.$style_dir;
			if (is_dir($dirpath))
			{
				if ($style_dir != '.' && $style_dir != '..')
				{
					if ($currentstyle == $style_dir OR ($style_dir == 'dokeos_classic' AND !$currentstyle))
					{
						$selected = 'checked="checked"';
					}
					else
					{
						$selected = '';
					}
					$show_name=ucwords(str_replace('_',' ', $style_dir));
					
					if ($is_style_changeable)
					{					
						echo "<input type=\"radio\" name=\"style\" value=\"".$style_dir."\" ".$selected." onClick=\"parent.preview.location='style_preview.php?style=".$style_dir."';\"/>";
						echo '<a href="style_preview.php?style='.$style_dir.'" target="preview">'.$show_name.'</a>';
					}
					else
						echo '<a href="style_preview.php?style='.$style_dir.'" target="preview">'.$show_name.'</a>';
					//echo '<div id="Layer'.$counter.'" style="position:relative; width:687px; z-index:2; visibility: hidden;">';
					//echo '<a href="#" onClick="MM_showHideLayers(\'Layer'.$counter.'\',\'\',\'hide\')">'.get_lang('Close').'</a>';
					//echo '<iframe src="style_preview.php?style='.$file.'" width="100%" style="float:right;"></iframe></div>';
					echo "<br />\n";
					$counter++;
				}
			}
		}
		@closedir($handle);
	}
	if ($is_style_changeable)
	{	
		echo '<button class="save" type="submit" name="submit_stylesheets"> '.get_lang('SaveSettings').' </button></form>';
	}
}

/**
 * creates the folder (if needed) and uploads the stylesheet in it
 *
 * @param array $values the values of the form
 * @param array $picture the values of the uploaded file
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008
 * @since Dokeos 1.8.5
 */
function upload_stylesheet($values,$picture)
{
	// valid name for the stylesheet folder
	$style_name = ereg_replace("[^A-Za-z0-9]", "", $values['name_stylesheet'] );
	
	// create the folder if needed
	if(!is_dir(api_get_path(SYS_CODE_PATH).'css/'.$style_name.'/'))
	{
		if(mkdir(api_get_path(SYS_CODE_PATH).'css/'.$style_name.'/'))
		{
			$perm = api_get_setting('permissions_for_new_directories');
			$perm = octdec(!empty($perm)?$perm:'0770');
			chmod(api_get_path(SYS_CODE_PATH).'css/'.$style_name.'/', $perm);
		}		
	}
	
	// move the file in the folder
	move_uploaded_file($picture['tmp_name'], api_get_path(SYS_CODE_PATH).'css/'.$style_name.'/'.$picture['name']);
}

/**
 * This function allows easy activating and inactivating of plugins
 * @todo: a similar function needs to be written to activate or inactivate additional tools.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function store_plugins()
{
	$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	global $_configuration;

	// Step 1 : we remove all the plugins
	//$sql = "DELETE FROM $table_settings_current WHERE category='Plugins'";
	//api_sql_query($sql, __LINE__, __FILE__);
	$r = api_delete_category_settings('Plugins',$_configuration['access_url']);

	// step 2: looping through all the post values we only store these which are really a valid plugin location.
	foreach ($_POST as $form_name => $formvalue)
	{
		$form_name_elements = explode("-", $form_name);
		if (is_valid_plugin_location($form_name_elements[1]))
		{
			//$sql = "INSERT into $table_settings_current (variable,category,selected_value) VALUES ('".$form_name_elements['1']."','Plugins','".$form_name_elements['0']."')";
			//api_sql_query($sql, __LINE__, __FILE__);
			api_add_setting($form_name_elements['0'],$form_name_elements['1'],$form_name_elements['0'],null,'Plugins',$form_name_elements['0'],null,null,null,$_configuration['access_url'],1);			
		}
	}
}

/**
 * Check if the post information is really a valid plugin location.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function is_valid_plugin_location($location)
{
	$valid_locations=array('loginpage_main', 'loginpage_menu', 'campushomepage_main', 'campushomepage_menu', 'mycourses_main', 'mycourses_menu','header', 'footer');
	if (in_array($location, $valid_locations))
	{
		return true;
	}
	else
	{
		return false;
	}
}


/**
 * This function allows the platform admin to choose which should be the default stylesheet
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function store_stylesheets()
{
	global $_configuration;
	// Database Table Definitions
	$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

	// Insert the stylesheet
	$style = Database::escape_string($_POST['style']);
	if (is_style($style))
	{
		/*
		$sql = 'UPDATE '.$table_settings_current.' SET
				selected_value = "'.$style.'"
				WHERE variable = "stylesheets"
				AND category = "stylesheets"';

		api_sql_query($sql, __LINE__, __FILE__);
		*/
		
		api_set_setting('stylesheets',$style,null,'stylesheets',$_configuration['access_url']);
	}

	return true;
}

/**
 * This function checks if the given style is a recognize style that exists in the css directory as
 * a standalone directory.
 * @param	string	Style
 * @return	bool	True if this style is recognized, false otherwise
 */
function is_style($style)
{
	$dir = api_get_path(SYS_PATH).'main/css/';
	$dirs = scandir($dir);
	$style = str_replace(array('/','\\'),array('',''),$style); //avoid slashes or backslashes
	if (in_array($style,$dirs) && is_dir($dir.$style))
	{
		return true;
	}
	return false;
}

/**
 * Search options
 * TODO: support for multiple site. aka $_configuration['access_url'] == 1
 * @author Marco Villegas <marvil07@gmail.com>
 */
function handle_search() {
    global $SettingsStored, $_configuration;

    $search_enabled = api_get_setting('search_enabled');
    $settings = api_get_settings('Search');

    if ($search_enabled !== 'true' || count($settings) < 1) {
        Display::display_error_message(get_lang('SearchFeatureNotEnabledComment'));
        return;
    }

    require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';

    $form = new FormValidator('search-options', 'post', api_get_self().'?category=Search');
    $renderer = & $form->defaultRenderer();
    $renderer->setHeaderTemplate('<div class="sectiontitle">{header}</div>'."\n");
    $renderer->setElementTemplate('<div class="sectioncomment">{label}</div>'."\n".'<div class="sectionvalue">{element}</div>'."\n");

    //search_show_unlinked_results
    $form->addElement('header', null, get_lang('SearchShowUnlinkedResultsTitle'));
    $form->addElement('label', null, get_lang('SearchShowUnlinkedResultsComment'));
    $values = get_settings_options('search_show_unlinked_results');
    $group = array ();
    foreach ($values as $key => $value) {
        $element = & $form->createElement('radio', 'search_show_unlinked_results', '', get_lang($value['display_text']), $value['value']);
        $group[] = $element;
    }
    $form->addGroup($group, 'search_show_unlinked_results', get_lang('SearchShowUnlinkedResultsComment'), '<br />', false);
    $default_values['search_show_unlinked_results'] = api_get_setting('search_show_unlinked_results');

    //search_prefilter_prefix
    $form->addElement('header', null, get_lang('SearchPrefilterPrefix'));
    $form->addElement('label', null, get_lang('SearchPrefilterPrefixComment'));
    $specific_fields = get_specific_field_list();
    $sf_values = array();
    foreach ($specific_fields as $sf) {
       $sf_values[$sf['code']] = $sf['name'];
    }
    $group = array ();
    $form->addElement('select', 'search_prefilter_prefix', get_lang('SearchPrefilterPrefix'), $sf_values, '');
    $default_values['search_prefilter_prefix'] = api_get_setting('search_prefilter_prefix');

    //$form->addRule('search_show_unlinked_results', get_lang('ThisFieldIsRequired'), 'required');
    $form->addElement('style_submit_button', 'search-options-save', get_lang('Ok'));
    $form->setDefaults($default_values);

    if( $form->validate()) {
        $formvalues = $form->exportValues();
        $r = api_set_settings_category('Search','false',$_configuration['access_url']);
        // Save the settings
        foreach ($formvalues as $key => $value)
        {
                $result = api_set_setting($key,$value,null,null);
        }

        Display :: display_normal_message($SettingsStored);
    } else {
        echo '<div id="search-options-form">';
        $form->display();
        echo '</div>';
    }
}

/**
 * wrapper for the templates
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function handle_templates()
{
	if ($_GET['action'] != 'add') {
		echo "\n<div class=\"actions\" style=\"margin-left:1px\" >";
		echo '<a href="settings.php?category=Templates&amp;action=add">'.Display::return_icon('template_add.gif', get_lang('AddTemplate')).get_lang('AddTemplate').'</a>';
		echo "\n</div>";
	}

	if ($_GET['action'] == 'add' OR ( $_GET['action'] == 'edit' AND is_numeric($_GET['id']))) {
		add_edit_template();
	} else {
		if ($_GET['action'] == 'delete' and is_numeric($_GET['id'])) {
			delete_template($_GET['id']);
		}
		display_templates();
	}
}

/**
 * Display a sortable table with all the templates that the platform administrator has defined.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function display_templates()
{
	$table = new SortableTable('templates', 'get_number_of_templates', 'get_template_data',1);
	$table->set_additional_parameters(array('category'=>$_GET['category']));
	$table->set_header(0, get_lang('Image'), true, array ('style' => 'width:101px;'));
	$table->set_header(1, get_lang('Title'));
	$table->set_header(2, get_lang('Actions'), false, array ('style' => 'width:30px;'));	
	$table->set_column_filter(2,'actions_filter');
	$table->set_column_filter(0,'image_filter');
	$table->display();	
}

/**
 * Get the number of templates that are defined by the platform admin. 
 *
 * @return integer 
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function get_number_of_templates()
{
	// Database table definition
	$table_system_template = Database :: get_main_table('system_template');
	
	// The sql statement
	$sql = "SELECT COUNT(id) AS total FROM $table_system_template";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$row = Database::fetch_array($result);
	
	// returning the number of templates
	return $row['total'];
}

/**
 * Get all the template data for the sortable table
 *
 * @param integer $from the start of the limit statement
 * @param integer $number_of_items the number of elements that have to be retrieved from the database
 * @param integer $column the column that is 
 * @param string $direction the sorting direction (ASC or DESC�
 * @return array 
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function get_template_data($from, $number_of_items, $column, $direction)
{
	// Database table definition
	$table_system_template = Database :: get_main_table('system_template');
	
	// the sql statement
	$sql = "SELECT image as col0, title as col1, id as col2 FROM $table_system_template";
	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";	
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = Database::fetch_array($result))
	{
		$return[]=$row;
	}
	
	// returning all the information for the sortable table
	return $return;
}

/**
 * display the edit and delete icons in the sortable table
 *
 * @param integer $id the id of the template
 * @return html code for the link to edit and delete the template
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function actions_filter($id)
{
	$return .= '<a href="settings.php?category=Templates&amp;action=edit&amp;id='.Security::remove_XSS($id).'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
	$return .= '<a href="settings.php?category=Templates&amp;action=delete&amp;id='.Security::remove_XSS($id).'" onclick="javascript:if(!confirm('."'".get_lang("ConfirmYourChoice")."'".')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
	return $return;
}

/**
 * Display the image of the template in the sortable table
 *
 * @param string $image the image
 * @return html code for the image
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function image_filter($image)
{
	if (!empty($image))
	{
		return '<img src="'.api_get_path(WEB_PATH).'home/default_platform_document/'.$image.'" alt="'.get_lang('TemplatePreview').'"/>';
	}
	else
	{
		return '<img src="'.api_get_path(WEB_PATH).'home/default_platform_document/noimage.gif" alt="'.get_lang('NoTemplatePreview').'"/>';
	}
}

/**
 * Add (or edit) a template. This function displays the form and also takes care of uploading the image and storing the information in the database
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function add_edit_template()
{
	// initiate the object
	$form = new FormValidator('template', 'post', 'settings.php?category=Templates&action='.$_GET['action'].'&id='.$_GET['id']);
	
	// settting the form elements: the header
	if ($_GET['action'] == 'add')
	{
		$title = get_lang('AddTemplate');
	}
	else 
	{
		$title = get_lang('EditTemplate');
	}
	$form->addElement('header', '', $title);
	
	// settting the form elements: the title of the template
	$form->add_textfield('title', get_lang('Title'), false);
	
	// settting the form elements: the content of the template (wysiwyg editor)
	$form->addElement('html_editor', 'template_text', get_lang('Text'));
	
	// settting the form elements: the form to upload an image to be used with the template
	$form->addElement('file','template_image',get_lang('Image'),'');
	
	// settting the form elements: a little bit information about the template image
	$form->addElement('static', 'file_comment', '', get_lang('TemplateImageComment100x70'));
	
	// getting all the information of the template when editing a template 
	if ($_GET['action'] == 'edit')
	{
		// Database table definition		
		$table_system_template = Database :: get_main_table('system_template');
		$sql = "SELECT * FROM $table_system_template WHERE id = '".Database::escape_string($_GET['id'])."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database::fetch_array($result);
		
		$defaults['template_id'] 	= $_GET['id'];
		$defaults['template_text'] 	= $row['content'];
		$defaults['title'] 			= $row['title'];
		
		// adding an extra field: a hidden field with the id of the template we are editing
		$form->addElement('hidden','template_id');
		
		// adding an extrra field: a preview of the image that is currently used
		if (!empty($row['image']))
		{		
			$form->addElement('static','template_image_preview', '', '<img src="'.api_get_path(WEB_PATH).'home/default_platform_document/'.$row['image'].'" alt="'.get_lang('TemplatePreview').'"/>');
		}
		else
		{
			$form->addElement('static','template_image_preview', '', '<img src="'.api_get_path(WEB_PATH).'home/default_platform_document/noimage.gif" alt="'.get_lang('NoTemplatePreview').'"/>');
		}
		
		// setting the information of the template that we are editing 
		$form->setDefaults($defaults);
	}	
		// settting the form elements: the submit button
	$form->addElement('style_submit_button' , 'submit', get_lang('Ok') ,'class="save"');
	
	// setting the rules: the required fields
	$form->addRule('title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');	
	$form->addRule('template_text', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');	
	
	// if the form validates (complies to all rules) we save the information, else we display the form again (with error message if needed)
	if( $form->validate() )
	{
		// exporting the values
		$values = $form->exportValues();
		
		// upload the file
		if (!empty($_FILES['template_image']['name']))
		{
			include_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
			$upload_ok = process_uploaded_file($_FILES['template_image']);
			
			if ($upload_ok)
			{
				// Try to add an extension to the file if it hasn't one
				$new_file_name = add_ext_on_mime(stripslashes($_FILES['template_image']['name']), $_FILES['template_image']['type']);	
				
				// upload dir
				$upload_dir = api_get_path(SYS_PATH).'home/default_platform_document/';
				
				// create dir if not exists
                if (!is_dir($upload_dir)) {
                    $perm = api_get_setting('permissions_for_new_directories');
                    $perm = octdec(!empty($perm)?$perm:'0770');
                	$res = @mkdir($upload_dir,$perm);
                }
				
				// resize image to max default and upload
				require_once (api_get_path(LIBRARY_PATH).'image.lib.php');
				$temp = new image($_FILES['template_image']['tmp_name']);	
				$picture_infos=getimagesize($_FILES['template_image']['tmp_name']);
				
				$max_width_for_picture = 100;
				
				if ($picture_infos[0]>$max_width_for_picture)
				{		
					$thumbwidth = $max_width_for_picture;
					if (empty($thumbwidth) or $thumbwidth==0) {
					  $thumbwidth=$max_width_for_picture;
					}
					$new_height = round(($thumbwidth/$picture_infos[0])*$picture_infos[1]);
					
					$temp->resize($thumbwidth,$new_height,0);
				}
				
				$type=$picture_infos[2];
						  
				switch (!empty($type))
				{
					case 2 : $temp->send_image('JPG',$upload_dir.$new_file_name);							 
							 break;
					case 3 : $temp->send_image('PNG',$upload_dir.$new_file_name);							
							 break;
					case 1 : $temp->send_image('GIF',$upload_dir.$new_file_name);							 
							 break;
				}
			}
	   }
	   
	   // store the information in the database (as insert or as update)
	   $table_system_template = Database :: get_main_table('system_template');
	   if ($_GET['action'] == 'add') {
		   	$sql = "INSERT INTO $table_system_template (title, content, image) VALUES ('".Database::escape_string($values['title'])."','".Database::escape_string($values['template_text'])."','".Database::escape_string($new_file_name)."')";
		   	$result = api_sql_query($sql, __FILE__, __LINE__);
		   	
		   	// display a feedback message
		   	Display::display_confirmation_message('TemplateAdded');
		   	echo '<a href="settings.php?category=Templates&amp;action=add">'.Display::return_icon('add_template.gif', get_lang('AddTemplate')).get_lang('AddTemplate').'</a>';
	   } else {
		   	$sql = "UPDATE $table_system_template set title = '".Database::escape_string($values['title'])."',
											   		  content = '".Database::escape_string($values['template_text'])."'";
		   	if (!empty($new_file_name))
		   	{
		   		$sql .= ", image = '".Database::escape_string($new_file_name)."'";
		   	}
		   	$sql .= " WHERE id='".Database::escape_string($_GET['id'])."'";
		   	$result = api_sql_query($sql, __FILE__, __LINE__);
		   	
		   	// display a feedback message
		   	Display::display_confirmation_message('TemplateEdited');		   	
	   }
	   display_templates();
	}
	else 
	{
		// display the form
		$form->display();		
	}
}

/**
 * Delete a template
 *
 * @param integer $id the id of the template that has to be deleted
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function delete_template($id)
{
	// first we remove the image
	$table_system_template = Database :: get_main_table('system_template');
	$sql = "SELECT * FROM $table_system_template WHERE id = '".Database::escape_string($id)."'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$row = Database::fetch_array($result);	
	if (!empty($row['image']))
	{
		unlink(api_get_path(SYS_PATH).'home/default_platform_document/'.$row['image']);
	}
	
	// now we remove it from the database
	$sql = "DELETE FROM $table_system_template WHERE id = '".Database::escape_string($id)."'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	
	// display a feedback message
	Display::display_confirmation_message('TemplateDeleted');
}
?>
