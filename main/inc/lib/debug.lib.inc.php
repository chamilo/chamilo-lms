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
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
* This is the debug library for Dokeos.
* Include/require it in your code to use its functionality.
*
* debug functions
*
* All these function only display output only when debugClaro is on
*
* function echoSessionValue()
function debugIO($file="")
*
* @package dokeos.library
============================================================================== 
*/

/**
 * function echoSessionValue()
 *
 * @desc print out  content of session's variable
 *
 * @return
 * @authot Christophe Gesché gesché@ipm.ucl.ac.be
 * @deprecated Function not in use
 *
 */
function echoSessionValue()
{
	$infoResult = "";

	global $statuts, $statut, $status, $dbHost, $dbLogin, $dbPass, $is_admin, $_GET, $_SESSION, $_POST;

	if (!isset ($is_admin) || !$is_admin)
	{
		exit ("pwet");
	}

	$infoResult .= "
			<HR>
			<a href=\"../main/admin/phpInfo.php\">phpInfo Dokeos</a>
			<PRE>";
	$infoResult .= "<strong>PHP Version</strong> : ".phpversion()."
			<strong>nivo d'err</strong> : ".error_reporting(2039);
	if (isset ($statuts))
	{
		$infoResult .= "
					<strong>statut</strong> : ";
		print_r($statuts);
	}
	if (isset ($statut))
	{
		$infoResult .= "
					<strong>statut</strong> : ";
		print_r($statut);
	}
	if (isset ($status))
	{
		$infoResult .= "
					<strong>status</strong> : ";
		print_r($status);
	}

	if (isset ($dbHost) || isset ($dbLogin))
	{
		$infoResult .= "
					<strong>mysql param</strong> :
					 Serveur : $dbHost
					 User    : $dbLogin";
	}
	if (isset ($_SESSION))
	{
		$infoResult .= "
					<strong>session</strong> : ";
		print_r($_SESSION);
	}
	if (isset ($_POST))
	{
		$infoResult .= "
					<strong>Post</strong> : ";
		print_r($_POST);
	}
	if (isset ($_GET))
	{
		$infoResult .= "
					<strong>GET</strong> : ";
		print_r($_GET);
	}

	$infoResult .= "
			<strong>Contantes</strong> : ";
	print_r(get_defined_constants());
	get_current_user();
	$infoResult .= "
			<strong>Fichiers inclus</strong> : ";
	print_r(get_included_files());
	$infoResult .= "
			<strong>Magic quote gpc</strong> : ".get_magic_quotes_gpc()."
			<strong>Magig quote runtime</strong> : ".get_magic_quotes_runtime()."
			<strong>date de dernière modification de la page</strong> : ".date("j-m-Y", getlastmod());
	/*
	get_cfg_var -- Retourne la valeur d'une option de PHP
	getenv -- Retourne la valeur de la variable d'environnement.
	ini_alter -- Change la valeur d'une option de configuration
	ini_get -- Lit la valeur d'une option de configuration.
	ini_get_all -- Lit toutes les valeurs de configuration
	ini_restore -- Restaure la valeur de l'option de configuration
	ini_set -- Change la valeur d'une option de configuration
	putenv -- Fixe la valeur d'une variable d'environnement.
	set_magic_quotes_runtime --  Active/désactive l'option magic_quotes_runtime.
	set_time_limit -- Fixe le temps maximum d'exécution d'un script.
	*/
	$infoResult .= "
			<strong>Type d'interface utilisé entre le serveur web et PHP</strong> : ".php_sapi_name()."
			<strong>informations OS</strong> : ".php_uname()."
			<strong>Version courante du moteur Zend</strong> : ".zend_version()."
			<strong>GID du propriétaire du script</strong> : ".getmygid()."
			<strong>inode du script</strong> : ".getmyinode()."
			<strong>numéro de processus courant</strong> : ".getmypid()."
			<strong>UID du propriétaire du script actuel</strong> : ".getmyuid()."
			<strong>niveau d'utilisation des ressources</strong> : ";
	print_r(@ getrusage());

	$infoResult .= "
			</PRE>
			<HR>
				";
	if (PRINT_DEBUG_INFO)
		echo $infoResult;
	return $infoResult;
}

/**
 * function debugIO($file="")
 *
 * @desc io file
 * @return
 * @author Christophe Gesché gesché@ipm.ucl.ac.be
 * @deprecated Function not in use
 */

function debugIO($file = "")
{
	GLOBAL $SERVER_SOFTWARE;

	$infoResult = "
		[Script :  ".$_SERVER['PHP_SELF']."]
		[Server :  ".$SERVER_SOFTWARE."]
		[Php :  ".phpversion()."]
		[sys :  ".php_uname()."]
		[My uid : ".getmyuid()."]
		[current_user : ".get_current_user()."]
		[my gid : ".getmygid()."]
		[my inode : ".getmyinode()."]
		[my pid : ".getmypid()."]
		[space  : - free -  : ".disk_free_space('..')."
		 - total - : ".disk_total_space('..')."
		]";

	if ($file != "")
	{
		$infoResult .= "<HR> <strong>".$file."</strong> -
						[<strong>o</strong>:".fileowner($file)." <strong>g</strong>:".filegroup($file)." ".display_perms(fileperms($file))."]";
		if (is_dir($file))
			$infoResult .= "-Dir-";
		if (is_file($file))
			$infoResult .= "-File-";
		if (is_link($file))
			$infoResult .= "-Lnk-";
		if (is_executable($file))
			$infoResult .= "-X-";
		if (is_readable($file))
			$infoResult .= "-R-";
		if (is_writeable($file))
			$infoResult .= "-W-";
	}

	$file = ".";
	$infoResult .= "<HR> <strong>".$file."</strong> -
			[<strong>o</strong>:".fileowner($file)." <strong>g</strong>:".filegroup($file)." ".display_perms(fileperms($file))."]";
	if (is_dir($file))
		$infoResult .= "-Dir-";
	if (is_file($file))
		$infoResult .= "-File-";
	if (is_link($file))
		echo "-Lnk-";
	if (is_executable($file))
		echo "-X-";
	if (is_readable($file))
		echo "-R-";
	if (is_writeable($file))
		echo "-W-";

	$file = "..";
	echo "<HR> <strong>".$file."</strong> -
			[<strong>o</strong>:".fileowner($file)." <strong>g</strong>:".filegroup($file)." ".display_perms(fileperms($file))."]";
	if (is_dir($file))
		$infoResult .= "-Dir-";
	if (is_file($file))
		$infoResult .= "-File-";
	if (is_link($file))
		$infoResult .= "-Lnk-";
	if (is_executable($file))
		$infoResult .= "-X-";
	if (is_readable($file))
		$infoResult .= "-R-";
	if (is_writeable($file))
		$infoResult .= "-W-";

	if (PRINT_DEBUG_INFO)
		echo $infoResult;
	return $infoResult;

}
/**
 * @deprecated Function only used in deprecated function debugIO
 */
function display_perms($mode)
{
	/* Determine Type */
	if ($mode & 0x1000)
		$type = 'p'; /* FIFO pipe */
	else
		if ($mode & 0x2000)
			$type = 'c'; /* Character special */
	else
		if ($mode & 0x4000)
			$type = 'd'; /* Directory */
	else
		if ($mode & 0x6000)
			$type = 'b'; /* Block special */
	else
		if ($mode & 0x8000)
			$type = '-'; /* Regular */
	else
		if ($mode & 0xA000)
			$type = 'l'; /* Symbolic Link */
	else
		if ($mode & 0xC000)
			$type = 's'; /* Socket */
	else
		$type = 'u'; /* UNKNOWN */

	/* Determine permissions */
	$owner["read"] = ($mode & 00400) ? 'r' : '-';
	$owner["write"] = ($mode & 00200) ? 'w' : '-';
	$owner["execute"] = ($mode & 00100) ? 'x' : '-';
	$group["read"] = ($mode & 00040) ? 'r' : '-';
	$group["write"] = ($mode & 00020) ? 'w' : '-';
	$group["execute"] = ($mode & 00010) ? 'x' : '-';
	$world["read"] = ($mode & 00004) ? 'r' : '-';
	$world["write"] = ($mode & 00002) ? 'w' : '-';
	$world["execute"] = ($mode & 00001) ? 'x' : '-';

	/* Adjust for SUID, SGID and sticky bit */
	if ($mode & 0x800)
		$owner["execute"] = ($owner[execute] == 'x') ? 's' : 'S';
	if ($mode & 0x400)
		$group["execute"] = ($group[execute] == 'x') ? 's' : 'S';
	if ($mode & 0x200)
		$world["execute"] = ($world[execute] == 'x') ? 't' : 'T';

	$strPerms = "<strong>t</strong>:".$type."<strong>o</strong>:".$owner[read].$owner[write].$owner[execute]."<strong>g</strong>:".$group[read].$group[write].$group[execute]."<strong>w</strong>:".$world[read].$world[write].$world[execute];
	return $strPerms;
}

function printVar($var, $varName = "@")
{
	GLOBAL $DEBUG;
	if ($DEBUG)
	{
		echo "<blockquote>\n";
		echo "<b>[$varName]</b>";
		echo "<hr noshade size=\"1\" style=\"color:blue\">";
		echo "<pre style=\"color:red\">\n";
		var_dump($var);
		echo "</pre>\n";
		echo "<hr noshade size=\"1\" style=\"color:blue\">";
		echo "</blockquote>\n";
	}
	else
	{
		echo "<!-- DEBUG is OFF -->";
		echo "DEBUG is OFF";
	}
}
/**
 * @deprecated Function not in use
 */
function printInit($selection = "*")
{
	GLOBAL $uidReset, $cidReset, $gidReset, $uidReq, $cidReq, $gidReq, $_uid, $_cid, $_gid, $_user, $_course, $is_platformAdmin, $is_allowedCreateCourse, $is_courseMember, $is_courseAdmin, $is_allowed_in_course, $is_courseTutor, $_SESSION, $_claro_local_run;

	if ($_claro_local_run)
	{
		echo "local init ran";
	}
	else
	{
		echo "<font color=\"red\">local init never ran during this script</font>";
	}
	echo "
			<table width=\"100%\" border=\"1\" cellspacing=\"4\" cellpadding=\"1\" bordercolor=\"#808080\" bgcolor=\"#C0C0C0\" lang=\"en\"><TR>";
	if ($selection == "*" or strstr($selection, "u"))
	{
		echo "<TD valign=\"top\" >USER :
						(uid):  ".$uid." |
						(_uid):  ".$_uid." |
						(session[_uid]):  ".$_SESSION['_uid']."
						<PRE>
						reset = ".$uidReset." | req = ".$uidReq."<br>
						_user : ";
		var_dump($_user);
		echo "is_platformAdmin:";
		var_dump($is_platformAdmin);
		echo "is_allowedCreateCourse:";
		var_dump($is_allowedCreateCourse);
		echo "</PRE></TD>";
	}
	if ($selection == "*" or strstr($selection, "c"))
	{
		echo "<TD valign=\"top\" >COURSE :(_cid)".$_cid."<PRE>
						reset = ".$cidReset." | req = ".$cidReq."<br>
						";
		echo "_course : ";
		var_dump($_course);
		echo "</PRE></TD>";
	}
	if ($selection == "*" or strstr($selection, "g"))
	{
		echo "<TD valign=\"top\" >GROUP :".$_gid."<PRE>
						reset = ".$gidReset." | req = ".$gidReq."<br>
						";
		echo "</PRE></TD>";
	}
	echo "</TR><TR>";
	if ($selection == "*" or (strstr($selection, "u") && strstr($selection, "c")))
	{
		echo "<TD valign=\"top\" colspan=2>USER :".$_uid." in ".$_cid."<PRE>";
		echo "_courseUser:";
		var_dump($_courseUser);
		echo "is_courseMember:";
		var_dump($is_courseMember);
		echo "is_courseAdmin:";
		var_dump($is_courseAdmin);
		echo "is_allowed_in_course:";
		var_dump($is_allowed_in_course);
		echo "is_courseTutor:";
		var_dump($is_courseTutor);
		echo "</PRE></TD><TD></TD>";
	}
	echo "</TR><TR>";
	if ($selection == "*" or (strstr($selection, "u") && strstr($selection, "g")))
	{

		echo "<td></td><TD valign=\"top\"  colspan=2>USER :".$_uid." in ".$_gid."<PRE>";
		echo "</PRE></TD>";
	}
	echo "</TR></TABLE>";
}
/**
 * @deprecated Function not in use
 */
function printConfig()
{
	GLOBAL $dbHost, $dbLogin, $dbPass, $mainDbName, $dokeos_version, $rootWeb, $urlAppend, $userPasswordCrypted, $userPasswordCrypted, $platformLanguage, $siteName, $rootWeb, $rootSys, $clarolineRepositoryAppend, $coursesRepositoryAppend, $rootAdminAppend, $clarolineRepositoryWeb, $clarolineRepositorySys, $coursesRepositoryWeb, $coursesRepositorySys, $rootAdminSys, $rootAdminWeb;
	echo "<table width=\"100%\" border=\"1\" cellspacing=\"1\" cellpadding=\"1\" bordercolor=\"#808080\" bgcolor=\"#C0C0C0\" lang=\"en\"><TR>";
	echo "
			<tr><td colspan=2><strong>Mysql</strong></td></tr>
			<tr><td>dbHost</TD><TD>$dbHost 			</td></tr>
			<tr><td>dbLogin 	</TD><TD>$dbLogin 			</td></tr>
			<tr><td>dbPass	</TD><TD>".str_repeat("*", strlen($dbPass))."</td></tr>
			<tr><td>mainDbName		</TD><TD>$mainDbName			</td></tr>
			<tr><td>clarolineVersion	</TD><TD>$dokeos_version</td></tr>
		    <tr><td>rootWeb</TD><TD>$rootWeb</td></tr>
			<tr><td>urlAppend </TD><TD>$urlAppend</td></tr>
			<tr><td colspan=2><HR></td></tr>
			<tr><td colspan=2><strong>param for new and future features</strong></td></tr>
			<tr><td>userPasswordCrypted 			</TD><TD>$userPasswordCrypted 			</td></tr>
			<tr><td colspan=2></td></tr>
			<tr><td>platformLanguage 	</TD><TD>$platformLanguage 	</td></tr>
			<tr><td>siteName			</TD><TD>$siteName			</td></tr>
			<tr><td>rootWeb			</TD><TD>$rootWeb			</td></tr>
			<tr><td>rootSys			</TD><TD>$rootSys			</td></tr>
			<tr><td colspan=2></td></tr>
			<tr><td>clarolineRepository<strong>Append</strong>  	</TD><TD>$clarolineRepositoryAppend </td></tr>
			<tr><td>coursesRepository<strong>Append</strong>		</TD><TD>$coursesRepositoryAppend	</td></tr>
			<tr><td>rootAdmin<strong>Append</strong>				</TD><TD>$rootAdminAppend			</td></tr>
			<tr><td colspan=2></td></tr>
			<tr><td>clarolineRepository<strong>Web</strong>	</TD><TD>$clarolineRepositoryWeb 	</td></tr>
			<tr><td>clarolineRepository<strong>Sys</strong>	</TD><TD>$clarolineRepositorySys		</td></tr>
			<tr><td>coursesRepository<strong>Web</strong>	</TD><TD>$coursesRepositoryWeb		</td></tr>
			<tr><td>coursesRepository<strong>Sys</strong>	</TD><TD>$coursesRepositorySys		</td></tr>
			<tr><td>rootAdmin<strong>Sys</strong>			</TD><TD>$rootAdminSys				</td></tr>
			<tr><td>rootAdmin<strong>Web</strong>			</TD><TD>$rootAdminWeb				</td></tr>
						";
	echo "</TABLE>";
}
?>