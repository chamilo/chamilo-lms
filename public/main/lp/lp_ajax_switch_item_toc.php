<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script contains the server part of the ajax interaction process. The client part is located
 * in lp_api.php or other api's.
 * This script updated the TOC of the SCORM without updating the SCO's attributes.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';

/**
 * Get one item's details.
 *
 * @param int $lpId        LP ID
 * @param int $userId      user ID
 * @param int $viewId      View ID
 * @param int $currentItem Current item ID
 * @param int $nextItem    New item ID
 *
 * @return string JavaScript commands to be executed in scorm_api.php
 */
function switch_item_toc($lpId, $userId, $viewId, $currentItem, $nextItem)
{
    $debug = 0;
    $return = '';

    if ($debug > 0) {
        error_log('In switch_item_toc('.$lpId.','.$userId.','.$viewId.','.$currentItem.','.$nextItem.')', 0);
    }

    // Read LP from session (requires session), then release session lock early.
    $myLP = learnpath::getLpFromSession(api_get_course_int_id(), $lpId, $userId);

    if (function_exists('session_write_close')) {
        @session_write_close();
    }

    $newItemId = 0;
    $oldItemId = 0;

    switch ($nextItem) {
        case 'next':
            $myLP->set_current_item($currentItem);
            $myLP->next();
            $newItemId = $myLP->get_current_item_id();
            break;
        case 'previous':
            $myLP->set_current_item($currentItem);
            $myLP->previous();
            $newItemId = $myLP->get_current_item_id();
            break;
        case 'first':
            $myLP->set_current_item($currentItem);
            $myLP->first();
            $newItemId = $myLP->get_current_item_id();
            break;
        case 'last':
            $newItemId = $myLP->get_current_item_id();
            break;
        default:
            if ($nextItem == $currentItem) {
                $myLP->items[$currentItem]->restart();
            } else {
                $oldItemId = $currentItem;
            }
            $newItemId = (int) $nextItem;
            $myLP->set_current_item($newItemId);
            break;
    }

    $myLP->start_current_item(true);

    if ($myLP->force_commit) {
        $myLP->save_current();
    }

    if (is_object($myLP->items[$newItemId])) {
        $myLPI = $myLP->items[$newItemId];
    } else {
        $myLPI = new learnpathItem($newItemId);
        $myLPI->set_lp_view($viewId);
    }

    $lessonStatus = $myLPI->get_status();
    $interactionsCount = $myLPI->get_interactions_count();

    $totalItems = $myLP->getTotalItemsCountWithoutDirs();
    $completedItems = $myLP->get_complete_items_count();
    $progressMode = $myLP->get_progress_bar_mode();
    $progressMode = ('' == $progressMode ? '%' : $progressMode);

    $nextItemId = $myLP->get_next_item_id();
    $previousItemId = $myLP->get_previous_item_id();

    $itemType = $myLPI->get_type();
    $lessonMode = $myLPI->get_lesson_mode();
    $credit = $myLPI->get_credit();
    $launchData = $myLPI->get_launch_data();
    $objectivesCount = $myLPI->get_objectives_count();
    $coreExit = $myLPI->get_core_exit();

    $return .=
        "olms.lms_lp_id=".$lpId.";".
        "olms.lms_item_id=".$newItemId.";".
        "olms.lms_old_item_id=".$oldItemId.";".
        "olms.lms_initialized=0;".
        "olms.lms_view_id=".$viewId.";".
        "olms.lms_user_id=".$userId.";".
        "olms.next_item=".$newItemId.";".
        "olms.lms_next_item=".$nextItemId.";".
        "olms.lms_previous_item=".$previousItemId.";".
        "olms.lms_item_type = '".$itemType."';".
        "olms.lms_item_credit = '".$credit."';".
        "olms.lms_item_lesson_mode = '".$lessonMode."';".
        "olms.lms_item_launch_data = '".$launchData."';".
        "olms.lms_item_interactions_count = '".$interactionsCount."';".
        "olms.lms_item_objectives_count = '".$objectivesCount."';".
        "olms.lms_item_core_exit = '".$coreExit."';".
        "olms.asset_timer = 0;";

    $return .= "update_toc('unhighlight','".$currentItem."');".
        "update_toc('highlight','".$newItemId."');".
        "update_toc('$lessonStatus','".$newItemId."');";

    $progressBarSpecial = false;
    $scoreAsProgressSetting = ('true' === api_get_setting('lp.lp_score_as_progress_enable'));
    if (true === $scoreAsProgressSetting) {
        $scoreAsProgress = $myLP->getUseScoreAsProgress();
        if ($scoreAsProgress) {
            $score = $myLPI->get_score();
            $maxScore = $myLPI->get_max();
            $return .= "update_progress_bar('$score', '$maxScore', '$progressMode');";
            $progressBarSpecial = true;
        }
    }
    if (!$progressBarSpecial) {
        $return .= "update_progress_bar('$completedItems','$totalItems','$progressMode');";
    }

    $myLP->set_error_msg('');
    $myLP->prerequisites_match($newItemId);

    // Re-open session only to write the new state (keep lock time minimal).
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    Session::write('scorm_item_id', $newItemId);
    Session::write('oLP', $myLP);

    return $return;
}

echo switch_item_toc(
    $_POST['lid'],
    $_POST['uid'],
    $_POST['vid'],
    $_POST['iid'],
    $_POST['next']
);
