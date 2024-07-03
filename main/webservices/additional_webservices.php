<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 *
 * @author Francis Gonzales
 */
require_once __DIR__.'/../inc/global.inc.php';

$enableThis = api_get_configuration_value('webservice_remote_ppt2png_enable');
if (!$enableThis) {
    echo "Remote PPT2PNG service is disabled. \n";
    echo "To enable, add \$_configuration['webservice_remote_ppt2png_enable'] = true; to your configuration.php";
    exit;
}
api_protect_webservices();

/**
 * Function to convert from ppt to png
 * This function is used from Chamilo Rapid Lesson.
 *
 * @param array $pptData
 *
 * @return string
 */
function wsConvertPpt($pptData)
{
    global $_configuration;
    $ip = trim($_SERVER['REMOTE_ADDR']);
    // If an IP filter array is defined in configuration.php,
    // check if this IP is allowed
    if (!empty($_configuration['ppt2lp_ip_filter'])) {
        if (!in_array($ip, $_configuration['ppt2lp_ip_filter'])) {
            return false;
        }
    }
    $fileData = $pptData['file_data'];
    // Clean filename to avoid hacks. Prevents "&" and ";" to be used in filename, notably

    if (strpos($pptData['file_name'], '..') !== false) {
        return false;
    }

    $sanitizedFileName = $pptData['file_name'];
    $dataInfo = pathinfo($sanitizedFileName);
    $fileName = basename($sanitizedFileName, '.'.$dataInfo['extension']);
    // Add additional cleaning of .php and .htaccess files
    $fullFileName = Security::filter_filename($sanitizedFileName);
    $size = $pptData['service_ppt2lp_size'];
    $w = 800;
    $h = 600;
    if (!empty($size)) {
        list($w, $h) = explode('x', $size);
        $w = (int) $w;
        $h = (int) $h;
    }

    $tempArchivePath = api_get_path(SYS_ARCHIVE_PATH);
    $tempPath = $tempArchivePath.'wsConvert/'.$fileName.'/';
    $tempPathNewFiles = $tempArchivePath.'wsConvert/'.$fileName.'-n/';

    $oldumask = umask(0);
    //$perms = api_get_permissions_for_new_directories();
    // Set permissions the most permissively possible: these files will
    // be deleted below and we need a parallel process to be able to write them
    $perms = api_get_permissions_for_new_directories();
    pptConverterDirectoriesCreate($tempPath, $tempPathNewFiles, $fileName, $perms);

    $file = base64_decode($fileData);
    file_put_contents($tempPath.$fullFileName, $file);

    $cmd = pptConverterGetCommandBaseParams(
        $w,
        $h,
        $tempPath.$fullFileName,
        $tempPathNewFiles.$fileName.'.html'
    );

    //$perms = api_get_permissions_for_new_files();
    chmod($tempPathNewFiles.$fileName, $perms);

    $files = [];
    $return = 0;
    $shell = exec($cmd, $files, $return);
    umask($oldumask);

    if ($return === 0) {
        $images = [];
        if (is_array($files) && !empty($files)) {
            foreach ($files as $file) {
                $imageData = explode('||', $file);
                $images[$imageData[1]] = base64_encode(file_get_contents($tempPathNewFiles.$fileName.'/'.$imageData[1]));
            }
        }
        $data = [
            'files' => $files,
            'images' => $images,
        ];

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
 *
 * @return bool
 */
function deleteDirectory($directoryPath)
{
    $files = array_diff(scandir($directoryPath), ['.', '..']);
    foreach ($files as $file) {
        if (is_dir("$directoryPath/$file")) {
            deleteDirectory("$directoryPath/$file");
        } else {
            unlink("$directoryPath/$file");
        }
    }

    return rmdir($directoryPath);
}

/**
 * Helper function to create the directory structure for the PPT converter.
 *
 * @param string $tempPath
 * @param string $tempPathNewFiles
 * @param string $fileName
 * @param string $perms
 */
function pptConverterDirectoriesCreate($tempPath, $tempPathNewFiles, $fileName, $perms)
{
    if (!is_dir($tempPath)) {
        mkdir($tempPath, $perms, true);
    }
    if (!is_dir($tempPathNewFiles)) {
        mkdir($tempPathNewFiles, $perms, true);
    }
    if (!is_dir($tempPathNewFiles.$fileName)) {
        mkdir($tempPathNewFiles.$fileName, $perms, true);
    }
}

/**
 * Helper function to build the command line parameters for the converter.
 *
 * @return string $cmd
 */
function pptConverterGetCommandBaseParams(int $w, int $h, string $inputPath, string $outputPath)
{
    $cd = '';

    if (IS_WINDOWS_OS) { // IS_WINDOWS_OS has been defined in main_api.lib.php
        $converterPath = str_replace('/', '\\', api_get_path(SYS_PATH).'main/inc/lib/ppt2png');
        $classPath = $converterPath.';'.$converterPath.'/jodconverter-2.2.2.jar;'.$converterPath.'/jodconverter-cli-2.2.2.jar';
        $cmd = 'java -Dfile.encoding=UTF-8 -cp "'.$classPath.'"';
    } else {
        $converterPath = api_get_path(SYS_PATH).'main/inc/lib/ppt2png';
        $classPath = ' -Dfile.encoding=UTF-8 -cp .:jodconverter-2.2.2.jar:jodconverter-cli-2.2.2.jar';
        $cd = 'cd '.$converterPath.' && ';
        $cmd = 'java '.$classPath;
    }

    $cmd .= ' DokeosConverter';
    $cmd .= ' -p '.api_get_setting('service_ppt2lp', 'port');
    $cmd .= ' -w '.$w.' -h '.$h;
    $cmd .= ' -d oogie '.Security::sanitizeExecParam($inputPath).' '.Security::sanitizeExecParam($outputPath);

    return $cd.escapeshellcmd($cmd);
}

$uri = api_get_path(WEB_CODE_PATH).'webservices/';
$server = new SoapServer(null, ['uri' => $uri]);
$server->addFunction("wsConvertPpt");
$server->handle();
