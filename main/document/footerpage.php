<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.document
 *	TODO: There is no indication that this file us used for something.
 */

require_once '../inc/global.inc.php';

$my_style = api_get_visual_theme();

?><!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<link rel="stylesheet" href="<?php echo api_get_path(WEB_CSS_PATH).$my_style; ?>/default.css" type="text/css">
</head>
<body dir="<?php echo api_get_text_direction(); ?>">
<?php

Display::display_footer();
