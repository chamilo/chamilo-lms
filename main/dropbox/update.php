<?php

require_once 'dropbox_init.inc.php';

api_protect_course_script();

if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
    api_not_allowed(true);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (empty($id)) {
    api_not_allowed(true);
}

$work = new Dropbox_SentWork($id);
if (empty($work)) {
    api_not_allowed(true);
}

if (isset($_POST['submitWork'])) {
    store_add_dropbox(null, true);
}



Display::display_footer();
