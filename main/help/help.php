<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003-2005 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	Copyright (c) Sally "Example" Programmer (sally@somewhere.net)

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
*	This script displays a help window.
*
*	@package dokeos.help
==============================================================================
*/
$langFile='help';
$helpName=$_GET['open'];
include('../inc/global.inc.php');
$language_code = Database::get_language_isocode($language_interface);
header('Content-Type: text/html; charset='. $charset);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $language_code; ?>" lang="<?php echo $language_code; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset ?>" />
<title>
<?php echo get_lang('H'.$helpName); ?>
</title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/default/default.css";
/*]]>*/
</style>
<?php
if(api_get_setting('stylesheets')<>'')
{
	?>
	<style type="text/css" media="screen, projection">
	/*<![CDATA[*/
	@import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/<?php echo api_get_setting('stylesheets');?>/default.css";
	/*]]>*/
	</style>
	<?php
}
?>
</head>
<body>
<div style="margin:10px;">
<div style="text-align:right;"><a href="javascript:window.close();"><?php echo get_lang('Close'); ?></a></div>
<h4>
<?php echo get_lang('H'.$helpName); ?>
</h4>
<?php echo get_lang($helpName.'Content'); ?>
<div style="text-align:right;"><a href="javascript:window.close();"><?php echo get_lang('Close'); ?></a></div>
</div>
</body>
</html>