<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	Copyright (c) Roan Embrechts, Vrije Universiteit Brussel

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
* GOAL : Dokeos installation
* As seen from the user, the installation proceeds in 6 steps.
* The user is presented with several webpages where he/she has to make choices
* and/or fill in data.
*
* The aim is, as always, to have good default settings and suggestions.
*
* @todo	reduce high level of duplication in this code
* @todo (busy) organise code into functions
* @package dokeos.install
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
session_start();

// Including necessary files
require('../inc/installedVersion.inc.php');
require('../inc/lib/main_api.lib.php');

require('../lang/english/trad4all.inc.php');
require('../lang/english/install.inc.php');

if ( isset($_POST['language_list']) && $_POST['language_list'] )
{
	$install_language = $_POST['language_list'];
	include_once("../lang/$install_language/trad4all.inc.php");
	include_once("../lang/$install_language/install.inc.php");
	api_session_register('install_language');
}
elseif ( isset($_SESSION['install_language']) && $_SESSION['install_language'] )
{
	$install_language = $_SESSION['install_language'];
	include_once("../lang/$install_language/trad4all.inc.php");
	include_once("../lang/$install_language/install.inc.php");
}

require_once('install_upgrade.lib.php');
require_once('install_functions.inc.php');

// Some constants
define('DOKEOS_INSTALL',1);
define('MAX_COURSE_TRANSFER',100);
define("INSTALL_TYPE_UPDATE", "update");
define("FORM_FIELD_DISPLAY_LENGTH", 40);
define("DATABASE_FORM_FIELD_DISPLAY_LENGTH", 25);
define("MAX_FORM_FIELD_LENGTH", 50);
define("DEFAULT_LANGUAGE", "english");

// setting the error reporting
error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR);

// overriding the timelimit (for large campusses that have to be migrated)
@set_time_limit(0);

//we hope in the future to add the ability to upgrade from 1.5.x versions
//to 1.8 as well.
$old_update_from_version=array('1.5','1.5.4','1.5.5','1.6');
$update_from_version=array('1.6','1.6.1','1.6.2','1.6.3','1.6.4','1.6.5');

/*
==============================================================================
		STEP 1 : INITIALIZES FORM VARIABLES IF IT IS THE FIRST VISIT
==============================================================================
*/
$badUpdatePath=false;

if($_POST['step2_install'] || $_POST['step2_update'])
{
	if($_POST['step2_install'])
	{
		$installType='new';

		$_POST['step2']=1;
	}
	else
	{
		$installType='update';

		if(empty($_POST['updatePath']))
		{
			$_POST['step1']=1;
		}
		else
		{
			if($_POST['updatePath'][strlen($_POST['updatePath'])-1] != '/')
			{
				$_POST['updatePath'].='/';
			}

			if(file_exists($_POST['updatePath']))
			{
				if(in_array(get_config_param('clarolineVersion'),$update_from_version))
				{
					$_POST['step2']=1;
				}
				else
				{
					$badUpdatePath=true;
				}
			}
			else
			{
				$badUpdatePath=true;
			}
		}
	}
}
elseif($_POST['step1'])
{
	$_POST['updatePath']='';
	$installType='';
	$updateFromConfigFile='';
	unset($_GET['running']);
}
else
{
	$installType=$_GET['installType'];
	$updateFromConfigFile=$_GET['updateFromConfigFile'];
}

if(!isset($_GET['running']))
{
	$dbHostForm='localhost';
	$dbUsernameForm='root';
	$dbPassForm='';
 	$dbPrefixForm='';
	$dbNameForm='dokeos_main';
	$dbStatsForm='dokeos_stats';
	$dbScormForm='dokeos_scorm';
	$dbUserForm='dokeos_user';

	// extract the path to append to the url if Dokeos is not installed on the web root directory
	$urlAppendPath=str_replace('/main/install/index.php','',$_SERVER['PHP_SELF']);
  	$urlForm='http://'.$_SERVER['HTTP_HOST'].$urlAppendPath.'/';
	$pathForm=str_replace('\\','/',realpath('../..')).'/';

	$emailForm=$_SERVER['SERVER_ADMIN'];
	$email_parts = explode('@',$emailForm);
	if($email_parts[1] == 'localhost')
	{
		$emailForm .= '.localdomain';
	}
	$adminLastName='Doe';
	$adminFirstName='John';
	$loginForm='admin';
	$passForm=api_generate_password();

	$campusForm='My campus';
	$educationForm='Albert Einstein';
	$adminPhoneForm='(000) 001 02 03';
	$institutionForm='My Organisation';
	$institutionUrlForm='http://www.dokeos.com';

	$languageForm='english';

	$checkEmailByHashSent=0;
	$ShowEmailnotcheckedToStudent=1;
	$userMailCanBeEmpty=1;
	$allowSelfReg=1;
	$allowSelfRegProf=1;
	$enableTrackingForm=1;
	$singleDbForm=0;
	$encryptPassForm=1;
}
else
{
	foreach($_POST as $key=>$val)
	{
		$magic_quotes_gpc=ini_get('magic_quotes_gpc')?true:false;

		if(is_string($val))
		{
			if($magic_quotes_gpc)
			{
				$val=stripslashes($val);
			}

			$val=trim($val);

			$_POST[$key]=$val;
		}
		elseif(is_array($val))
		{
			foreach($val as $key2=>$val2)
			{
				if($magic_quotes_gpc)
				{
					$val2=stripslashes($val2);
				}

				$val2=trim($val2);

				$_POST[$key][$key2]=$val2;
			}
		}

		$GLOBALS[$key]=$_POST[$key];
	}
}




// The Steps
$total_steps=7;
if (!$_POST)
{
	$current_step=1;
}
elseif ($_POST['language_list'])
{
	$current_step=2;
}
elseif ($_POST['step2'])
{
	$current_step=3;
}
elseif ($_POST['step3'])
{
	$current_step=4;
}
elseif ($_POST['step4'])
{
	$current_step=5;
}
elseif ($_POST['step5'])
{
	$current_step=6;
}



?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>&mdash; <?php echo get_lang('DokeosInstallation').' &mdash; '.get_lang('Version').$dokeos_version; ?></title>
	<style type="text/css" media="screen, projection">
		/*<![CDATA[*/
		@import "../css/default/default.css";
		/*]]>*/
	</style>
</head>
<body dir="<?php echo $text_dir ?>">


<div id="header">
	<div id="header1"><?php echo get_lang('DokeosInstallation').' &mdash; '.get_lang('Version').$dokeos_version; ?><?php if($installType == 'new') echo ' &ndash; '.get_lang('NewInstallation'); else if($installType == 'update') echo ' &ndash; '.get_lang('UpdateFromDokeosVersion').implode('|',$updateFromVersion); ?></div>
	<div class="clear"></div>
	<div id="header2">&nbsp;</div>
	<div id="header3">&nbsp;</div>
</div>


<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?running=1&amp;installType=<?php echo $installType; ?>&amp;updateFromConfigFile=<?php echo urlencode($updateFromConfigFile); ?>" autocomplete="off">

<div id="installation_steps">
	<img src="../img/bluelogo.gif" hspace="10" vspace="10" alt="Dokeos logo" />
	<ol>
		<li <?php step_active('1'); ?>><?php echo get_lang('InstallationLanguage'); ?></li>
		<li <?php step_active('2'); ?>><?php echo get_lang('Requirements'); ?></li>
		<li <?php step_active('3'); ?>><?php echo get_lang('Licence'); ?></li>
		<li <?php step_active('4'); ?>><?php echo get_lang('DBSetting'); ?></li>
		<li <?php step_active('5'); ?>><?php echo get_lang('CfgSetting'); ?></li>
		<li <?php step_active('6'); ?>><?php echo get_lang('PrintOverview'); ?></li>
		<li <?php step_active('7'); ?>><?php echo get_lang('Installing'); ?></li>
	</ol>
</div>

<table cellpadding="6" cellspacing="0" border="0" width="80%" align="center">
<tr>
  <td>
	<input type="hidden" name="updatePath"           value="<?php if(!$badUpdatePath) echo htmlentities($_POST['updatePath']); ?>" />
	<input type="hidden" name="urlAppendPath"        value="<?php echo htmlentities($urlAppendPath); ?>" />
	<input type="hidden" name="pathForm"             value="<?php echo htmlentities($pathForm); ?>" />
	<input type="hidden" name="urlForm"              value="<?php echo htmlentities($urlForm); ?>" />
	<input type="hidden" name="dbHostForm"           value="<?php echo htmlentities($dbHostForm); ?>" />
	<input type="hidden" name="dbUsernameForm"       value="<?php echo htmlentities($dbUsernameForm); ?>" />
	<input type="hidden" name="dbPassForm"           value="<?php echo htmlentities($dbPassForm); ?>" />
	<input type="hidden" name="singleDbForm"         value="<?php echo htmlentities($singleDbForm); ?>" />
	<input type="hidden" name="dbPrefixForm"         value="<?php echo htmlentities($dbPrefixForm); ?>" />
	<input type="hidden" name="dbNameForm"           value="<?php echo htmlentities($dbNameForm); ?>" />
	<input type="hidden" name="dbStatsForm"          value="<?php echo htmlentities($dbStatsForm); ?>" />
	<input type="hidden" name="dbScormForm"          value="<?php echo htmlentities($dbScormForm); ?>" />
	<input type="hidden" name="dbUserForm"           value="<?php echo htmlentities($dbUserForm); ?>" />
	<input type="hidden" name="enableTrackingForm"   value="<?php echo htmlentities($enableTrackingForm); ?>" />
	<input type="hidden" name="allowSelfReg"         value="<?php echo htmlentities($allowSelfReg); ?>" />
	<input type="hidden" name="allowSelfRegProf"     value="<?php echo htmlentities($allowSelfRegProf); ?>" />
	<input type="hidden" name="emailForm"            value="<?php echo htmlentities($emailForm); ?>" />
	<input type="hidden" name="adminLastName"        value="<?php echo htmlentities($adminLastName); ?>" />
	<input type="hidden" name="adminFirstName"       value="<?php echo htmlentities($adminFirstName); ?>" />
	<input type="hidden" name="adminPhoneForm"       value="<?php echo htmlentities($adminPhoneForm); ?>" />
	<input type="hidden" name="loginForm"            value="<?php echo htmlentities($loginForm); ?>" />
	<input type="hidden" name="passForm"             value="<?php echo htmlentities($passForm); ?>" />
	<input type="hidden" name="languageForm"         value="<?php echo htmlentities($languageForm); ?>" />
	<input type="hidden" name="campusForm"           value="<?php echo htmlentities($campusForm); ?>" />
	<input type="hidden" name="educationForm"        value="<?php echo htmlentities($educationForm); ?>" />
	<input type="hidden" name="institutionForm"      value="<?php echo htmlentities($institutionForm); ?>" />
	<input type="hidden" name="institutionUrlForm"   value="<?php echo stristr($institutionUrlForm,'http://')?htmlentities($institutionUrlForm):'http://'.htmlentities($institutionUrlForm); ?>" />
	<input type="hidden" name="checkEmailByHashSent" value="<?php echo htmlentities($checkEmailByHashSent); ?>" />
	<input type="hidden" name="ShowEmailnotcheckedToStudent" value="<?php echo htmlentities($ShowEmailnotcheckedToStudent); ?>" />
	<input type="hidden" name="userMailCanBeEmpty"   value="<?php echo htmlentities($userMailCanBeEmpty); ?>" />
	<input type="hidden" name="encryptPassForm"      value="<?php echo htmlentities($encryptPassForm); ?>" />




<?php

if($_POST['step2'])
{
	//STEP 3 : LICENSE
	display_license_agreement();
}
elseif($_POST['step3'])
{
	//STEP 4 : MYSQL DATABASE SETTINGS
	display_database_settings_form($installType, $dbHostForm, $dbUsernameForm, $dbPassForm, $dbPrefixForm, $enableTrackingForm, $singleDbForm, $dbNameForm, $dbStatsForm, $dbScormForm, $dbUserForm);
}
elseif($_POST['step4'])
{
	//STEP 5 : CONFIGURATION SETTINGS
	display_configuration_settings_form($installType, $urlForm, $languageForm, $emailForm, $adminFirstName, $adminLastName, $adminPhoneForm, $campusForm, $institutionForm, $institutionUrlForm, $encryptPassForm, $allowSelfReg, $loginForm, $passForm);
}
elseif($_POST['step5'])
{
	//STEP 6 : LAST CHECK BEFORE INSTALL
?>

	<h2><?php echo display_step_sequence().get_lang('LastCheck'); ?></h2>

	<?php echo get_lang('HereAreTheValuesYouEntered');?>
	<br>
	<b><?php echo get_lang('PrintThisPageToRememberPassAndOthers');?></b>

	<blockquote>

	<?php echo $langMainLang.' : '.$languageForm; ?><br><br>

	<?php echo $langDBHost.' : '.$dbHostForm; ?><br>
	<?php echo $langDBLogin.' : '.$dbUsernameForm; ?><br>
	<?php echo $langDBPassword.' : '.$dbPassForm; ?><br>
	<?php if(!empty($dbPrefixForm)) echo $langDbPrefixForm.' : '.$dbPrefixForm.'<br>'; ?>
	<?php echo $langMainDB.' : <b>'.$dbNameForm; ?></b><?php if($installType == 'new') echo ' (<font color="#cc0033">'.get_lang('ReadWarningBelow').'</font>)'; ?><br>
	<?php if(!$singleDbForm) { ?>
		<?php echo $langStatDB.' : <b>'.$dbStatsForm; ?></b><?php if($installType == 'new') echo ' (<font color="#cc0033">'.get_lang('ReadWarningBelow').'</font>)'; ?><br>
		<?php echo $langScormDB.' : <b>'.$dbScormForm; ?></b><?php if($installType == 'new') echo ' (<font color="#cc0033">'.get_lang('ReadWarningBelow').'</font>)'; ?><br>
		<?php echo $langUserDB.' : <b>'.$dbUserForm; ?></b><?php if($installType == 'new') echo ' (<font color="#cc0033">'.get_lang('ReadWarningBelow').'</font>)'; ?><br>
	<?php } ?>
	<?php echo $langEnableTracking.' : '.($enableTrackingForm?$langYes:$langNo); ?><br>
	<?php echo $langSingleDb.' : '.($singleDbForm?$langOne:$langSeveral); ?><br><br>

	<?php echo $langAllowSelfReg.' : '.($allowSelfReg?$langYes:$langNo); ?><br>
	<?php echo $langEncryptUserPass.' : '.($encryptPassForm?$langYes:$langNo); ?><br><br>

	<?php echo $langAdminEmail.' : '.$emailForm; ?><br>
	<?php echo $langAdminLastName.' : '.$adminLastName; ?><br>
	<?php echo $langAdminFirstName.' : '.$adminFirstName; ?><br>
	<?php echo $langAdminPhone.' : '.$adminPhoneForm; ?><br>

	<?php if($installType == 'new'): ?>
	<?php echo $langAdminLogin.' : <b>'.$loginForm; ?></b><br>
	<?php echo $langAdminPass.' : <b>'.$passForm; ?></b><br><br>
	<?php else: ?>
	<br>
	<?php endif; ?>

	<?php echo $langCampusName.' : '.$campusForm; ?><br>
	<?php echo $langInstituteShortName.' : '.$institutionForm; ?><br>
	<?php echo $langInstituteURL.' : '.$institutionUrlForm; ?><br>
	<?php echo $langDokeosURL.' : '.$urlForm; ?><br>

	</blockquote>

	<?php if($installType == 'new'): ?>
	<div style="background-color:#FFFFFF">
	<p align="center"><b><font color="red">
	<?php echo get_lang('Warning');?> !<br>
	<?php echo get_lang('TheInstallScriptWillEraseAllTables');?>
	</font></b></p>
	</div>
	<?php endif; ?>

	<table width="100%">
	<tr>
	  <td><input type="submit" name="step4" value="&lt; <?php echo get_lang('Previous'); ?>" /></td>
	  <td align="right"><input type="submit" name="step6" value="<?php echo get_lang('InstallDokeos'); ?> &gt;" onclick="javascript:if(this.value == '<?php echo get_lang('PleaseWait');?>...') return false; else this.value='<?php echo get_lang('PleaseWait');?>...';" /></td>
	</tr>
	</table>

<?php
}
elseif($_POST['step6'])
{
	//STEP 6 : INSTALLATION PROCESS
	if($installType == 'update')
	{
		include('update_db.inc.php');
		include('update_files.inc.php');
	}
	else
	{
		include('install_db.inc.php');
		include('install_files.inc.php');
	}

	display_after_install_message($installType, $nbr_courses);
}
elseif($_POST['step1'] || $badUpdatePath)
{

	//STEP 1 : REQUIREMENTS
	display_requirements($installType, $badUpdatePath, $update_from_version);
}
else
{
	//start screen
	display_language_selection();
}
?>

  </td>
</tr>
</table>



</form>
<br style="clear:both;" />
<div id="footer">
	<div class="copyright"><?php echo get_lang('Platform');?> <a href="http://www.dokeos.com"> Dokeos <?php echo $dokeos_version ?></a> &copy; <?php echo date('Y'); ?> </div>
	&nbsp;
</div>
</body>
</html>