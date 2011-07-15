<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays the Dokeos header up to the </head> tag
 *   IT IS A COPY OF header.inc.php EXCEPT that it doesn't start the body
 *   output.
 *
 *	@package chamilo.include
 */

/*  HEADERS SECTION */

/*
 * HTTP HEADER
 */

header('Content-Type: text/html; charset='.api_get_system_encoding());

// Include here the script ASCIIMathML.js if you want to show mathematical formulas and graphics
// not only in the "Documents" tool, but elsewhere in the system. This setting is related to the
// online editor's plugins 'asciimath' and 'asciisvg'.
if (api_get_setting('include_asciimathml_script') == 'true') {
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'asciimath/ASCIIMathML.js" type="text/javascript"></script>';
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

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $document_language; ?>" lang="<?php echo $document_language; ?>">
<head>
<title>
<?php
if (!empty($nameTools)) {
    echo $nameTools.' - ';
}

if (!empty($_course['official_code'])) {
    echo $_course['official_code'].' - ';
}

echo api_get_setting('siteName');
?>
</title>

<?php

/*
 * Choose CSS style platform's, user's, course's, or Learning path CSS
 */

$platform_theme = api_get_setting('stylesheets');
$my_style = api_get_visual_theme();

// Sets the css reference it is call from lp_nav.php, lp_toc.php, lp_message, lp_log.php
if (!empty($scorm_css_header)) {
    if (!empty($my_style)) {
        $scorm_css = api_get_path(WEB_CSS_PATH).$my_style.'/scorm.css';
        $scormfs_css = api_get_path(WEB_CSS_PATH).$my_style.'/scormfs.css';
    } else {
        $scorm_css = 'scorm.css';
        $scormfs_css = 'scormfs.css';
    }

    if (!empty($display_mode) && $display_mode == 'fullscreen') {
        $htmlHeadXtra[] = '<style type="text/css" media="screen, projection">
                            /*<![CDATA[*/
                            @import "'.$scormfs_css.'";
                            /*]]>*/
                            </style>';
    } else {
        $htmlHeadXtra[] = '<style type="text/css" media="screen, projection">
                            /*<![CDATA[*/
                            @import "'.$scorm_css.'";
                            /*]]>*/
                            </style>';
    }
}


if ($my_style != '') {
?>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
<?php 
//Base CSS
echo '@import "'.api_get_path(WEB_CSS_PATH).'base.css";';

//Global chamilo CSS
if (in_array(api_get_visual_theme(), array('chamilo','chamilo_red','chamilo_blue','chamilo_orange','chamilo_green','chamilo_electric_blue'))) {
    echo '@import "'.api_get_path(WEB_CSS_PATH).'base_chamilo.css";';
} else {
    echo '@import "'.api_get_path(WEB_CSS_PATH).'base_classic.css";';
}

//Default CSS
echo '@import "'.api_get_path(WEB_CSS_PATH).$my_style.'/default.css";';
?>
/*]]>*/
</style>
<?php
}
?>

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
if (isset($htmlHeadXtra) && $htmlHeadXtra) {
    foreach ($htmlHeadXtra as & $this_html_head) {
        echo $this_html_head;
    }
}
?>
</head>