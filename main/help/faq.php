<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
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

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
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
// name of the language file that needs to be included 
$language_file='help';
$helpName=$_GET['open'];
include('../inc/global.inc.php');
include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
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
@import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/public_admin/default.css";
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
<?php 
echo get_lang('Faq'); 

/*
-----------------------------------------------------------
	FAQ configuration settings
-----------------------------------------------------------
*/

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'FAQ';
//

if(api_is_platform_admin())
{
	echo '&nbsp;<a href="faq.php?edit=true"><img src="'.api_get_path(WEB_IMG_PATH).'edit.gif" /></a>';
}
?>
</h4>
<?php
$faq_file = 'faq.html';
if(!empty($_GET['edit']) && $_GET['edit']=='true' && api_is_platform_admin())
{
	$form = new FormValidator('set_faq','post','faq.php?edit=true');
	$form -> add_html_editor('faq_content',null, false);
	$form -> addElement('submit','faq_submit', get_lang('Ok'));
	$form -> setDefaults(array('faq_content'=>file_get_contents(api_get_path(SYS_PATH).'home/faq.html')));
	if($form -> validate())
	{
		$content = $form -> getSubmitValue('faq_content');
		$fpath = api_get_path(SYS_PATH).'home/'.$faq_file;
		if(is_file($fpath) && is_writeable($fpath))
		{
			$fp = fopen(api_get_path(SYS_PATH).'home/'.$faq_file,'w');
			fwrite($fp, $content);
			fclose($fp);
		}
		else
		{
			echo get_lang('WarningFaqFileNonWriteable').'<br />';
		}
		echo $content;
	}
	else
	{
		$form -> display();
	}
}
else
{
	echo file_get_contents(api_get_path(SYS_PATH).'home/'.$faq_file);	
}
?>
<div style="text-align:right;"><a href="javascript:window.close();"><?php echo get_lang('Close'); ?></a></div>
</div>
</body>
</html>