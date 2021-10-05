<?php

/* For licensing terms, see /license.txt */

/**
 * This script contains the server part of the AJAX interaction process.
 * The client part is located * in lp_api.php or other api's.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

$interactions = [];
if (isset($_REQUEST['interact'])) {
    if (is_array($_REQUEST['interact'])) {
        foreach ($_REQUEST['interact'] as $idx => $interac) {
            $interactions[$idx] = preg_split('/,/', substr($interac, 1, -1));
            if (!isset($interactions[$idx][7])) { // Make sure there are 7 elements.
                $interactions[$idx][7] = '';
            }
        }
    }
}

echo ScormApi::saveItem(
    (!empty($_REQUEST['lid']) ? $_REQUEST['lid'] : null),
    (!empty($_REQUEST['uid']) ? $_REQUEST['uid'] : null),
    (!empty($_REQUEST['vid']) ? $_REQUEST['vid'] : null),
    (!empty($_REQUEST['iid']) ? $_REQUEST['iid'] : null),
    (!empty($_REQUEST['s']) ? $_REQUEST['s'] : null),
    (!empty($_REQUEST['max']) ? $_REQUEST['max'] : null),
    (!empty($_REQUEST['min']) ? $_REQUEST['min'] : null),
    (!empty($_REQUEST['status']) ? $_REQUEST['status'] : null),
    (!empty($_REQUEST['t']) ? $_REQUEST['t'] : null),
    (!empty($_REQUEST['suspend']) ? $_REQUEST['suspend'] : null),
    (!empty($_REQUEST['loc']) ? $_REQUEST['loc'] : null),
    $interactions,
    (!empty($_REQUEST['core_exit']) ? $_REQUEST['core_exit'] : ''),
    (!empty($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : null),
    (!empty($_REQUEST['course_id']) ? (int) $_REQUEST['course_id'] : null),
    (empty($_REQUEST['finish']) ? 0 : 1),
    (empty($_REQUEST['userNavigatesAway']) ? 0 : 1),
    (empty($_REQUEST['statusSignalReceived']) ? 0 : 1),
    $_REQUEST['switch_next'] ?? 0,
    $_REQUEST['load_nav'] ?? 0
);
