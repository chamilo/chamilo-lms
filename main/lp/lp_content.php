<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Script that displays an error message when no content could be loaded
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

require_once __DIR__.'/../inc/global.inc.php';

$debug = 0;
if ($debug > 0) {
    error_log('New lp - In lp_content.php', 0);
}
if (empty($lp_controller_touched)) {
    if ($debug > 0) {
        error_log('New lp - In lp_content.php - Redirecting to lp_controller', 0);
    }
    header('location: lp_controller.php?action=content&lp_id='.intval($_REQUEST['lp_id']).'&item_id='.intval($_REQUEST['item_id']).'&'.api_get_cidreq());
    exit;
}

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');
$learnPath->error = '';
$lp_type = $learnPath->get_type();
$lp_item_id = $learnPath->get_current_item_id();

/**
 * Get a link to the corresponding document
 */
$src = '';
if ($debug > 0) {
    error_log('New lp - In lp_content.php - Looking for file url', 0);
}

$list = $learnPath->get_toc();
$dir = false;

foreach ($list as $toc) {
    if ($toc['id'] == $lp_item_id && $toc['type'] == 'dir') {
        $dir = true;
    }
}

if ($dir) {
    $src = 'blank.php';
} else {
    switch ($lp_type) {
        case 1:
            $learnPath->stop_previous_item();
            $prereq_check = $learnPath->prerequisites_match($lp_item_id);

            if ($prereq_check === true) {
                $src = $learnPath->get_link('http', $lp_item_id);
                $learnPath->start_current_item(); // starts time counter manually if asset
                $src = $learnPath->fixBlockedLinks($src);

                break;
            }

            $src = 'blank.php?error=prerequisites';
            break;
        case 2:
            $learnPath->stop_previous_item();
            $prereq_check = $learnPath->prerequisites_match($lp_item_id);

            if ($prereq_check === true) {
                $src = $learnPath->get_link('http', $lp_item_id);
                $learnPath->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php?error=prerequisites';
            }
            break;
        case 3:
            // save old if asset
            $learnPath->stop_previous_item(); // save status manually if asset
            $prereq_check = $learnPath->prerequisites_match($lp_item_id);
            if ($prereq_check === true) {
                $src = $learnPath->get_link('http', $lp_item_id);
                $learnPath->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php';
            }
            break;
        case 4:
            break;
    }
}

if ($debug > 0) {
    error_log('New lp - In lp_content.php - File url is '.$src, 0);
}
$learnPath->set_previous_item($lp_item_id);

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook')
    ];
}
// Define the 'doc.inc.php' as language file.
$nameTools = $learnPath->get_name();
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'lp/lp_list.php?'.api_get_cidreq(),
    'name' => get_lang('Doc'),
];
// Update global setting to avoid displaying right menu.
$save_setting = api_get_setting('show_navigation_menu');
global $_setting;
$_setting['show_navigation_menu'] = false;
if ($debug > 0) {
    error_log('New LP - In lp_content.php - Loading '.$src, 0);
}
Session::write('oLP', $learnPath);
header("Location: ".urldecode($src));
exit;
