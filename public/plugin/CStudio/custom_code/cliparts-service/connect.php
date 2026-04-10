<?php

declare(strict_types=1);

header('Content-type: application/javascript');

require_once __DIR__.'/../../../../main/inc/global.inc.php';

require_once '../../ajax/inc/functions.php';

require_once '../../0_dal/dal.save.php';

if (api_is_anonymous()) {
    echo '{"files":[]}';

    exit;
}
if (isset($_POST['idteach']) || isset($_GET['idteach'])) {
    echo '{"files" :[';

    echo getCollFromFold('openclipart');

    echo ']}';
} else {
    echo '{"files":[]}';
}

function getCollFromFold($localFolder)
{
    $rt = '';

    $rt .= '{';
    $rt .= '"folder":"","src":"",';
    $rt .= '"nameonly":"","usefile":0';
    $rt .= '}';

    if (file_exists($localFolder)) {
        $dir = opendir($localFolder);
        while ($file = readdir($dir)) {
            if ('.' != $file && '..' != $file && '' != $file
            && !is_dir($localFolder.'/'.$file)) {
                if (false != strpos($file, '.jpg') || false != strpos($file, '.png')
                    || false != strpos($file, '.gif') || false != strpos($file, '.jpeg')) {
                    $nam = $file;
                    $rt .= ',{';
                    $rt .= '"folder":"",';
                    $rt .= '"src":"web_plugin|CStudio/custom_code/cliparts-service/'.$localFolder.'/'.$nam.'",';
                    $rt .= '"nameonly":"'.$nam.'",';
                    $rt .= '"usefile":0';
                    $rt .= '}';
                }
            }
        }
        closedir($dir);
    }

    return $rt;
}
