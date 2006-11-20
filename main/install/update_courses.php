<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

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
* GOAL: Updates courses separately
*
* After upgrading a previous version to Dokeos 1.6, there are only
* MAX_COURSE_TRANSFER courses converted to the new format - with
* MAX_COURSE_TRANSFER in install/index.php being 100 as default.
*
* To update the rest of the courses you need to run this script.
*
* @package dokeos.install
* @todo remove duplication: MAX_COURSE_TRANSFER is defined here and
* also in install.index.php
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

require('../inc/installedVersion.inc.php');
require('../inc/lib/main_api.lib.php');

require('../lang/english/trad4all.inc.php');
require('../lang/english/install.inc.php');


define('DOKEOS_COURSE_UPDATE',1);
define('MAX_COURSE_TRANSFER',100);

error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR);

@set_time_limit(0);

$update_path=trim(stripslashes($_GET['update_path']));

$updateFromVersion=array('1.5','1.5.4','1.5.5');

/*
==============================================================================
		INITIALISE FORM VARIABLES
		(If this is the first visit to this script.)
		Variables are read from the configuration file
		of the old Dokeos version (configuration.php).
==============================================================================
*/

$updateFromConfigFile=''; // leave empty
$badUpdatePath=false;

if($_POST['step2'])
{
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

		if(!file_exists($_POST['updatePath']))
		{
			$badUpdatePath=true;

			$_POST['step2']=0;
		}
		elseif(!in_array(get_config_param('clarolineVersion'),$updateFromVersion))
		{
			$badUpdatePath=true;

			$_POST['step2']=0;
		}
		else
		{
			$urlAppendPath=str_replace('/main/install/update_courses.php','',$_SERVER['PHP_SELF']);
		  	$urlForm='http://'.$_SERVER['HTTP_HOST'].$urlAppendPath.'/';

			$singleDbForm=get_config_param('singleDbEnabled');
			$dbNameForm=get_config_param('mainDbName');
			$dbHostForm=get_config_param('dbHost');
			$dbUsernameForm=get_config_param('dbLogin');
			$dbPassForm=get_config_param('dbPass');
		}
	}
}
elseif($_POST['step1'])
{
	$_POST['updatePath']='';
}
?>

<html>
<head>
<title>-- Dokeos course update -- version <?php echo $dokeos_version; ?></title>
<link rel="stylesheet" href="../css/default.css" type="text/css">
</head>
<body bgcolor="white" dir="<?php echo $text_dir ?>">

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<table cellpadding="6" cellspacing="0" border="0" width="650" bgcolor="#E6E6E6" align="center">
<tr bgcolor="#4171B5"">
  <td valign="top">
	<big><font color="white">Dokeos course update - version <?php echo $dokeos_version; ?></font></big>
  </td>
</tr>
<tr bgcolor="#E6E6E6">
  <td>

	<img src="../img/bluelogo.gif" align="right" hspace="10" vspace="10">

<?php


/*
==============================================================================
		STEP 2 - COURSE UPDATE PROCESS

		the included files, update_db.inc.php and update_files.inc.php
		do the actual work of converting the course database
		and the files, respectively
==============================================================================
*/

if($_POST['step2'])
{
	include('update_db.inc.php');
	include('update_files.inc.php');
?>

	<h2>Step 2 of 2 &ndash; Course Update</h2>

	<?php echo sizeof($coursePath); ?> courses have been successfully updated.
	<br /><br />

	<?php if($nbr_courses > MAX_COURSE_TRANSFER): ?>
	<font color="red"><b>Warning:</b> You have more than <?php echo MAX_COURSE_TRANSFER; ?> courses on your Dokeos platform ! Only <?php echo MAX_COURSE_TRANSFER; ?> courses have been updated. To update the other courses, <a href="update_courses.php?update_path=<?php echo urlencode($updatePath); ?>"><font color="red">click here</font></a>.</font>
	<?php else: ?>
	<br /><br />
	<?php endif; ?>

	<br /><br /><br /><br />

	</form>
	<form method="get" action="../../">
	<p align="right"><input type="submit" value="Go to your Dokeos portal" /></p>

<?php
}


/*
==============================================================================
		STEP 1 : CONFIGURATION
==============================================================================
*/

else
{
	?>
		<h2>Step 1 of 2 &ndash; Configuration</h2>

		Please enter the path where the older version of Dokeos is installed (<?php echo implode('&nbsp;|&nbsp;',$updateFromVersion); ?>). The courses will be moved from that location to the Dokeos path.
		<br /><br />
		<b>Notice:</b> Please run this update script only if you've just updated (incompletely) Dokeos <?php echo implode('&nbsp;|&nbsp;',$updateFromVersion); ?> to Dokeos <?php echo $dokeos_version; ?>!
		<br /><br />
	<?php
	if($badUpdatePath)
	{
		?>
			<br /><br />
			<div style="background-color:white; color:red; text-align:center; font-weight:bold;">
			Error!<br />
			Dokeos <?php echo implode('|',$updateFromVersion); ?> has not been found in that directory.
			</div>
		<?php
	}
	else
	{
		echo '<br />';
	}
	?>
		<table border="0" cellpadding="5" width="100%" align="center">
		<tr>
		<td>Where are the courses to be updated: </td>
		<td><input type="text" name="updatePath" size="50" value="<?php echo empty($update_path)?($badUpdatePath?htmlentities($_POST['updatePath']):$_SERVER['DOCUMENT_ROOT'].'/old_version/'):htmlentities($update_path); ?>" /></td>
		</tr>
		</table>

		<p align="center">
		<input type="submit" name="step2" value="Update courses" onclick="javascript:if(this.value == 'Please Wait...') return false; else this.value='Please Wait...';" />
		</p>
	<?php
}
?>

  </td>
</tr>
</table>
</form>

</body>
</html>

<?php
/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
 * this function returns a the value of a parameter from the configuration file
 *
 * @param string  $param  the parameter which the value is returned for
 * @return  string  the value of the parameter
 * @author Olivier Brouckaert
 */

function get_config_param($param)
{
	global $configFile, $updateFromConfigFile;

	if(empty($updateFromConfigFile))
	{
		if(file_exists($_POST['updatePath'].'main/include/config.inc.php.old'))
		{
			$updateFromConfigFile='main/include/config.inc.php.old';
		}
		elseif(file_exists($_POST['updatePath'].'main/inc/conf/configuration.php.old'))
		{
			$updateFromConfigFile='main/inc/conf/configuration.php.old';
		}
		else
		{
			return;
		}
	}

	if(is_array($configFile) && isset($configFile[$param]))
	{
		return $configFile[$param];
	}
	elseif(file_exists($_POST['updatePath'].$updateFromConfigFile))
	{
		$configFile=array();

		$temp=file($_POST['updatePath'].$updateFromConfigFile);

		$val='';

		foreach($temp as $enreg)
		{
			if(strstr($enreg,'='))
			{
				$enreg=explode('=',$enreg);

				if($enreg[0][0] == '$')
				{
					list($enreg[1])=explode(' //',$enreg[1]);

					$enreg[0]=trim(str_replace('$','',$enreg[0]));
					$enreg[1]=str_replace('\"','"',ereg_replace('(^"|"$)','',substr(trim($enreg[1]),0,-1)));

					if(strtolower($enreg[1]) == 'true')
					{
						$enreg[1]=1;
					}
					if(strtolower($enreg[1]) == 'false')
					{
						$enreg[1]=0;
					}
					else
					{
						$implode_string=' ';

						if(!strstr($enreg[1],'." ".') && strstr($enreg[1],'.$'))
						{
							$enreg[1]=str_replace('.$','." ".$',$enreg[1]);
							$implode_string='';
						}

						$tmp=explode('." ".',$enreg[1]);

						foreach($tmp as $tmp_key=>$tmp_val)
						{
							if(eregi('^\$[a-z_][a-z0-9_]*$',$tmp_val))
							{
								$tmp[$tmp_key]=get_config_param(str_replace('$','',$tmp_val));
							}
						}

						$enreg[1]=implode($implode_string,$tmp);
					}

					$configFile[$enreg[0]]=$enreg[1];

					if($enreg[0] == $param)
					{
						$val=$enreg[1];
					}
				}
			}
		}

		return $val;
	}
}
?>