<?php
/* For licensing terms, see /license.txt */

/**
 *   @package chamilo.admin
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$interbreadcrumb[] = ['url' => 'index.php', "name" => get_lang('Administration')];
$tool_name = get_lang('Statistics');
Display::display_header($tool_name);
Display::display_footer();
