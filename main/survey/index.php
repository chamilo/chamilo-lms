<?php
require_once '../inc/global.inc.php';
header('location: '.api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq());
exit;
