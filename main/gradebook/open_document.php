<?php

include ('../inc/global.inc.php');
include ('../inc/lib/document.lib.php');
api_block_anonymous_users();


DocumentManager :: file_send_for_download(api_get_path(SYS_COURSE_PATH).$_GET['file']);


?>
