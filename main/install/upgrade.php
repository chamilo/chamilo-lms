<?php // $Id: template.php,v 1.2 2006/03/15 14:34:45 pcool Exp $
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
* software is in a new_dokeos directory.
*
* This script uses PEAR QuickForm and QuickFormController classes.
*
* @package dokeos.install
==============================================================================
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

define('DOKEOS_INSTALL',1);
define('MAX_COURSE_TRANSFER',100);
define('INSTALL_TYPE_UPDATE', 'update');
define('FORM_FIELD_DISPLAY_LENGTH', 40);
define('DATABASE_FORM_FIELD_DISPLAY_LENGTH', 25);
define('MAX_FORM_FIELD_LENGTH', 50);
define('DEFAULT_LANGUAGE', 'english');

error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR);

@set_time_limit(0);

//deprecated:
$old_update_from_version=array('1.5','1.5.4','1.5.5','1.6');
//deprecated:
$update_from_version=array('1.6','1.6.1','1.6.2','1.6.3','1.6.4','1.6.5','community release 2.0','community release 2.0.1','community release 2.0.2','community release 2.0.3','community release 2.0.4');
$update_from_16_version = array('1.6','1.6.1','1.6.2','1.6.3','1.6.4','1.6.5');
$update_from_20_version = array('community release 2.0','community release 2.0.1','community release 2.0.2','community release 2.0.3','community release 2.0.4');




/*
==============================================================================
		CLASSES
==============================================================================
*/ 


/**
 * Class for location of old Dokeos installation
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
			@import "../css/default/default.css";
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
	
/*
==============================================================================
		MAIN CODE
==============================================================================
*/ 

display_upgrade_header($text_dir, $dokeos_version, $install_type, $update_from_version);

// Create a new wizard
$wizard = & new HTML_QuickForm_Controller('regWizard', true);

$wizard->addPage(new Page_LocationOldVersion('page_location_old_version'));

$defaults['old_version_path'] = '/var/www/html/old_version/';

// Set the default values
$wizard->setDefaults($defaults);

// Start the wizard
$wizard->run();

//$values = $wizard->exportValues();
//echo 'old version path = ' . $values['old_version_path'];


/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
Display::display_footer();
?>