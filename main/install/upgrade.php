<?php // $Id:$
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) 2007 various contributors
	
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
* In this file we're working on a well-organised upgrade script to
* upgrade directly from Dokeos 1.6.x to Dokeos 1.8.3 
*
* For this upgrade we assume there is an old_dokeos directory and the new
* software is in a new_dokeos directory. While we're busy developing we 
* work in this one  - large - separate file so not to disturb the other
* existing classes - the existing code remains working.
*
* This script uses PEAR QuickForm and QuickFormController classes.
*
* First version
* - ask for old version path
* - check version (1.6.x or 1.8.x, no others supported at the moment)
* - get settings from old version
* - perform necessary upgrade functions based on version

* Future improvements
* - ask user if she agrees to detected version (chance to cancel)
* - ability to do in-place upgrade
* - ability to let old databases remain and clone them for new install so
* Dokeos admins can have old and new version running side by side
*
* @package dokeos.install
==============================================================================
*/
/*
* ABOUT DETECTING OLDER VERSIONS
* Dokeos versions 1.6.x and 1.8.x have an installedVersion.inc.php file.
* In 1.6.x they have a parameter $platformVersion,
* in 1.8.x a parameter $dokeos_version.
* The function get_installed_version($old_installation_path, $parameter)
* can be used to detect version numbers.
*/
	
/*
==============================================================================
		INIT SECTION
==============================================================================
*/ 
session_start();

ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.'../inc/lib/pear');
//echo ini_get('include_path'); //DEBUG
require_once 'HTML/QuickForm/Controller.php';
require_once 'HTML/QuickForm/Rule.php';
require_once 'HTML/QuickForm/Action/Display.php';

require('../inc/installedVersion.inc.php');
require('../inc/lib/main_api.lib.php');

require('../lang/english/trad4all.inc.php');
require('../lang/english/install.inc.php');
require_once('install_upgrade.lib.php');
require_once('upgrade_lib.php');

define('DOKEOS_INSTALL',1);
define('MAX_COURSE_TRANSFER',100);
define('INSTALL_TYPE_UPDATE', 'update');
define('FORM_FIELD_DISPLAY_LENGTH', 40);
define('DATABASE_FORM_FIELD_DISPLAY_LENGTH', 25);
define('MAX_FORM_FIELD_LENGTH', 50);
define('DEFAULT_LANGUAGE', 'english');

//error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR);
error_reporting(E_ALL);

@set_time_limit(0);

if(function_exists('ini_set'))
{
	ini_set('memory_limit',-1);
	ini_set('max_execution_time',0);
}

$update_from_version=array('1.6','1.6.1','1.6.2','1.6.3','1.6.4','1.6.5','1.8.0','1.8.1','1.8.2');
$update_from_16_version = array('1.6','1.6.1','1.6.2','1.6.3','1.6.4','1.6.5');
$update_from_18_version = array('1.8.0','1.8.1','1.8.2');


/*
==============================================================================
		CLASSES
==============================================================================
*/ 

/**
 * Page in the install wizard to select the language which will be used during
 * the installation process.
 */
class Page_Language extends HTML_QuickForm_Page
{
	function get_title()
	{
		return get_lang('WelcomeToDokeosInstaller');
	}
	function get_info()
	{
		return 'Please select the language you\'d like to use while installing:';
	}
	function buildForm()
	{
		$this->_formBuilt = true;
		$this->addElement('select', 'install_language', get_lang('InstallationLanguage'), get_language_folder_list());
		$buttons[0] = & HTML_QuickForm :: createElement('submit', $this->getButtonName('next'), get_lang('Next').' >>');
		$this->addGroup($buttons, 'buttons', '', '&nbsp;', false);
		$this->setDefaultAction('next');
	}
}

/**
 * Class for requirements page
 * This checks and informs about some requirements for installing Dokeos:
 * - necessary and optional extensions
 * - folders which have to be writable
 */
class Page_Requirements extends HTML_QuickForm_Page
{
	/**
	* this function checks if a php extension exists or not
	*
	* @param string  $extentionName  name of the php extension to be checked
	* @param boolean  $echoWhenOk  true => show ok when the extension exists
	* @author Christophe Gesche
	*/
	function check_extension($extentionName)
	{
		if (extension_loaded($extentionName))
		{
			return '<li>'.$extentionName.' - ok</li>';
		}
		else
		{
			return '<li><b>'.$extentionName.'</b> <font color="red">is missing (Dokeos can work without)</font> (<a href="http://www.php.net/'.$extentionName.'" target="_blank">'.$extentionName.'</a>)</li>';
		}
	}
	function get_not_writable_folders()
	{
		$writable_folders = array ('../inc/conf', '../upload', '../../archive', '../../courses', '../../home');
		$not_writable = array ();
		$perm = api_get_setting('permissions_for_new_directories');
		$perm = octdec(!empty($perm)?$perm:'0770');
		foreach ($writable_folders as $index => $folder)
		{
			if (!is_writable($folder) && !@ chmod($folder, $perm))
			{
				$not_writable[] = $folder;
			}
		}
		return $not_writable;
	}
	function get_title()
	{
		return get_lang("Requirements");
	}
	function get_info()
	{
		$not_writable = $this->get_not_writable_folders();

		if (count($not_writable) > 0)
		{
			$info[] = '<div style="margin:20px;padding:10px;width: 50%;color:#FF6600;border:2px solid #FF6600;">';
			$info[] = 'Some files or folders don\'t have writing permission. To be able to install Dokeos you should first change their permissions (using CHMOD). Please read the <a href="../../installation_guide.html" target="blank">installation guide</a>.';
			$info[] = '<ul>';
			foreach ($not_writable as $index => $folder)
			{
				$info[] = '<li>'.$folder.'</li>';
			}
			$info[] = '</ul>';
			$info[] = '</div>';
			$this->disableNext = true;
		}
		elseif (file_exists('../inc/conf/claro_main.conf.php'))
		{
			$info[] = '<div style="margin:20px;padding:10px;width: 50%;color:#FF6600;border:2px solid #FF6600;text-align:center;">';
			$info[] = get_lang("WarningExistingDokeosInstallationDetected");
			$info[] = '</div>';
		}
		$info[] = '<b>'.get_lang("ReadThoroughly").'</b>';
		$info[] = '<br />';
		$info[] = get_lang("DokeosNeedFollowingOnServer");
		$info[] = "<ul>";
		$info[] = "<li>Webserver with PHP 5.x";
		$info[] = '<ul>';
		$info[] = $this->check_extension('standard');
		$info[] = $this->check_extension('session');
		$info[] = $this->check_extension('mysql');
		$info[] = $this->check_extension('zlib');
		$info[] = $this->check_extension('pcre');
		$info[] = '</ul></li>';
		$info[] = "<li>MySQL + login/password allowing to access and create at least one database</li>";
		$info[] = "<li>Write access to web directory where Dokeos files have been put</li>";
		$info[] = "</ul>";
		$info[] = get_lang('MoreDetails').", <a href=\"../../installation_guide.html\" target=\"blank\">read the installation guide</a>.";
		return implode("\n",$info);
	}
	function buildForm()
	{
		global $updateFromVersion;
		$this->_formBuilt = true;
		$this->addElement('radio', 'installation_type', get_lang('InstallType'), get_lang('NewInstall'), 'new');
		$update_group[0] = & HTML_QuickForm :: createElement('radio', 'installation_type', null, 'Update from Dokeos '.implode('|', $updateFromVersion).'', 'update');
		//$this->addGroup($update_group, 'update_group', '', '&nbsp;', false);
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('back'), '<< '.get_lang('Previous'));
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('next'), get_lang('Next').' >>');
		$not_writable = $this->get_not_writable_folders();
		if (count($not_writable) > 0)
		{
			$el = $prevnext[1];
			$el->updateAttributes('disabled="disabled"');
		}
		$this->addGroup($prevnext, 'buttons', '', '&nbsp;', false);
		$this->setDefaultAction('next');
	}
}

/**
 * Page in the install wizard to select the location of the old Dokeos installation.
 */
class Page_LocationOldVersion extends HTML_QuickForm_Page
{
	function get_title()
	{
		return 'Old version root path';
	}
	function get_info()
	{
		return 'Give location of your old Dokeos installation ';
	}
	function buildForm()
	{
		$this->_formBuilt = true;
		$this->addElement('text', 'old_version_path', 'Old version root path');
		$this->applyFilter('old_version_path', 'trim');
		$this->addRule('old_version_path', get_lang('ThisFieldIsRequired'), 'required');
		$this->addRule('old_version_path', get_lang('BadUpdatePath'), 'callback', 'check_update_path');
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('back'), '<< '.get_lang('Previous'));
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('next'), get_lang('Next').' >>');
		$this->addGroup($prevnext, 'buttons', '', '&nbsp;', false);
		$this->setDefaultAction('next');
	}
}

/**
 * Class for license page
 * Displays the GNU GPL license that has to be accepted to install Dokeos.
 */
class Page_License extends HTML_QuickForm_Page
{
	function get_title()
	{
		return get_lang('Licence');
	}
	function get_info()
	{
		return get_lang('DokeosLicenseInfo');
	}
	function buildForm()
	{
		$this->_formBuilt = true;
		$this->addElement('textarea', 'license', get_lang('Licence'), array ('cols' => 80, 'rows' => 20, 'disabled' => 'disabled', 'style'=>'background-color: white;'));
		$this->addElement('checkbox','license_accept','',get_lang('IAccept'));
		$this->addRule('license_accept',get_lang('ThisFieldIsRequired'),'required');
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('back'), '<< '.get_lang('Previous'));
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('next'), get_lang('Next').' >>');
		$this->addGroup($prevnext, 'buttons', '', '&nbsp;', false);
		$this->setDefaultAction('next');
	}
}

/**
 * Class for database settings page
 * Displays a form where the user can enter the installation settings
 * regarding the databases - login and password, names, prefixes, single
 * or multiple databases, tracking or not...
 */
class Page_DatabaseSettings extends HTML_QuickForm_Page
{
	function get_title()
	{
		return get_lang('DBSetting');
	}
	function get_info()
	{
		return get_lang('DBSettingIntro');
	}
	function buildForm()
	{
		$this->_formBuilt = true;
		$this->addElement('text', 'database_host', get_lang("DBHost"), array ('size' => '40'));
		$this->addRule('database_host', 'ThisFieldIsRequired', 'required');
		$this->addElement('text', 'database_username', get_lang("DBLogin"), array ('size' => '40'));
		$this->addElement('password', 'database_password', get_lang("DBPassword"), array ('size' => '40'));
		$this->addRule(array('database_host','database_username','database_password'),get_lang('CouldNotConnectToDatabase'),new ValidateDatabaseConnection());
		$this->addElement('text', 'database_prefix', get_lang("DbPrefixForm"), array ('size' => '40'));
		$this->addElement('text', 'database_main_db', get_lang("MainDB"), array ('size' => '40'));
		$this->addRule('database_main_db', 'ThisFieldIsRequired', 'required');
		$this->addElement('text', 'database_tracking', get_lang("StatDB"), array ('size' => '40'));
		$this->addRule('database_tracking', 'ThisFieldIsRequired', 'required');
		$this->addElement('text', 'database_scorm', get_lang("ScormDB"), array ('size' => '40'));
		$this->addRule('database_scorm', 'ThisFieldIsRequired', 'required');
		$this->addElement('text', 'database_user', get_lang("UserDB"), array ('size' => '40'));
		$this->addRule('database_user', 'ThisFieldIsRequired', 'required');
		//$this->addElement('text', 'database_repository', get_lang("RepositoryDatabase"), array ('size' => '40'));
		//$this->addRule('database_repository', 'ThisFieldIsRequired', 'required');
		//$this->addElement('text', 'database_weblcms', get_lang("WeblcmsDatabase"), array ('size' => '40'));
		//$this->addRule('database_weblcms', 'ThisFieldIsRequired', 'required');
		//$this->addElement('text', 'database_personal_calendar', get_lang("PersonalCalendarDatabase"), array ('size' => '40'));
		//$this->addRule('database_personal_calendar', 'ThisFieldIsRequired', 'required');
		//$this->addElement('text', 'database_personal_messenger', get_lang("PersonalMessageDatabase"), array ('size' => '40'));
		//$this->addRule('database_personal_messenger', 'ThisFieldIsRequired', 'required');
		//$this->addElement('text', 'database_profiler', get_lang("ProfilerDatabase"), array ('size' => '40'));
		//$this->addRule('database_profiler', 'ThisFieldIsRequired', 'required');
		
		$enable_tracking[] = & $this->createElement('radio', 'enable_tracking', null, get_lang("Yes"), 1);
		$enable_tracking[] = & $this->createElement('radio', 'enable_tracking', null, get_lang("No"), 0);
		$this->addGroup($enable_tracking, 'tracking', get_lang("EnableTracking"), '&nbsp;', false);
		$several_db[] = & $this->createElement('radio', 'database_single', null, get_lang("One"),1);
		$several_db[] = & $this->createElement('radio', 'database_single', null, get_lang("Several"),0);
		$this->addGroup($several_db, 'db', get_lang("SingleDb"), '&nbsp;', false);
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('back'), '<< '.get_lang('Previous'));
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('next'), get_lang('Next').' >>');
		$this->addGroup($prevnext, 'buttons', '', '&nbsp;', false);
		$this->setDefaultAction('next');
	}
}
class ValidateDatabaseConnection extends HTML_QuickForm_Rule
{
	public function validate($parameters)
	{
		$db_host = $parameters[0];
		$db_user = $parameters[1];
		$db_password = $parameters[2];
		if(mysql_connect($db_host,$db_user,$db_password))
		{
			return true;
		}
		return false;
	}
}

/**
 * Page in the install wizard in which some config settings are asked to the
 * user.
 */
class Page_ConfigSettings extends HTML_QuickForm_Page
{
	function get_title()
	{
		return get_lang('CfgSetting');
	}
	function get_info()
	{
		return get_lang('ConfigSettingsInfo');
	}
	function buildForm()
	{
		$this->_formBuilt = true;
		$languages = array ();
		$languages['dutch'] = 'dutch';
		$this->addElement('select', 'platform_language', get_lang("MainLang"), get_language_folder_list());
		$this->addElement('text', 'platform_url', get_lang("DokeosURL"), array ('size' => '40'));
		$this->addRule('platform_url', get_lang('ThisFieldIsRequired'), 'required');
		$this->addElement('text', 'admin_email', get_lang("AdminEmail"), array ('size' => '40'));
		$this->addRule('admin_email', get_lang('ThisFieldIsRequired'), 'required');
		$this->addRule('admin_email', get_lang('WrongEmail'), 'email');
		$this->addElement('text', 'admin_lastname', get_lang("AdminLastName"), array ('size' => '40'));
		$this->addRule('admin_lastname', get_lang('ThisFieldIsRequired'), 'required');
		$this->addElement('text', 'admin_firstname', get_lang("AdminFirstName"), array ('size' => '40'));
		$this->addRule('admin_firstname', get_lang('ThisFieldIsRequired'), 'required');
		$this->addElement('text', 'admin_phone', get_lang("AdminPhone"), array ('size' => '40'));
		$this->addElement('text', 'admin_username', get_lang("AdminLogin"), array ('size' => '40'));
		$this->addRule('admin_username', get_lang('ThisFieldIsRequired'), 'required');
		$this->addElement('text', 'admin_password', get_lang("AdminPass"), array ('size' => '40'));
		$this->addRule('admin_password', get_lang('ThisFieldIsRequired'), 'required');
		$this->addElement('text', 'platform_name', get_lang("CampusName"), array ('size' => '40'));
		$this->addRule('platform_name', get_lang('ThisFieldIsRequired'), 'required');
		$this->addElement('text', 'organization_name', get_lang("InstituteShortName"), array ('size' => '40'));
		$this->addRule('organization_name', get_lang('ThisFieldIsRequired'), 'required');
		$this->addElement('text', 'organization_url', get_lang("InstituteURL"), array ('size' => '40'));
		$this->addRule('organization_url', get_lang('ThisFieldIsRequired'), 'required');
		$encrypt[] = & $this->createElement('radio', 'encrypt_password', null, get_lang('Yes'), 1);
		$encrypt[] = & $this->createElement('radio', 'encrypt_password', null, get_lang('No'), 0);
		$this->addGroup($encrypt, 'tracking', get_lang("EncryptUserPass"), '&nbsp;', false);
		$self_reg[] = & $this->createElement('radio', 'self_reg', null, get_lang('Yes'), 1);
		$self_reg[] = & $this->createElement('radio', 'self_reg', null, get_lang('No'), 0);
		$this->addGroup($self_reg, 'tracking', get_lang("AllowSelfReg"), '&nbsp;', false);
		$self_reg_teacher[] = & $this->createElement('radio', 'self_reg_teacher', null, get_lang('Yes'), 1);
		$self_reg_teacher[] = & $this->createElement('radio', 'self_reg_teacher', null, get_lang('No'), 0);
		$this->addGroup($self_reg_teacher, 'tracking', get_lang("AllowSelfRegProf"), '&nbsp;', false);
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('back'), '<< '.get_lang('Previous'));
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('next'), get_lang('Next').' >>');
		$this->addGroup($prevnext, 'buttons', '', '&nbsp;', false);
		$this->setDefaultAction('next');
	}
}

/**
 * Page in the install wizard in which a final overview of all settings is
 * displayed.
 */
class Page_ConfirmSettings extends HTML_QuickForm_Page
{
	function get_title()
	{
		return get_lang('LastCheck');
	}
	function get_info()
	{
		return 'Here are the values you entered
				<br />
				<strong>Print this page to remember your password and other settings</strong>';

	}
	function buildForm()
	{
		$wizard = $this->controller;
		$values = $wizard->exportValues();
		$this->addElement('static', 'confirm_platform_language', get_lang("MainLang"), $values['platform_language']);
		$this->addElement('static', 'confirm_platform_url', get_lang("DokeosURL"), $values['platform_url']);
		$this->addElement('static', 'confirm_admin_email', get_lang("AdminEmail"), $values['admin_email']);
		$this->addElement('static', 'confirm_admin_lastname', get_lang("AdminLastName"), $values['admin_lastname']);
		$this->addElement('static', 'confirm_admin_firstname', get_lang("AdminFirstName"), $values['admin_firstname']);
		$this->addElement('static', 'confirm_admin_phone', get_lang("AdminPhone"), $values['admin_phone']);
		$this->addElement('static', 'confirm_admin_username', get_lang("AdminLogin"), $values['admin_username']);
		$this->addElement('static', 'confirm_admin_password', get_lang("AdminPass"), $values['admin_password']);
		$this->addElement('static', 'confirm_platform_name', get_lang("CampusName"), $values['platform_name']);
		$this->addElement('static', 'confirm_organization_name', get_lang("InstituteShortName"), $values['organization_name']);
		$this->addElement('static', 'confirm_organization_url', get_lang("InstituteURL"), $values['organization_url']);
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('back'), '<< '.get_lang('Previous'));
		$prevnext[] = & $this->createElement('submit', $this->getButtonName('next'), get_lang('Next').' >>');
		$this->addGroup($prevnext, 'buttons', '', '&nbsp;', false);
		$this->setDefaultAction('next');
	}
}


/**
 * Class to render a page in the install wizard.
 */
class ActionDisplay extends HTML_QuickForm_Action_Display
{
	/**
	 * Displays the HTML-code of a page in the wizard
	 * @param HTML_Quickform_Page $page The page to display.
	 */
	function _renderForm(& $current_page)
	{
		global $charset;

		global $dokeos_version, $installType, $updateFromVersion;
		$renderer = & $current_page->defaultRenderer();
		$current_page->setRequiredNote('<font color="#FF0000">*</font> '.get_lang('ThisFieldIsRequired'));
		$element_template = "\n\t<tr>\n\t\t<td valign=\"top\"><!-- BEGIN required --><span style=\"color: #ff0000\">*</span> <!-- END required -->{label}</td>\n\t\t<td valign=\"top\" align=\"left\"><!-- BEGIN error --><span style=\"color: #ff0000;font-size:x-small;margin:2px;\">{error}</span><br /><!-- END error -->\t{element}</td>\n\t</tr>";
		$renderer->setElementTemplate($element_template);
		$header_template = "\n\t<tr>\n\t\t<td valign=\"top\" colspan=\"2\">{header}</td>\n\t</tr>";
		$renderer->setHeaderTemplate($header_template);
		HTML_QuickForm :: setRequiredNote('<font color="red">*</font> <small>'.get_lang('ThisFieldIsRequired').'</small>');
		$current_page->accept($renderer);
?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
		<head>
		<title>-- Dokeos - upgrade to version <?php echo $dokeos_version; ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
		<link rel="stylesheet" href="../css/public_admin/default.css" type="text/css"/>
		</head>
		<body dir="<?php echo get_lang('text_dir'); ?>">
		<div id="header1">
			Dokeos - upgrade to version <?php echo $dokeos_version; ?><?php if($installType == 'new') echo ' - New installation'; else if($installType == 'update') echo ' - Update from Dokeos '.implode('|',$updateFromVersion); ?>
		</div>
		<div style="float: left; background-color:#EFEFEF;margin-right: 20px;padding: 10px;">
			<img src="../img/bluelogo.gif" alt="logo"/>
			<?php

		$all_pages = $current_page->controller->_pages;
		$total_number_of_pages = count($all_pages);
		$current_page_number = 0;
		$page_number = 0;
		echo '<ol>';
		foreach($all_pages as $index => $page)
		{
			$page_number++;
			if($page->get_title() == $current_page->get_title())
			{
				$current_page_number = $page_number;
				echo '<li style="font-weight: bold;">'.$page->get_title().'</li>';
			}
			else
			{
				echo '<li>'.$page->get_title().'</li>';
			}
		}
		echo '</ol>';
		echo '</div>';
		echo '<div style="margin: 10px;">';
		echo '<h2>'.get_lang('Step').' '.$current_page_number.' '.get_lang('of').' '.$total_number_of_pages.' &ndash; '.$current_page->get_title().'</h2>';
		echo '<div>';
		echo $current_page->get_info();
		echo '</div>';
		echo $renderer->toHtml();
		?>
        </div>
        <div style="clear:both;"></div>
        <div id="footer">
        &copy; <?php echo $dokeos_version; ?>
        </div>
		</body>
		</html>
		<?php
	}
}

/**
* Class for form processing
* Here happens the actual installation action after collecting
* all the required data.
*/
class ActionProcess extends HTML_QuickForm_Action
{
	function perform(& $page, $actionName)
	{
		global $charset;

		global $dokeos_version, $installType, $updateFromVersion;
		$values = $page->controller->exportValues();
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
		<head>
		<title>-- Dokeos installation -- version <?php echo $dokeos_version; ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
		<link rel="stylesheet" href="../css/public_admin/default.css" type="text/css"/>
		</head>
		<body dir="<?php echo get_lang('text_dir'); ?>">
		<div style="background-color:#4171B5;color:white;font-size:x-large;">
			Dokeos installation - version <?php echo $dokeos_version; ?><?php if($installType == 'new') echo ' - New installation'; else if($installType == 'update') echo ' - Update from Dokeos '.implode('|',$updateFromVersion); ?>
		</div>
		<div style="margin:50px;">
			<img src="../img/bluelogo.gif" alt="logo" align="right"/>
		<?php
		echo '<pre>';
		
		global $repository_database;
		global $weblcms_database;
		global $personal_calendar_database;
		global $user_database;
		global $personal_messenger_database;
		global $profiler_database;
		
		$repository_database = $values['database_repository'];
		$weblcms_database = $values['database_weblcms'];
		$personal_calendar_database = $values['database_personal_calendar'];
		$user_database = $values['database_user'];
		$personal_messenger_database = $values['database_personal_messenger'];
		$profiler_database = $values['database_profiler'];
		
		/*full_database_install($values);
		full_file_install($values);
		create_admin_in_user_table($values);
		create_default_categories_in_weblcms();*/
		echo "<p>Performing upgrade to latest version....</p>";

		//upgrade_16x_to_180($values);

		echo '</pre>';
		$page->controller->container(true);
		?>
		<a class="portal" href="../../index.php"><?php echo get_lang('GoToYourNewlyCreatedPortal'); ?></a>
        </div>
		</body>
		</html>
		<?php
	}
}


/*
==============================================================================
		FUNCTIONS
==============================================================================
*/ 

function display_upgrade_header($text_dir, $dokeos_version, $install_type, $update_from_version)
{
	?>
	<!DOCTYPE html
		PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>&mdash; <?php echo get_lang('DokeosInstallation').' &mdash; '.get_lang('Version_').' '.$dokeos_version; ?></title>
		<style type="text/css" media="screen, projection">
			/*<![CDATA[*/
			@import "../css/public_admin/default.css";
			/*]]>*/
		</style>
	<?php if(!empty($charset)){ ?>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset ?>" />
	<?php } ?>
	</head>
	<body dir="<?php echo $text_dir ?>">
	
	<div id="header">
		<div id="header1"><?php echo get_lang('DokeosInstallation').' &mdash; '.get_lang('Version_').' '.$dokeos_version; ?><?php if($install_type == 'new') echo ' &ndash; '.get_lang('NewInstallation'); else if($install_type == 'update') echo ' &ndash; '.get_lang('UpdateFromDokeosVersion').implode('|',$update_from_version); ?></div>
		<div class="clear"></div>
		<div id="header2">&nbsp;</div>
		<div id="header3">&nbsp;</div>
	</div>
	<?php
}

/**
*	Return a list of language directories.
*	@todo function does not belong here, move to code library,
*	also see infocours.php which contains similar function
*/
function get_language_folder_list()
{
	$dirname = dirname(__FILE__).'/../lang';
	if ($dirname[strlen($dirname) - 1] != '/')
		$dirname .= '/';
	$handle = opendir($dirname);
	while ($entries = readdir($handle))
	{
		if ($entries == '.' || $entries == '..' || $entries == '.svn')
			continue;
		if (is_dir($dirname.$entries))
		{
			$language_list[$entries] = api_ucfirst($entries);
		}
	}
	closedir($handle);
	asort($language_list);
	return $language_list;
}


function display_installation_overview()
{
	echo '<div id="installation_steps">';
	echo '<img src="../img/bluelogo.gif" hspace="10" vspace="10" alt="Dokeos logo" />';
	echo '<ol>';
	echo '<li ' . step_active('1') . '> ' . get_lang('InstallationLanguage') . '</li>';
	echo '<li ' . step_active('2') . '> ' . get_lang('Requirements') . '</li>';
	echo '<li ' . step_active('3') . '> ' . get_lang('Licence') . '</li>';
	echo '<li ' . step_active('4') . '> ' . get_lang('DBSetting') . '</li>';
	echo '<li ' . step_active('5') . '> ' . get_lang('CfgSetting') . '</li>';
	echo '<li ' . step_active('6') . '> ' . get_lang('PrintOverview') . '</li>';
	echo '<li ' . step_active('7') . '> ' . get_lang('Installing') . '</li>';
	echo '</ol>';
	echo '</div>';
}

/**
 * This function prints class=active_step $current_step=$param
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function step_active($this_step)
{
	global $current_active_step;
	if ($current_active_step == $this_step)
	{
		return ' class="current_step" ';
	}
}

// Rule to check update path
function check_update_path($path)
{
	global $update_from_version;
	// Make sure path has a trailing /
	$path = substr($path,-1) != '/' ? $path.'/' : $path;
	// Check the path
	if (file_exists($path))
	{
		//search for 1.6.x installation
		$version = get_installed_version($path, 'platformVersion');
		
		//search for 1.8.x installation
		//if (! isset($version) || $version == '')
		//{
		//   $version = get_installed_version($path, 'dokeos_version');
		//}
		
		if (in_array($version, $update_from_version))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	return false;
}

/**
 * This function returns the installed version of
 * the older installation to upgrade by checking the
 * claroline/inc/installedVersion.inc.php file.
 */
function get_installed_version($old_installation_path, $parameter)
{
	if( file_exists($old_installation_path.'claroline/inc/installedVersion.inc.php') )
	{
		$version_info_file = 'claroline/inc/installedVersion.inc.php';
	}
	// with include_once inside a function, variables aren't remembered for later use
	include($old_installation_path.$version_info_file);
	if (isset($$parameter))
	{
		return $$parameter;
	}
}

/**
 * This function returns a the value of a parameter from the configuration file
 * of a previous installation.
 *
 * IMPORTANT
 * - Before Dokeos 1.8 the main code folder was called 'claroline'. Since Dokeos 1.8
 * this folder is called 'main' -> we have to make a difference based on previous 
 * version.
 * - The version may be in the config file or in the installedVersion file... 
 *
 * WARNING - this function relies heavily on global variables $updateFromConfigFile
 * and $configFile, and also changes these globals. This can be rewritten.
 *
 * @param string  $param  the parameter which the value is returned for
 * @return  string  the value of the parameter
 * @author Olivier Brouckaert
 */
function get_config_param($param,$path)
{
	global $configFile, $updateFromConfigFile;

	if (empty ($updateFromConfigFile))
	{
		if (file_exists($path.'claroline/include/config.inc.php'))
		{
			$updateFromConfigFile = 'claroline/include/config.inc.php';
		}
		elseif (file_exists($path.'claroline/inc/conf/claro_main.conf.php'))
		{
			$updateFromConfigFile = 'claroline/inc/conf/claro_main.conf.php';
		}
		else
		{
			return;
		}
	}

	//echo "reading from file $path$updateFromConfigFile, which exists...";

	if (is_array($configFile) && isset ($configFile[$param]))
	{
		return $configFile[$param];
	}
	elseif (file_exists($path.$updateFromConfigFile))
	{
		$configFile = array ();

		$temp = file($path.$updateFromConfigFile);

		$val = '';

		foreach ($temp as $enreg)
		{
			if (strstr($enreg, '='))
			{
				$enreg = explode('=', $enreg);

				if ($enreg[0][0] == '$')
				{
					list ($enreg[1]) = explode(' //', $enreg[1]);

					$enreg[0] = trim(str_replace('$', '', $enreg[0]));
					$enreg[1] = str_replace('\"', '"', ereg_replace('(^"|"$)', '', substr(trim($enreg[1]), 0, -1)));

					if (strtolower($enreg[1]) == 'true')
					{
						$enreg[1] = 1;
					}
					if (strtolower($enreg[1]) == 'false')
					{
						$enreg[1] = 0;
					}
					else
					{
						$implode_string = ' ';

						if (!strstr($enreg[1], '." ".') && strstr($enreg[1], '.$'))
						{
							$enreg[1] = str_replace('.$', '." ".$', $enreg[1]);
							$implode_string = '';
						}

						$tmp = explode('." ".', $enreg[1]);

						foreach ($tmp as $tmp_key => $tmp_val)
						{
							if (eregi('^\$[a-z_][a-z0-9_]*$', $tmp_val))
							{
								$tmp[$tmp_key] = get_config_param(str_replace('$', '', $tmp_val), $path);
							}
						}

						$enreg[1] = implode($implode_string, $tmp);
					}

					$configFile[$enreg[0]] = $enreg[1];

					if ($enreg[0] == $param)
					{
						$val = $enreg[1];
					}
				}
			}
		}

		return $val;
	}
}
	
/*
==============================================================================
		MAIN CODE
==============================================================================
*/ 
global $current_active_step;
$current_active_step = '1';
$install_type = 'update';
//display_upgrade_header($text_dir, $dokeos_version, $install_type, $update_from_version);
//display_installation_overview();

// Create a new wizard
$wizard = & new HTML_QuickForm_Controller('regWizard', true);

//Add pages to wizard - path to follow for upgrade
//$wizard->addPage(new Page_Language('page_language'));
//$wizard->addPage(new Page_Requirements('page_requirements'));
$wizard->addPage(new Page_LocationOldVersion('page_location_old_version'));
$values = $wizard->exportValues();
if( isset($values['old_version_path']) && $values['old_version_path'] != '/var/www/html/old_version/' )
{
	$path = $values['old_version_path'];
	$defaults['platform_language'] = get_config_param('platformLanguage',$path);
	$defaults['platform_url'] = 'http://'.$_SERVER['HTTP_HOST'].$urlAppendPath.'/';
	//to keep debug output readable:
	//$defaults['license'] = 'GNU GPL v2';
	//actual license:
	$defaults['license'] = implode("\n", file('../../documentation/license.txt'));
	$defaults['database_host'] = get_config_param('dbHost',$path);
	$defaults['database_main_db'] = get_config_param('mainDbName',$path);
	$defaults['database_tracking'] = get_config_param('statsDbName',$path);
	$defaults['database_scorm'] = get_config_param('scormDbName',$path);
	$defaults['database_user'] = get_config_param('user_personal_database',$path);
	//$defaults['database_repository'] = 'dokeos_repository';
	//$defaults['database_weblcms'] = 'dokeos_weblcms';
	$defaults['database_username'] = get_config_param('dbLogin',$path);
	$defaults['database_password'] = get_config_param('dbPass',$path);
	$defaults['database_prefix'] = get_config_param('dbNamePrefix',$path);
	$defaults['enable_tracking'] = get_config_param('is_trackingEnabled',$path);
	$defaults['database_single'] = get_config_param('singleDbEnabled',$path);
	$defaults['admin_lastname'] = 'Doe';
	$defaults['admin_firstname'] = mt_rand(0,1)?'John':'Jane';
	$defaults['admin_email'] = get_config_param('emailAdministrator',$path);
	$defaults['admin_username'] = 'admin';
	$defaults['admin_password'] = api_generate_password();
	$defaults['admin_phone'] = get_config_param('administrator["phone"]',$path);
	$defaults['platform_name'] = get_config_param('siteName',$path);
	$defaults['encrypt_password'] = 1;
	$defaults['organization_name'] = get_config_param('institution["name"]',$path);
	$defaults['organization_url'] = get_config_param('institution["url"]',$path);
	if (get_config_param('userPasswordCrypted',$path)==1) {
		$defaults['encrypt_password'] = 'md5';
	} elseif (get_config_param('userPasswordCrypted',$path)==0){
		$defaults['encrypt_password'] = 'none';
	}	
	//$defaults['encrypt_password'] = get_config_param('userPasswordCrypted',$path);
	$defaults['self_reg'] = get_config_param('allowSelfReg',$path);
}
else
{
	//old version path not correct yet
}

$wizard->addPage(new Page_License('page_license'));
$wizard->addPage(new Page_DatabaseSettings('page_databasesettings'));
$wizard->addPage(new Page_ConfigSettings('page_configsettings'));
$wizard->addPage(new Page_ConfirmSettings('page_confirmsettings'));

$defaults['install_language'] = 'english';
//$defaults['old_version_path'] = '/var/www/html/old_version/';
$defaults['old_version_path'] = '';

// Set the default values
$wizard->setDefaults($defaults);

// Add the process action to the wizard
$wizard->addAction('process', new ActionProcess());

// Add the display action to the wizard
$wizard->addAction('display', new ActionDisplay());

// Set the installation language
$install_language = $wizard->exportValue('page_language', 'install_language');
require_once ('../lang/english/trad4all.inc.php');
require_once ('../lang/english/install.inc.php');
include_once ("../lang/$install_language/trad4all.inc.php");
include_once ("../lang/$install_language/install.inc.php");

// Set default platform language to the selected install language
$defaults['platform_language'] = $install_language;
$wizard->setDefaults($defaults);

// Start the wizard
$wizard->run();

// Set the installation language
$install_language = $wizard->exportValue('page_language', 'install_language');
require_once ('../lang/english/trad4all.inc.php');
require_once ('../lang/english/install.inc.php');
include_once ("../lang/$install_language/trad4all.inc.php");
include_once ("../lang/$install_language/install.inc.php");

//$values = $wizard->exportValues();

?>
