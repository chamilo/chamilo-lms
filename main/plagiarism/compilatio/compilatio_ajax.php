<?php
/* For licensing terms, see /license.txt */

require_once '../../inc/global.inc.php';

api_protect_course_script();

if (isset($_GET['workid'])) {
    $workIdList = $_GET['workid'];    // list of workid separate by the :
    $result = '';
    $tabWorkId = explode('a', $workIdList);
    $compilatio = new Compilatio();
    for ($i = 0; $i < count($tabWorkId); $i++) {
        if (is_numeric($tabWorkId[$i])) {
            $result .= $compilatio->giveWorkIdState($tabWorkId[$i]);
        }
    }
    echo $result;
}
