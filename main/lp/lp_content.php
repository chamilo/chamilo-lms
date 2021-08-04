<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Script that displays an error message when no content could be loaded.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
require_once __DIR__.'/../inc/global.inc.php';

$debug = 0;
if ($debug > 0) {
    error_log('New lp - In lp_content.php');
}
if (empty($lp_controller_touched)) {
    if ($debug > 0) {
        error_log('New lp - In lp_content.php - Redirecting to lp_controller');
    }
    header('Location: lp_controller.php?action=content&lp_id='.intval($_REQUEST['lp_id']).'&item_id='.intval($_REQUEST['item_id']).'&'.api_get_cidreq());
    exit;
}

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');
$learnPath->error = '';
$lpType = $learnPath->get_type();
$lpItemId = $learnPath->get_current_item_id();

/**
 * Get a link to the corresponding document.
 */
$src = '';
if ($debug > 0) {
    error_log('New lp - In lp_content.php - Looking for file url');
    error_log("lp_type $lpType");
    error_log("lp_item_id $lpItemId");
}

$list = $learnPath->get_toc();
$dir = false;

foreach ($list as $toc) {
    if ($toc['id'] == $lpItemId && $toc['type'] === 'dir') {
        $dir = true;
    }
}

if ($dir) {
    $src = 'blank.php';
} else {
    switch ($lpType) {
        case 1:
            $learnPath->stop_previous_item();
            $prerequisiteCheck = $learnPath->prerequisites_match($lpItemId);
            if ($prerequisiteCheck === true) {
                $src = $learnPath->get_link('http', $lpItemId);
                if (empty($src)) {
                    $src = 'blank.php?'.api_get_cidreq().'&error=document_protected';
                    break;
                }

                $learnPath->start_current_item(); // starts time counter manually if asset
                $src = $learnPath->fixBlockedLinks($src);

                if (WhispeakAuthPlugin::isLpItemMarked($lpItemId)) {
                    ChamiloSession::write(
                        WhispeakAuthPlugin::SESSION_LP_ITEM,
                        ['lp' => $learnPath->lp_id, 'lp_item' => $lpItemId, 'src' => $src]
                    );

                    $src = api_get_path(WEB_PLUGIN_PATH).'whispeakauth/authentify.php';
                }
                break;
            }
            $src = 'blank.php?'.api_get_cidreq().'&error=prerequisites&prerequisite_message='.Security::remove_XSS($learnPath->error);
            break;
        case 2:
            $learnPath->stop_previous_item();
            $prerequisiteCheck = $learnPath->prerequisites_match($lpItemId);

            if ($prerequisiteCheck === true) {
                $src = $learnPath->get_link('http', $lpItemId);
                $learnPath->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php?'.api_get_cidreq().'&error=prerequisites&prerequisite_message='.Security::remove_XSS($learnPath->error);
            }
            break;
        case 3:
            // save old if asset
            $learnPath->stop_previous_item(); // save status manually if asset
            $prerequisiteCheck = $learnPath->prerequisites_match($lpItemId);
            if ($prerequisiteCheck === true) {
                $src = $learnPath->get_link('http', $lpItemId);
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
    error_log('New lp - In lp_content.php - File url is '.$src);
}
$learnPath->set_previous_item($lpItemId);

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}
// Define the 'doc.inc.php' as language file.
$nameTools = $learnPath->getNameNoTags();
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'lp/lp_list.php?'.api_get_cidreq(),
    'name' => get_lang('Doc'),
];
// Update global setting to avoid displaying right menu.
$save_setting = api_get_setting('show_navigation_menu');
global $_setting;
$_setting['show_navigation_menu'] = false;
if ($debug > 0) {
    error_log('New LP - In lp_content.php - Loading '.$src);
}
Session::write('oLP', $learnPath);
header('Location: '.urldecode($src));
exit;
