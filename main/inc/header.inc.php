<?php
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

//Give a default value to $charset. Should change to UTF-8 some time in the future.
//This parameter should be set in the platform configuration interface in time.
if(empty($charset))
{
	$charset = 'ISO-8859-15';
}

//header('Content-Type: text/html; charset='. $charset)
//	or die ("WARNING : it remains some characters before &lt;?php bracket or after ?&gt end");

header('Content-Type: text/html; charset='. $charset);
if ( isset($httpHeadXtra) && $httpHeadXtra )
{
	foreach($httpHeadXtra as $thisHttpHead)
	{
		header($thisHttpHead);
	}
}

// Get language iso-code for this page - ignore errors
// The error ignorance is due to the non compatibility of function_exists()
// with the object syntax of Database::get_language_isocode()
@$document_language = Database::get_language_isocode($language_interface);
if(empty($document_language))
{
  //if there was no valid iso-code, use the english one
  $document_language = 'en';
}

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
if(!empty($nameTools))
{
	echo $nameTools.' - ';
}

if(!empty($_course['official_code']))
{
	echo $_course['official_code'].' - ';
}

echo get_setting('siteName');
?>
</title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
<?php
$my_style = api_get_setting('stylesheet');
$my_code_path = api_get_path(WEB_CODE_PATH);
if(empty($my_style)){$my_style = 'default';}
echo '@import "'.$my_code_path.'css/'.$my_style.'/default.css";'."\n";
echo '@import "'.$my_code_path.'css/'.$my_style.'/course.css";'."\n";
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
<link rel="courses" href="<?php echo api_get_path(WEB_CODE_PATH) ?>auth/courses.php" title="<?php echo htmlentities(get_lang('OtherCourses')); ?>" />
<link rel="profil" href="<?php echo api_get_path(WEB_CODE_PATH) ?>auth/profile.php" title="<?php echo htmlentities(get_lang('ModifyProfile')); ?>" />
<link href="http://www.dokeos.com/documentation.php" rel="Help" />
<link href="http://www.dokeos.com/team.php" rel="Author" />
<link href="http://www.dokeos.com" rel="Copyright" />
<link rel="shortcut icon" href="<?php echo api_get_path(WEB_PATH); ?>favicon.ico" type="image/x-icon" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset ?>" />
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
<body dir="<?php echo  $text_dir ?>" <?php
 if(defined('DOKEOS_HOMEPAGE') && DOKEOS_HOMEPAGE)
 echo 'onload="javascript:if(document.formLogin) { document.formLogin.login.focus(); }"'; ?>>
<div class="skip">
<ul>
<li><a href="#menu"><?php echo ( get_lang('WCAGGoMenu') )?></a></li>
<li><a href="#content" accesskey="2"><?php echo ( get_lang('WCAGGoContent') )?></a></li>
</ul>
</div>
		
<!-- #outerframe container to control some general layout of all pages -->
<div id="outerframe">

<?php
//  Banner
include(api_get_path(INCLUDE_PATH)."banner.inc.php");
?>
