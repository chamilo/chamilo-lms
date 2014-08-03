<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 * @author Francis Gonzales
 */

require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'fileManage.lib.php';
require_once $libpath.'fileUpload.lib.php';
require_once api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php';
require_once $libpath.'add_course.lib.inc.php';


/**
 * Function to convert from ppt to png
 * This function is used from Chamilo Rapid Lesson
 *
 * @param array $pptData
 * @return string
 */
function wsConvertPpt($pptData)
{
    $fileData = $pptData['file_data'];
    $dataInfo = pathinfo($pptData['file_name']);
    $fileName =  basename($pptData['file_name'], '.' . $dataInfo['extension']);
    $fullFileName = $pptData['file_name'];

    $tempArchivePath = api_get_path(SYS_ARCHIVE_PATH);
    $tempPath = $tempArchivePath . 'wsConvert/' . $fileName . '/';
    $tempPathNewFiles = $tempArchivePath . 'wsConvert/' . $fileName . '-n/';

    mkdir($tempPath, 0777, true);
    mkdir($tempPathNewFiles, 0777, true);
    mkdir($tempPathNewFiles . $fileName, 0777, true);

    $file = base64_decode($fileData);
    file_put_contents($tempPath . $fullFileName, $file);

    if (IS_WINDOWS_OS) { // IS_WINDOWS_OS has been defined in main_api.lib.php
        $converterPath = str_replace('/', '\\', api_get_path(SYS_PATH) . 'main/inc/lib/ppt2png');
        $classPath = $converterPath . ';' . $converterPath . '/jodconverter-2.2.2.jar;' . $converterPath . '/jodconverter-cli-2.2.2.jar';
        $cmd = 'java -Dfile.encoding=UTF-8 -cp "' . $classPath . '" DokeosConverter';
    } else {
        $converterPath = api_get_path(SYS_PATH) . 'main/inc/lib/ppt2png';
        $classPath = ' -Dfile.encoding=UTF-8 -cp .:jodconverter-2.2.2.jar:jodconverter-cli-2.2.2.jar';
        $cmd = 'cd ' . $converterPath . ' && java ' . $classPath . ' DokeosConverter';
    }

    $cmd .= ' -p ' . api_get_setting('service_ppt2lp', 'port');
    $cmd .= ' -w 720 -h 540 -d oogie "' . $tempPath . $fullFileName.'"  "' . $tempPathNewFiles . $fileName . '.html"';

    chmod($tempPath, 0777);
    chmod($tempPathNewFiles, 0777);
    chmod($tempPathNewFiles . $fileName, 0777, true);

    $files = array();
    $return = 0;
    $shell = exec($cmd, $files, $return);

    if ($return === 0) {
        $images = array();
        foreach ($files as $file) {
            $imageData = explode('||', $file);
            $images[$imageData[1]] = base64_encode(file_get_contents($tempPathNewFiles . $fileName . '/' . $imageData[1]));
        }
        $data = array(
            'files' => $files,
            'images' => $images
        );

        deleteDirectory($tempPath);
        deleteDirectory($tempPathNewFiles);

        return serialize($data);
    } else {
        deleteDirectory($tempPath);
        deleteDirectory($tempPathNewFiles);

        return false;
    }
}

function deleteDirectory($directoryPath)
{
    $files = array_diff(scandir($directoryPath), array('.','..'));
    foreach ($files as $file) {
        (is_dir("$directoryPath/$file")) ? deleteDirectory("$directoryPath/$file") : unlink("$directoryPath/$file");
    }

    return rmdir($directoryPath);
}


$webPath = api_get_path(WEB_PATH);
$webCodePath = api_get_path(WEB_CODE_PATH);
$options = array(
    'uri' => $webPath,
    'location' => $webCodePath . 'webservices/additional_webservices.php'
);
$soapServer = new SoapServer(NULL, $options);
$soapServer->addFunction('wsConvertPpt');
$soapServer->handle();