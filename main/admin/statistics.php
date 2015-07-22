<?php
/* For licensing terms, see /license.txt */

/**
*   @package chamilo.admin
*/

$cidReset=true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$tool_name = get_lang('Statistics');
Display::display_header($tool_name);
Display::display_footer();
