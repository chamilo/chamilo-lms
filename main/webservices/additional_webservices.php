<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 * @author Francis Gonzales
 */

require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);

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

    $perms = api_get_permissions_for_new_directories();
    if (!is_dir($tempPath)) {
        mkdir($tempPath, $perms, true);
    }
    if (!is_dir($tempPathNewFiles)) {
        mkdir($tempPathNewFiles, $perms, true);
    }
    if (!is_dir($tempPathNewFiles . $fileName)) {
        mkdir($tempPathNewFiles . $fileName, $perms, true);
    }

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

    $perms = api_get_permissions_for_new_files();
    chmod($tempPathNewFiles . $fileName, $perms);

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

/**
 * @param $directoryPath
 * @return bool
 */
function deleteDirectory($directoryPath)
{
    $files = array_diff(scandir($directoryPath), array('.','..'));
    foreach ($files as $file) {
        if (is_dir("$directoryPath/$file")) {
            deleteDirectory("$directoryPath/$file");
        } else {
            unlink("$directoryPath/$file");
        }
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
