<?php

/* For licensing terms, see /license.txt */
/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
// User permissions
api_protect_admin_script();
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
Display::display_header(get_lang('SystemStatus'));
$diag = new Diagnoser();
$diag->show_html();
Display::display_footer();
