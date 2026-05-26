<?php

declare(strict_types=1);

/**
 * chamidoc plugin\CStudio\0_dal\dal.global_lib.php.
 *
 * @author Damien Renou <rxxxx.dxxxxx@gmail.com>
 *
 * @version 18/05/2024
 */
$GLibLMS = false;

// CHAMILO 1.11.x
if (!$GLibLMS && file_exists(__DIR__.'/../../../../../main/inc/global.inc.php')) {
    require_once __DIR__.'/../../../../../main/inc/global.inc.php';
    $GLibLMS = true;
}
if (!$GLibLMS && file_exists(__DIR__.'/../../../../main/inc/global.inc.php')) {
    require_once __DIR__.'/../../../../main/inc/global.inc.php';
    $GLibLMS = true;
}
if (!$GLibLMS && file_exists(__DIR__.'/../../../main/inc/global.inc.php')) {
    require_once __DIR__.'/../../../main/inc/global.inc.php';
    $GLibLMS = true;
}
if (!$GLibLMS && file_exists(__DIR__.'/../../main/inc/global.inc.php')) {
    require_once __DIR__.'/../../main/inc/global.inc.php';
    $GLibLMS = true;
}
if (!$GLibLMS && file_exists(__DIR__.'/../../../../../../main/inc/global.inc.php')) {
    require_once __DIR__.'/../../../../../../main/inc/global.inc.php';
    $GLibLMS = true;
}
$isMoo = false;

// MOO
if (!$GLibLMS && file_exists('../../../../config.php')) {
    require_once '../../../../config.php';
    $GLibLMS = true;
    $isMoo = true;
}
if (!$GLibLMS && file_exists('../../../config.php')) {
    require_once '../../../config.php';
    $GLibLMS = true;
    $isMoo = true;
}
if (!$GLibLMS && file_exists('../../../../../config.php')) {
    require_once '../../../../../config.php';
    $GLibLMS = true;
    $isMoo = true;
}
if (!$GLibLMS && file_exists('../../../../../../config.php')) {
    require_once '../../../../../../config.php';
    $GLibLMS = true;
    $isMoo = true;
}

if ($isMoo) {
    define('SESSIONADMIN', 3);
    define('COURSEMANAGER', 1);
    define('PLATFORM_ADMIN', 11);
    define('SYS_PATH', 'SYS_PATH');
    define('WEB_PATH', 'WEB_PATH');
    define('SYS_PLUGIN_PATH', 'SYS_PLUGIN_PATH');
    define('WEB_PLUGIN_PATH', 'WEB_PLUGIN_PATH');
    define('SYS_COURSE_PATH', 'SYS_COURSE_PATH');
    define('WEB_COURSE_PATH', 'WEB_COURSE_PATH');
    define('TABLE_MAIN_COURSE', 'TABLE_MAIN_COURSE');
    define('TABLE_LP_MAIN', 'TABLE_LP_MAIN');
}
