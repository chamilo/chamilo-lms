<?php // $Id: statistics.php 10811 2007-01-22 08:26:40Z elixir_julian $
/* For licensing terms, see /license.txt */
/**
*   @package chamilo.admin
*/
/*
        INIT SECTION
*/
// name of the language file that needs to be included
$language_file='admin';
$cidReset=true;
require_once '../inc/global.inc.php';
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$tool_name = get_lang('Statistics');
Display::display_header($tool_name);
Display::display_footer();