<?php

/* For licensing terms, see /license.txt */

require_once 'dropbox_init.inc.php';

api_protect_course_script();

if (0 != api_get_session_id() && !api_is_allowed_to_session_edit(false, true)) {
    api_not_allowed(true);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (empty($id)) {
    api_not_allowed(true);
}

$work = new Dropbox_SentWork($id);
if (empty($work)) {
    api_not_allowed(true);
}

if (isset($_POST['submitWork'])) {
    store_add_dropbox(null, $work);
}

$viewReceivedCategory = isset($_GET['view_received_category']) ? Security::remove_XSS($_GET['view_received_category']) : '';
$viewSentCategory = isset($_GET['view_sent_category']) ? Security::remove_XSS($_GET['view_sent_category']) : '';
$view = isset($_GET['view']) ? Security::remove_XSS($_GET['view']) : '';

echo Display::page_header($work->title);

display_add_form(
    $viewReceivedCategory,
    $viewSentCategory,
    $view,
    $id
);

Display::display_footer();
