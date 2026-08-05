<?php
// Deprecated file left here because contents removed after 2.0 RC2. Should be removed before the next major version.
// Redirect to the Vue SPA page; extract the id parameter for backwards compatibility.
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    header('Location: /admin/usergroup-users/'.$id);
} else {
    header('Location: /admin/usergroups');
}
exit;
