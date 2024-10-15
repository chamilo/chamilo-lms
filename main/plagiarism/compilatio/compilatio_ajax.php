<?php
/* For licensing terms, see /license.txt */

require_once '../../inc/global.inc.php';

api_protect_course_script();

if (isset($_GET['workid'])) {
    $workIdList = $_GET['workid'];    // list of workid separate by the :
    $workList = explode('a', $workIdList);
    $workList = array_map('intval', $workList);
    $workList = array_filter($workList);
    try {
        $compilatio = new Compilatio();
        $result = '';
        foreach ($workList as $workId) {
            if (!empty($workId)) {
                $result .= $compilatio->giveWorkIdState($workId);
            }
        }
        echo $result;
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
