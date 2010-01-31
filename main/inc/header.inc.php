<?php
/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	This script displays the Dokeos header.
*
*	@package dokeos.include
==============================================================================
*/

/*----------------------------------------
              HEADERS SECTION
  --------------------------------------*/

/*
 * HTTP HEADER
 */

header('Content-Type: text/html; charset='.api_get_system_encoding());

$navigator_info = api_get_navigator();
//ie6 fix
if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
	$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/iepngfix/iepngfix_tilebg.js" type="text/javascript" language="javascript"></script>'; //jQuery
}

if ( isset($httpHeadXtra) && $httpHeadXtra )
{
	foreach($httpHeadXtra as $thisHttpHead)
	{
		header($thisHttpHead);
	}
}

// Get language iso-code for this page - ignore errors
$document_language = api_get_language_isocode();

/*
 * HTML HEADER
 */

?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $document_language; ?>" lang="<?php echo $document_language; ?>">
<head>
<title>
<?php
if(!empty($nameTools)) {
	echo $nameTools.' - ';
}

if(!empty($_course['official_code'])) {
	echo $_course['official_code'].' - ';
}

echo api_get_setting('siteName');

?>
</title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
<?php

$platform_theme= api_get_setting('stylesheets'); 	// plataform's css
$my_style=$platform_theme;

if(api_get_setting('user_selected_theme') == 'true')
{
	$useri = api_get_user_info();
	$user_theme = $useri['theme'];
	if(!empty($user_theme) && $user_theme != $my_style)
	{
		$my_style = $user_theme;					// user's css
	}
}
$mycourseid = api_get_course_id();
if (!empty($mycourseid) && $mycourseid != -1) {
	if (api_get_setting('allow_course_theme') == 'true')
	{
		$mycoursetheme=api_get_course_setting('course_theme');

		if (!empty($mycoursetheme) && $mycoursetheme!=-1)
		{
			if(!empty($mycoursetheme) && $mycoursetheme != $my_style)
			{
				$my_style = $mycoursetheme;		// course's css
			}
		}

		$mycourselptheme=api_get_course_setting('allow_learning_path_theme');
		if (!empty($mycourselptheme) && $mycourselptheme!=-1 && $mycourselptheme== 1)
		{

			global $lp_theme_css; //  it comes from the lp_controller.php
			global $lp_theme_config; // it comes from the lp_controller.php

			if (!$lp_theme_config)
			{
				if ($lp_theme_css!='')
				{
					$theme=$lp_theme_css;
					if(!empty($theme) && $theme != $my_style)
					{
						$my_style = $theme;	 // LP's css
					}
				}
			}
		}
	}
}

global $show_learn_path;

if ($show_learn_path) {
	$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/learnpath.css"/>';
	$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="dtree.css" />'; //will be moved
	$htmlHeadXtra[] = '<script src="dtree.js" type="text/javascript"></script>'; //will be moved
}

$my_code_path = api_get_path(WEB_CODE_PATH);
if(empty($my_style)) {
	$my_style = 'dokeos_classic';
}
echo '@import "'.$my_code_path.'css/'.$my_style.'/default.css";'."\n";
echo '@import "'.$my_code_path.'css/'.$my_style.'/course.css";'."\n";

if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
	echo 'img, div { behavior: url('.api_get_path(WEB_LIBRARY_PATH).'javascript/iepngfix/iepngfix.htc) } ';
}

?>
/*]]>*/
</style>
<style type="text/css" media="print">
/*<![CDATA[*/
<?php
  echo '@import "'.$my_code_path.'css/'.$my_style.'/print.css";'."\n";

?>
/*]]>*/
</style>

<link rel="top" href="<?php echo api_get_path(WEB_PATH); ?>index.php" title="" />
<link rel="courses" href="<?php echo api_get_path(WEB_CODE_PATH) ?>auth/courses.php" title="<?php echo api_htmlentities(get_lang('OtherCourses'),ENT_QUOTES,$charset); ?>" />
<link rel="profil" href="<?php echo api_get_path(WEB_CODE_PATH) ?>auth/profile.php" title="<?php echo api_htmlentities(get_lang('ModifyProfile'),ENT_QUOTES,$charset); ?>" />
<link href="http://www.chamilo.org/documentation.php" rel="Help" />
<link href="http://www.chamilo.org/team.php" rel="Author" />
<link href="http://www.chamilo.org" rel="Copyright" />
<link rel="shortcut icon" href="<?php echo api_get_path(WEB_PATH); ?>favicon.ico" type="image/x-icon" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />

<script type="text/javascript">
//<![CDATA[
// This is a patch for the "__flash__removeCallback" bug, see FS#4378.
if ( ( navigator.userAgent.toLowerCase().indexOf('msie') != -1 ) && ( navigator.userAgent.toLowerCase().indexOf( 'opera' ) == -1 ) )
{
	window.attachEvent( 'onunload', function()
		{
			window['__flash__removeCallback'] = function ( instance, name )
			{
				try
				{
					if ( instance )
					{
						instance[name] = null ;
					}
				}
				catch ( flashEx )
				{

				}
			} ;
		}
	) ;
}
//]]>
</script>

<?php
if ( isset($htmlHeadXtra) && $htmlHeadXtra )
{
	foreach($htmlHeadXtra as $this_html_head)
	{
		echo($this_html_head);
	}
}
if ( isset($htmlIncHeadXtra) && $htmlIncHeadXtra )
{
	foreach($htmlIncHeadXtra as $this_html_head)
	{
		include($this_html_head);
	}
}
//the following include might be subject to a setting proper to the course or platform
include(api_get_path(LIBRARY_PATH).'/javascript/email_links.lib.js.php');
?>

</head>
<body dir="<?php echo api_get_text_direction(); ?>" <?php
 if(defined('DOKEOS_HOMEPAGE') && DOKEOS_HOMEPAGE)
 echo 'onload="javascript:if(document.formLogin) { document.formLogin.login.focus(); }"'; ?>>
<div class="skip">
<ul>
<li><a href="#menu"><?php echo ( get_lang('WCAGGoMenu') )?></a></li>
<li><a href="#content" accesskey="2"><?php echo ( get_lang('WCAGGoContent') )?></a></li>
</ul>
</div>

<?php
//  Banner
require_once api_get_path(INCLUDE_PATH).'banner.inc.php';
