<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays the Chamilo header.
 *
 *	@package chamilo.include
 */

/*	HEADERS SECTION */

/*
 * HTTP HEADER
 */

// Server mode indicator.
if (api_is_platform_admin()) {
	if (api_get_setting('server_type') == 'test') {
   		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$starttime = $mtime;
		$_SESSION['page_start_time_execution'] = $starttime;
	}
}

header('Content-Type: text/html; charset='.api_get_system_encoding());

$navigator_info = api_get_navigator();
//ie6 fix
if ($navigator_info['name'] == 'Internet Explorer' &&  $navigator_info['version'] == '6') {
	$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/iepngfix/iepngfix_tilebg.js" type="text/javascript" language="javascript"></script>';
}

if (isset($httpHeadXtra) && $httpHeadXtra) {
	foreach ($httpHeadXtra as & $thisHttpHead) {
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

$title_list[] = api_get_setting('siteName');
$title_list[] = $nameTools;
$title_list[] = $_course['official_code'];

$title_string = '';
for($i=0; $i<count($title_list);$i++) {
    if (!empty($title_list[$i])) {
    	$title_string .=$title_list[$i];
        
        if (isset($title_list[$i+1])) {
            $title_string .=' - ';
        }
    }
}
echo $title_string;


?>
</title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
<?php

$platform_theme = api_get_setting('stylesheets');
$my_style = api_get_visual_theme();

global $show_learn_path;

if ($show_learn_path) {
	$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CSS_PATH).$my_style.'/learnpath.css"/>';
	$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="dtree.css" />'; //will be moved
	$htmlHeadXtra[] = '<script src="dtree.js" type="text/javascript"></script>'; //will be moved
	}

echo '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/default.css";'."\n";
echo '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/course.css";'."\n";

if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
	echo 'img, div { behavior: url('.api_get_path(WEB_LIBRARY_PATH).'javascript/iepngfix/iepngfix.htc) } ';
}

?>
/*]]>*/
</style>
<style type="text/css" media="print">
/*<![CDATA[*/
<?php
  echo '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/print.css";'."\n";

?>
/*]]>*/
</style>
<script src="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/jquery.js" type="text/javascript" ></script>
<script src="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/thickbox.js" type="text/javascript" ></script>
<link rel="stylesheet" href="<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/thickbox.css" type="text/css" media="projection, screen" />
<link rel="top" href="<?php echo api_get_path(WEB_PATH); ?>index.php" title="" />
<link rel="courses" href="<?php echo api_get_path(WEB_CODE_PATH); ?>auth/courses.php" title="<?php echo api_htmlentities(get_lang('OtherCourses'), ENT_QUOTES); ?>" />
<link rel="profil" href="<?php echo api_get_path(WEB_CODE_PATH); ?>auth/profile.php" title="<?php echo api_htmlentities(get_lang('ModifyProfile'), ENT_QUOTES); ?>" />
<link href="http://www.chamilo.org/documentation.php" rel="Help" />
<link href="http://www.chamilo.org/team.php" rel="Author" />
<link href="http://www.chamilo.org" rel="Copyright" />
<link rel="shortcut icon" href="<?php echo api_get_path(WEB_PATH); ?>favicon.ico" type="image/x-icon" />

<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<script src= "<?php echo api_get_path(WEB_LIBRARY_PATH);?>javascript/mmenu.js" type="text/javascript"></script>

<?php if (!empty($help)) { ?>
	<script type="text/javascript">
		//One global variable to set, use true if you want the menus to reinit when the user changes text size (recommended):
		menu[3] = { 
		id:'menu-slide-right', //use unique quoted id (quoted) REQUIRED!!
		bartext: '<img src="/main/img/help.png">',
		menupos:'right',
		kviewtype:'fixed', 
		menuItems:[ // REQUIRED!!
			//[name, link, target, colspan, endrow?] - leave 'link' and 'target' blank to make a header
			["Help", "<?php echo api_get_path(WEB_CODE_PATH); ?>help/help.php?open=Home&height=400&width=600", "", "thickbox"],	
		]}; // REQUIRED!! do not edit or remove
		make_menus();
	</script>
<?php } ?>

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
if (isset($htmlHeadXtra) && $htmlHeadXtra) {
	foreach ($htmlHeadXtra as & $this_html_head) {
		echo $this_html_head;
	}
}
if (isset($htmlIncHeadXtra) && $htmlIncHeadXtra) {
	foreach ($htmlIncHeadXtra as & $this_html_head) {
		include($this_html_head);
	}
}
// The following include might be subject to a setting proper to the course or platform.
include api_get_path(LIBRARY_PATH).'javascript/email_links.lib.js.php';
?>

</head>
<body dir="<?php echo api_get_text_direction(); ?>" <?php
 if (defined('DOKEOS_HOMEPAGE') && DOKEOS_HOMEPAGE)
 echo 'onload="javascript: if(document.formLogin) { document.formLogin.login.focus(); }"'; ?>>
<div class="skip">
<ul>
<li><a href="#menu"><?php echo get_lang('WCAGGoMenu'); ?></a></li>
<li><a href="#content" accesskey="2"><?php echo get_lang('WCAGGoContent'); ?></a></li>
</ul>
</div>
<?php
// Banner
require_once api_get_path(INCLUDE_PATH).'banner.inc.php';
