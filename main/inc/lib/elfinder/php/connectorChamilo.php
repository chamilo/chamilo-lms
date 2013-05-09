<?php

error_reporting(0); // Set E_ALL for debuging

include_once dirname(__FILE__).'../../../../global.inc.php';

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderConnector.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeLocalFileSystem.class.php';

/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from  '.' (dot)
 *
 * @param  string  $attr  attribute name (read|write|locked|hidden)
 * @param  string  $path  file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function access($attr, $path, $data, $volume) {
    //error_log($path); error_log($attr);
	return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
		? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
		:  null;                                    // else elFinder decide it itself
}

/**
 * Simple callback catcher
 *
 * @param  string   $cmd       command name
 * @param  array    $result    command result
 * @param  array    $args      command arguments from client
 * @param  \elFinder   $elfinder  elFinder instance
 * @return void|true
 **/
function logger($cmd, $result, $args, $elfinder) {
    // do something here
    //echo $cmd;

    $courseInfo = api_get_course_info();
    /*
    error_log($cmd);
    error_log(print_r($result,1));
    error_log(print_r($args,1));
    error_log(print_r($elfinder,1));*/

    switch($cmd) {
        case 'mkdir':
            break;
        case 'mkfile':
            break;
        case 'rename':
            break;
        case 'duplicate':
            break;
        case 'upload':
            // Files added
            if (isset($result['added'])) {
                foreach ($result['added'] as $file) {
                    $realPath = $elfinder->realpath($file['hash']);
                    if (!empty($realPath)) {
                        // Getting file info
                        $info = $elfinder->exec('file', array('target' => $file['hash']));
                        /** @var elFinderVolumeLocalFileSystem $volume */
                        $volume = $info['volume'];
                        $root = $volume->root();
                        //var/www/chamilogits/data/courses/NEWONE/document
                        $realPathRoot = $elfinder->realpath($root);
                        /*
                        $defaultPath = $volume->defaultPath();
                        error_log($defaultPath);
                        $driverId= $volume->driverId();
                        error_log($root);
                        error_log($driverId);*/

                        //error_log(print_r($info, 1));
                        //error_log($realPathRoot);
                        //error_log($realPath);
                        // Removing course path
                        $realPath = str_replace($realPathRoot, '', $realPath);
                        FileManager::add_document($courseInfo, $realPath, 'file', intval($file['size']), $file['name']);
                    }
                }
            }
            break;
        case 'rm':
            if (isset($result['removed'])) {
                foreach ($result['removed'] as $file) {
                    $realFilePath = $file['realpath'];
                    $filePath = str_replace($courseInfo['course_sys_data'].'document', '', $realFilePath);
                    /*error_log($filePath);
                    error_log($courseInfo['course_sys_data'].'document');*/

                    /*
                    $info = $elfinder->exec('file', array('target' => $file['phash']));
                    error_log(print_r($info,1));

                    $volume = $info['volume'];
                    $root = $volume->root();
                    //var/www/chamilogits/data/courses/NEWONE/document
                    $realPathRoot = $elfinder->realpath($root);
                    error_log($realPathRoot);

                    $realPath = $file['realpath'];
                    $realPath = str_replace($realPathRoot, '', $realPath);
                    error_log($realPath);
                    error_log($realPathRoot);*/
                    DocumentManager::delete_document($courseInfo, $filePath, $courseInfo['course_sys_data'].'document');
                }
            }
            break;
        case 'paste':
            break;
    }
}

$opts = array(
	//'debug' => true,
    'bind' => array(
        'mkdir mkfile rename duplicate upload rm paste' => 'logger'
        //'mkdir mkfile rename duplicate upload rm paste' => 'chamilo'
    ),
    /*
	'roots' => array(
		array(
			'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
			'path'          => '../files/',         // path to files (REQUIRED)
			'URL'           => dirname($_SERVER['PHP_SELF']) . '/../files/', // URL to files (REQUIRED)
			'accessControl' => 'access'             // disable and hide dot starting files (OPTIONAL)
		)
	)*/
);

$courseInfo = api_get_course_info();

if (!empty($courseInfo)) {

    // Adding course driver
    $opts['roots'][] = array(
        'driver'     => 'LocalFileSystem',
        'path'       => api_get_path(SYS_DATA_PATH).'courses/'.$courseInfo['path'].'/document',
        'startPath'  => '/',
        'URL' => api_get_path(REL_COURSE_PATH).$courseInfo['path'].'/document',
        //'alias' => $courseInfo['code'].' documents',
        'accessControl' => 'access',
        'attributes' => array(
            'pattern' => '/^images$/',
            'read'   => false,
            'write'  => false,
            'locked' => true,
            //'hidden' => false
        )
    );

    /*

    // Adding course user file driver
    $userId = api_get_user_id();
    if (!empty($userId)) {
        $opts['roots'][] = array(
            'driver'     => 'LocalFileSystem',
            'path'       => api_get_path(SYS_DATA_PATH).'courses/'.$courseInfo['path'].'/document/shared_folder/sf_user_'.$userId,
            'startPath'  => '/',
            //'alias' => $courseInfo['code'].' personal documents',
            'URL' => api_get_path(REL_COURSE_PATH).$courseInfo['path'].'/document/shared_folder/sf_user_'.$userId,
            'accessControl' => 'access',
            'attributes' => array(
                'pattern' => '/^images$/',
                'read'   => false,
                'write'  => false,
                'locked' => true,
                //'hidden' => false
            )
        );

        // Adding user personal files

        $dir = UserManager::get_user_picture_path_by_id($userId, 'system');
        $dirWeb = UserManager::get_user_picture_path_by_id($userId, 'web');

        $opts['roots'][] = array(
            'driver'     => 'LocalFileSystem',
            'path'       => $dir['dir'].'my_files',
            'startPath'  => '/',
            //'alias' => 'Personal documents',
            'URL' => $dirWeb['dir'].'my_files',
            'accessControl' => 'access',
            'attributes' => array(
                'pattern' => '/^images$/',
                'read'   => false,
                'write'  => false,
                'locked' => true,
                //'hidden' => false
            )
        );
    }*/
} else {
    // Add another driver



   // Adding user personal files

    $dir = UserManager::get_user_picture_path_by_id($userId, 'system');
    $dirWeb = UserManager::get_user_picture_path_by_id($userId, 'web');

    $opts['roots'][] = array(
        'driver'     => 'LocalFileSystem',
        'path'       => $dir['dir'].'my_files',
        'startPath'  => '/',
        'URL' => $dirWeb['dir'].'my_files',
        'accessControl' => 'access',
        'attributes' => array(
            'pattern' => '/^images$/',
            'read'   => false,
            'write'  => false,
            'locked' => true,
            //'hidden' => false
        )
    );
}
// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();
