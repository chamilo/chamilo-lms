<?php
/* For licensing terms, see /license.txt */
/**
 * This file includes lp_list_search to avoid duplication of code, it
 * bootstraps chamilo api enough to make lp_list_search work.
 *
 * @package chamilo.search
 */
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

if (extension_loaded('xapian')) {
    require '../lp/lp_list_search.php';
} else {
    Display::display_header(get_lang('Search'));
    echo Display::return_message(get_lang('SearchXapianModuleNotInstalled'), 'error');
    Display::display_footer();
    exit;
}
