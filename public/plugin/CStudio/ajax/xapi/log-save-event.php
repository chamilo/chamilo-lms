<?php

declare(strict_types=1);

require_once __DIR__.'/../../0_dal/dal.global_lib.php';

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../../0_dal/dal.insert.php';

require_once __DIR__.'/../inc/functions.php';

if (isset($_POST['title'])
    || isset($_POST['id'])
    || isset($_POST['idteach'])
    || isset($_POST['result'])
    || isset($_POST['logs'])) {
    if (!$VDB->w_api_is_anonymous()) {
        // oel_add_dev_logs('inserOelToolsLog("'.clean_term_string($_POST['title']).','.get_int_from('id').'",'. get_int_from('idteach').'","'.$logsactions.'",1,'.get_int_from('result').');');

        $resultV = 0;
        if (true == $_POST['result'] || 'true' == $_POST['result']) {
            $resultV = 1;
        }

        insertOelToolsLog(
            clean_term_string($_POST['title']),
            get_int_from('id'),
            get_int_from('idteach'),
            clean_term_logs($_POST['logs']),
            3,
            $resultV
        );

        echo 'OK';
    }
}

function clean_term_logs($value)
{
    $search = ['é', ',', 'ç', 'è', '@', "\r", "\n"];
    $replace = ['e', '-', 'c', 'e', '-', '', ''];

    return str_replace($search, $replace, $value);
}
