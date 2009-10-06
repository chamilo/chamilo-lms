<?php
require ('../inc/global.inc.php');

define('FRAME','footer');

echo '<html><head><style>';

/*
 * Choose CSS style (platform's, user's, or course's)
 */

$platform_theme = api_get_setting('stylesheets'); 	// plataform's css
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
if (!empty($mycourseid) && $mycourseid != -1)
{
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

	}
}

echo '@import "'.api_get_path(WEB_CODE_PATH).'css/'.$my_style.'/default.css'.'";';

echo '</style></head><body><br>';

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();

?>
