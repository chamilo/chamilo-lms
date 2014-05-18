<?php
/* For licensing terms, see /license.txt */
/**
 * OpenID 
 * @package chamilo.auth.openid
 */
/**
 * Code
 */
require_once '../../inc/global.inc.php';
Display::display_header('OpenID', NULL);
echo Display::page_header(get_lang('OpenIDWhatIs'));
echo get_lang('OpenIDDescription');
Display::display_footer();