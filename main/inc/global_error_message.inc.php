<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 *==============================================================================
 * This script displays error messages on fatal errors
 * during initialization.
 *
 * @package dokeos.include
 * @author Ivan Tcholakov, 2009
 *==============================================================================
 */

$Organisation = '<a href="http://www.dokeos.com" target="_blank">Dokeos Homepage</a>';
$PoweredBy = 'Platform <a href="http://www.dokeos.com" target="_blank"> Dokeos </a> &copy; 2009';

/**
 * English language variables.
 */

// Sections.
$SectionSystemRequirementsProblem = 'System requirements problem';
$SectionInstallation = 'Installation';
$SectionDatabaseUnavailable = 'Database is unavailable';
$SectionTechnicalIssues = 'Technical issues';

// Error code.
$ErrorCode = 'Error code';

// Error code 1.
$IncorrectPhpVersionTitle = 'Incorrect PHP version';
$IncorrectPhpVersionDescription = 'Scripting language version %s1 on your server is incorrect. PHP %s2 should be supported. %s3 Read the installation guide.';

// Error code 2.
$InstallationTitle = 'Dokeos has not been installed';
$InstallationDescription = 'Click to INSTALL Dokeos %s or read the installation guide';

// Error code 3.
// Error code 4.
// Error code 5.
$DatabaseUnavailableTitle = 'Database is unavailable';
$DatabaseUnavailableDescription = 'This portal is currently experiencing database issues. Please report this to the portal administrator. Thank you for your help.';

// Unspecified error.
$TechnicalIssuesTitle = 'Technical issues';
$TechnicalIssuesDescription = 'This portal is currently experiencing technical issues. Please report this to the portal administrator. Thank you for your help.';

if (is_int($global_error_code) && $global_error_code > 0) {

	$theme = 'dokeos_blue/';
	$css_path = 'main/css/';
	$css_file = $css_path.$theme.'default.css';

	$root_sys = str_replace('\\', '/', realpath(dirname(__FILE__).'/../../')).'/';
	$root_rel = htmlentities($_SERVER['PHP_SELF']);
	if (!empty($root_rel)) {
		$pos = strrpos($root_rel, '/');
		$root_rel = substr($root_rel, 0, $pos - strlen($root_rel) + 1);
		if (strpos($root_rel, '/main/') !== false) {
			$pos = 0;
			while (($test_pos = strpos(substr($root_rel, $pos, strlen($root_rel)), '/main/')) !== false) {
				$pos = $test_pos + 1;
			}
			$root_rel = substr($root_rel, 0, $pos);
		}
		elseif (strpos($root_rel, '/courses/') !== false) {
			$pos = 0;
			while (($test_pos = strpos(substr($root_rel, $pos, strlen($root_rel)), '/courses/')) !== false) {
				$pos = $test_pos + 1;
			}
			$root_rel = substr($root_rel, 0, $pos);
		}
	}

	$css_file = $root_sys.$css_file;
	if (file_exists($css_file)) {
		$css_def = @file_get_contents($css_file);
	} else {
		$css_def = '';
	}

	$css_def = str_replace('behavior:url("/main/css/csshover3.htc");', '', $css_def);
	$css_def = str_replace('main/', $root_rel.'main/', $css_def);
	$css_def = str_replace('images/', $root_rel.$css_path.$theme.'images/', $css_def);
	$css_def = str_replace('../../img/', $root_rel.'main/img/', $css_def);

	$global_error_message = array();

	switch ($global_error_code) {

		case 1:
			$global_error_message['section'] = $SectionSystemRequirementsProblem;
			$global_error_message['title'] = $IncorrectPhpVersionTitle;
			$php_version = function_exists('phpversion') ? phpversion() : (defined('PHP_VERSION') ? PHP_VERSION : '');
			$php_version = empty($php_version) ? '' : '(PHP '.$php_version.')';
			$IncorrectPhpVersionDescription = str_replace('%s1', $php_version, $IncorrectPhpVersionDescription);
			$IncorrectPhpVersionDescription = str_replace('%s2', REQUIRED_PHP_VERSION, $IncorrectPhpVersionDescription);
			$pos = strpos($IncorrectPhpVersionDescription, '%s3');
			if ($pos !== false) {
				$length = strlen($IncorrectPhpVersionDescription);
				$read_installation_guide = substr($IncorrectPhpVersionDescription, $pos + 3, $length);
				$IncorrectPhpVersionDescription = substr($IncorrectPhpVersionDescription, 0, $pos);
				$IncorrectPhpVersionDescription .= '<br /><a href="documentation/installation_guide.html" target="_blank">'.$read_installation_guide.'</a>';
			}
			$global_error_message['description'] = $IncorrectPhpVersionDescription;
			break;

		case 2:
			$global_error_message['section'] = $SectionInstallation;
			$global_error_message['title'] = $InstallationTitle;
			if (($pos = strpos($InstallationDescription, '%s')) === false) {
				$InstallationDescription = 'Click to INSTALL Dokeos %s or read the installation guide';
			}
			$click_to_install = substr($InstallationDescription, 0, $pos);
			$read_installation_guide = substr($InstallationDescription, $pos + 2);
			$InstallationDescription = '<form action="main/install/index.php" method="get"><button class="save" type="submit" value="&nbsp;&nbsp; '.$click_to_install.' &nbsp;&nbsp;" >'.$click_to_install.'</button></form><br />
					<a href="documentation/installation_guide.html" target="_blank">'.$read_installation_guide.'</a>';
			$global_error_message['description'] = $InstallationDescription;
			break;

		case 3:
		case 4:
		case 5:
			$global_error_message['section'] = $SectionDatabaseUnavailable;
			$global_error_message['title'] = $DatabaseUnavailableTitle;
			$global_error_message['description'] = $DatabaseUnavailableDescription;
			break;

		default:
			$global_error_message['section'] = $SectionTechnicalIssues;
			$global_error_message['title'] = $TechnicalIssuesTitle;
			$global_error_message['description'] = $TechnicalIssuesDescription;
			break;
	}

	$show_error_codes = defined('SHOW_ERROR_CODES') && SHOW_ERROR_CODES && $global_error_code != 2;
	$global_error_message['code'] = $show_error_codes ? $ErrorCode.': '.$global_error_code.'<br /><br />' : '';
	$global_error_message['details'] = empty($global_error_message['details']) ? '' : ($show_error_codes ? ': '.$global_error_message['details'] : $global_error_message['details']);

	$global_error_message['organisation'] = $Organisation;
	$global_error_message['powered_by'] = $PoweredBy;

	$global_error_message['encoding'] = 'UTF-8';
	$global_error_message['css'] = $css_def;

	$global_error_message_page =
<<<EOM
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
		<head>
			<title>{TITLE}</title>
			<meta http-equiv="Content-Type" content="text/html; charset={ENCODING}" />
			<style type="text/css" media="screen, projection">
				/*<![CDATA[*/
				{CSS}
				/*]]>*/
			</style>
		</head>
		<body>
			<div id="header">
				<div id="header1">{ORGANISATION}</div>
				<div class="clear"></div>
				<div id="header2">&nbsp;</div>
				<div id="header3">
					<ul id="logout">
						<li><a href="" target="_top"><span>&nbsp;</span></a></li>
					</ul>
					<ul>
						<li id="current"><a href="#"><span>{SECTION}</span></a></li>
					</ul>
					<div style="clear: both;" class="clear"></div>
				</div>
				<div id="header4">&nbsp;</div>
			</div>

			<div style="text-align: center;">
					<br /><br />{DESCRIPTION}<br /><br />
					{CODE}
			</div>

			<div id="footer">
				<div class="copyright">{POWERED_BY}</div>
				&nbsp;
			</div>
		</body>
</html>
EOM;

	foreach ($global_error_message as $key => $value) {
		$global_error_message_page = str_replace('{'.strtoupper($key).'}', $value, $global_error_message_page);
	}

	header('Content-Type: text/html; charset='.$global_error_message['encoding']);
	die($global_error_message_page);
}
