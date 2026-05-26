<?php
// Deprecated file left here because contents removed after 2.0 RC2. Should be removed before the next major version.
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
if ($id > 0) {
    header('Location: /admin/usergroups/'.$id.'/add-courses');
} else {
    header('Location: /admin/usergroups');
}
exit;
