<?php
// $url = api_get_path(WEB_PUBLIC_PATH).'admin';  // This function not exist
$url = '../../web/admin'; // go to admin page.
header('Location: ' . $url);
exit;
