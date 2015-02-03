<?php
/* For license terms, see /license.txt */
/**
 * Index of the Advanced subscription plugin courses list
 * @package chamilo.plugin.advancedsubscription
 */
/**
 * Init
 */
require_once __DIR__ . '/../config.php';
// protect
api_protect_admin_script();
// start plugin
$plugin = AdvancedSubscriptionPlugin::create();
// Decrypt if data is a long string
$data = isset($_REQUEST['data']) ?
    strlen($_REQUEST['data']) > 16 ?
        $plugin->decrypt($_REQUEST['data']) :
        null :
    null;
// Get data
if (isset($data) && is_array($data)) {
    // Action code
    $a = isset($data['a']) ? $data['a'] : null;
    // User ID
    $u = isset($data['u']) ? intval($data['u']) : null;
    // Session ID
    $s = isset($data['s']) ? intval($data['s']) : null;
    // More data
    $params['is_connected'] = isset($data['is_connected']) ? $data['is_connected'] : false;
    $params['profile_completed'] = isset($data['profile_completed']) ? $data['profile_completed'] : 0;
    $params['accept'] = isset($data['accept']) ? $data['accept'] : false;
} else {
    // Action code
    $a = isset($_REQUEST['a']) ? Security::remove_XSS($_REQUEST['a']) : null;
    // User ID
    $u = isset($_REQUEST['u']) ? intval($_REQUEST['u']) : null;
    // Session ID
    $s = isset($_REQUEST['s']) ? intval($_REQUEST['s']) : null;
    // More data
    $params['is_connected'] = isset($_REQUEST['is_connected']) ? $_REQUEST['is_connected'] : false;
    $params['profile_completed'] = isset($_REQUEST['profile_completed']) ? $_REQUEST['profile_completed'] : 0;
    $params['accept'] = isset($_REQUEST['accept']) ? $_REQUEST['accept'] : false;
}

// Init template
$tpl = new Template('TESTING');

if (!empty($s)) {
    // Get student list in queue
    $studentList = $plugin->listAllStudentsInQueueBySession($s);
    // Set selected to current session
    $sessionList[$s]['selected'] = 'selected="selected"';
    // Assign variables
    $tpl->assign('session', $studentList['session']);
    $tpl->assign('students', $studentList['students']);
}

// Get all sessions
$sessionList = $plugin->listAllSessions();
// Assign variables
$tpl->assign('sessionItems', $sessionList);
// Get rendered template
$content = $tpl->fetch('/advancedsubscription/views/admin_view.tpl');
// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
