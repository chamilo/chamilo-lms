<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLp;
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
$lpId = $learnPath->get_id();

// Base URL for blank page including lp context (useful for access checks / voter).
$blankBaseUrl = 'blank.php?'.api_get_cidreq().'&lp_id='.$lpId.'&item_id='.$lpItemId;

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
    if ($toc['id'] == $lpItemId && 'dir' == $toc['type']) {
        $dir = true;
    }
}

if ($dir) {
    // Use blank page but keep lp context in query string.
    $src = $blankBaseUrl;
} else {
    switch ($lpType) {
        case CLp::LP_TYPE:
            $learnPath->stop_previous_item();
            $prerequisiteCheck = $learnPath->prerequisites_match($lpItemId);
            if (true === $prerequisiteCheck) {
                $src = $learnPath->get_link('http', $lpItemId);
                if (empty($src)) {
                    // Document is protected or not reachable -> send to blank with lp context.
                    $src = $blankBaseUrl.'&error=document_protected';
                    break;
                }
                $learnPath->start_current_item(); // starts time counter manually if asset
                $src = $learnPath->fixBlockedLinks($src);
                break;
            }
            // Prerequisites not met -> blank with lp context and message.
            $src = $blankBaseUrl.'&error=prerequisites&prerequisite_message='.Security::remove_XSS($learnPath->error);
            break;
        case CLp::SCORM_TYPE:
            $learnPath->stop_previous_item();
            $prerequisiteCheck = $learnPath->prerequisites_match($lpItemId);

            if (true === $prerequisiteCheck) {
                $src = $learnPath->get_link('http', $lpItemId);
                $learnPath->start_current_item(); // starts time counter manually if asset
            } else {
                // Prerequisites not met -> blank with lp context and message.
                $src = $blankBaseUrl.'&error=prerequisites&prerequisite_message='.Security::remove_XSS($learnPath->error);
            }
            break;
        case CLp::AICC_TYPE:
            // save old if asset
            $learnPath->stop_previous_item(); // save status manually if asset
            $prerequisiteCheck = $learnPath->prerequisites_match($lpItemId);
            if (true === $prerequisiteCheck) {
                $src = $learnPath->get_link('http', $lpItemId);
                $learnPath->start_current_item(); // starts time counter manually if asset
            } else {
                // Fallback to blank with lp context (no specific error used here).
                $src = $blankBaseUrl;
            }
            break;
    }
}

// -------------------------------------------------------------------------
// Ensure lp context is present in the final URL (useful for voters / checks)
// This will affect normal document URLs like /r/document/files/.../view?cid=...&sid=...
// but will not duplicate the parameters for blank.php where they are already set.
// -------------------------------------------------------------------------
if (!empty($src) && false === strpos($src, 'lp_id=')) {
    $separator = false === strpos($src, '?') ? '?' : '&';
    $src .= $separator.'lp_id='.$lpId.'&item_id='.$lpItemId;
}

if ($debug > 0) {
    error_log('New lp - In lp_content.php - File url is '.$src);
}
$learnPath->set_previous_item($lpItemId);

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}
// Define the 'doc.inc.php' as language file.
$nameTools = $learnPath->getNameNoTags();
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'lp/lp_list.php?'.api_get_cidreq(),
    'name' => get_lang('Document'),
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
