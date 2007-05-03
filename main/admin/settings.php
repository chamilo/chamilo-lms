<?php
// $Id: settings.php 12263 2007-05-03 13:34:40Z elixir_julian $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University
	Copyright (c) Patrick Cool, Ghent University
	Copyright (c) Roan Embrechts, Vrije Universiteit Brussel
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
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

// including some necessary dokeos files
include_once ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script();

// Submit Stylesheets
if ($_POST['submit_stylesheets'])
{
	$message = store_stylesheets();
	header("Location: {$_SERVER['PHP_SELF']}?category=stylesheets");
	exit;
}

// Database Table Definitions
$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

// setting breadcrumbs
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

// setting the name of the tool
$tool_name = get_lang('DokeosConfigSettings');

// Build the form
if ($_GET['category'] and $_GET['category'] <> "Plugins" and $_GET['category'] <> "stylesheets")
{
	$form = new FormValidator('settings', 'post', 'settings.php?category='.$_GET['category']);
	$renderer = & $form->defaultRenderer();
	$renderer->setHeaderTemplate('<div class="settingtitle">{header}</div>'."\n");
	$renderer->setElementTemplate('<div class="settingcomment">{label}</div>'."\n".'<div class="settingvalue">{element}</div>'."\n");
	$my_category = mysql_real_escape_string($_GET['category']);
	$sqlsettings = "SELECT DISTINCT * FROM $table_settings_current WHERE category='$my_category' GROUP BY variable ORDER BY id ASC";
	$resultsettings = api_sql_query($sqlsettings, __FILE__, __LINE__);
	while ($row = mysql_fetch_array($resultsettings))
	{
		$form->addElement('header', null, get_lang($row['title']));
		switch ($row['type'])
		{
			case 'textfield' :
				$form->addElement('text', $row['variable'], get_lang($row['comment']));
				$default_values[$row['variable']] = $row['selected_value'];
				break;
			case 'textarea' :
				$form->addElement('textarea', $row['variable'], get_lang($row['comment']));
				$default_values[$row['variable']] = $row['selected_value'];
				break;
			case 'radio' :
				$values = get_settings_options($row['variable']);
				$group = array ();
				foreach ($values as $key => $value)
				{
					$group[] = $form->createElement('radio', $row['variable'], '', get_lang($value['display_text']), $value['value']);
				}
				$form->addGroup($group, $row['variable'], get_lang($row['comment']), '<br />', false);
				$default_values[$row['variable']] = $row['selected_value'];
				break;
			case 'checkbox';
				$sql = "SELECT * FROM settings_current WHERE variable='".$row['variable']."'";
				$result = api_sql_query($sql, __FILE__, __LINE__);
				$group = array ();
				while ($rowkeys = mysql_fetch_array($result))
				{
					$element = & $form->createElement('checkbox', $rowkeys['subkey'], '', get_lang($rowkeys['subkeytext']));
					if ($rowkeys['selected_value'] == 'true' && ! $form->isSubmitted())
					{
						$element->setChecked(true);
					}
					$group[] = $element;
				}
				$form->addGroup($group, $row['variable'], get_lang($row['comment']), '<br />'."\n");
				break;
			case "link" :
				$form->addElement('static', null, get_lang($row['comment']), get_lang('CurrentValue').' : '.$row['selected_value']);
		}
	}
	$form->addElement('submit', null, get_lang('Ok'));
	$form->setDefaults($default_values);
	if ($form->validate())
	{
		$values = $form->exportValues();
		// the first step is to set all the variables that have type=checkbox of the category
		// to false as the checkbox that is unchecked is not in the $_POST data and can
		// therefore not be set to false
		$sql = "UPDATE $table_settings_current SET selected_value='false' WHERE category='$my_category' AND type='checkbox'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		// Save the settings
		foreach ($values as $key => $value)
		{
			if (!is_array($value))
			{
				$sql = "UPDATE $table_settings_current SET selected_value='".mysql_real_escape_string($value)."' WHERE variable='$key'";
				$result = api_sql_query($sql, __FILE__, __LINE__);
			}
			else
			{
				foreach ($value as $subkey => $subvalue)
				{
					$sql = "UPDATE $table_settings_current SET selected_value='true' WHERE variable='$key' AND subkey = '$subkey'";
					$result = api_sql_query($sql, __FILE__, __LINE__);
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
if ($_GET['action'] == "stored")
{
	Display :: display_normal_message($SettingsStored);
}

// grabbing the categories
$selectcategories = "SELECT DISTINCT category FROM ".$table_settings_current." WHERE category NOT IN ('stylesheets','Plugins')";
$resultcategories = api_sql_query($selectcategories, __FILE__, __LINE__);
echo "\n<div><ul>";
while ($row = mysql_fetch_array($resultcategories))
{
	echo "\n\t<li><a href=\"".api_get_self()."?category=".$row['category']."\">".ucfirst(get_lang($row['category']))."</a></li>";
}
echo "\n\t<li><a href=\"".api_get_self()."?category=Plugins\">".ucfirst(get_lang('Plugins'))."</a></li>";
echo "\n\t<li><a href=\"".api_get_self()."?category=stylesheets\">".ucfirst(get_lang('Stylesheets'))."</a></li>";
echo "\n</ul></div>";

if (isset ($_GET['category']))
{
	switch ($_GET['category'])
	{
		// displaying the extensions: plugins
		case 'Plugins' :
			handle_plugins();
			break;
			// displaying the extensions: Stylesheets
		case 'stylesheets' :
			handle_stylesheets();
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
	$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

	if ($_POST['submit_plugins'])
	{
		store_plugins();
		Display :: display_normal_message($SettingsStored);
	}

	echo get_lang('AvailablePlugins')."<br/><br/>";

	/* We scan the plugin directory. Each folder is a potential plugin. */
	$pluginpath = api_get_path(SYS_PLUGIN_PATH);

	$handle = opendir($pluginpath);
	while (false !== ($file = readdir($handle)))
	{
		if (is_dir(api_get_path(SYS_PLUGIN_PATH).$file) AND $file <> '.' AND $file <> '..')
		{
			$possibleplugins[] = $file;
		}
	}
	closedir($handle);

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
	$sql = "SELECT * FROM $table_settings_current WHERE category='Plugins'";
	$result = api_sql_query($sql);
	while ($row = mysql_fetch_array($result))
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

	echo '<input type="submit" name="submit_plugins" value="Submit" /></form>';
			}


function display_plugin_cell($location, $plugin_info, $current_plugin, $active_plugins)
{
	echo "\t\t<td align=\"center\">\n";
	if (in_array($location, $plugin_info['location']))
			{
		if (in_array($current_plugin, $active_plugins[$location]))
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
	// Current style
	$currentstyle = api_get_setting('stylesheets');

	// Preview of the stylesheet
	echo '<div><iframe src="style_preview.php" width="100%" height="300" name="preview"></iframe></div>';

	echo '<form name="stylesheets" method="post" action="'.api_get_self().'?category='.$_GET['category'].'">';
	if ($handle = opendir(api_get_path(SYS_PATH).'main/css/'))
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
					if ($currentstyle == $style_dir OR ($style_dir == 'default' AND !$currentstyle))
					{
						$selected = 'checked="checked"';
					}
					else
					{
						$selected = '';
					}

					echo "<input type=\"radio\" name=\"style\" value=\"".$style_dir."\" ".$selected." onClick=\"parent.preview.location='style_preview.php?style=".$style_dir."';\"/>";
					echo '<a href="style_preview.php?style='.$style_dir.'" target="preview">'.$style_dir.'</a>';
					//echo '<div id="Layer'.$counter.'" style="position:relative; width:687px; z-index:2; visibility: hidden;">';
					//echo '<a href="#" onClick="MM_showHideLayers(\'Layer'.$counter.'\',\'\',\'hide\')">'.get_lang('Close').'</a>';
					//echo '<iframe src="style_preview.php?style='.$file.'" width="100%" style="float:right;"></iframe></div>';
					echo "<br />\n";
					$counter++;
				}
			}
		}
		closedir($handle);
	}
	echo '<input type="submit" name="submit_stylesheets" value="Submit" /></form>';
}

/**
 * This function allows easy activating and inactivating of plugins
 * @todo: a similar function needs to be written to activate or inactivate additional tools.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function store_plugins()
{
	$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

	// Step 1 : we remove all the plugins
	$sql = "DELETE FROM $table_settings_current WHERE category='Plugins'";
	api_sql_query($sql, __LINE__, __FILE__);

	// step 2: looping through all the post values we only store these which are really a valid plugin location.
	foreach ($_POST as $form_name => $formvalue)
	{
		$form_name_elements = explode("-", $form_name);
		if (is_valid_plugin_location($form_name_elements[1]))
		{
			$sql = "INSERT into $table_settings_current (variable,category,selected_value) VALUES ('".$form_name_elements['1']."','Plugins','".$form_name_elements['0']."')";
			api_sql_query($sql, __LINE__, __FILE__);
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
	// Database Table Definitions
	$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

	// Insert the stylesheet
	$style = Database::escape_string($_POST['style']);
	if (is_style($style))
	{
		$sql = 'UPDATE '.$table_settings_current.' SET
				selected_value = "'.$style.'"
				WHERE variable = "stylesheets"
				AND category = "stylesheets"';

		api_sql_query($sql, __LINE__, __FILE__);
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
?>