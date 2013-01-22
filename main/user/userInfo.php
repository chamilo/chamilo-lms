<?php

require_once '../inc/global.inc.php';

if (isset($_REQUEST['uInfo'])) {
    $url = api_get_path(WEB_CODE_PATH)."social/profile.php?u=".intval($_REQUEST['uInfo']);
    header("Location: $url");
    exit;
}
api_not_allowed('true');