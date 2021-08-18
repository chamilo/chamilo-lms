<?php
require_once __DIR__.'/../inc/global.inc.php';

function loadExportRoot($class) {
    require_once api_get_path(SYS_CODE_PATH).'common_cartridge/export/src/lib/ccdependencyparser.php';
    require_once api_get_path(SYS_CODE_PATH).'common_cartridge/export/src/CcAssesment.php';
    $path = api_get_path(SYS_CODE_PATH).'common_cartridge/export/';
    $file = $path.$class.'.php';
    if (file_exists($file)) {
        require_once $path.$class.'.php';
    }
}

function loadExportBase($class) {
    $path = api_get_path(SYS_CODE_PATH).'common_cartridge/export/src/base/';
    $file = $path.$class.'.php';
    if (file_exists($file)) {
        require_once $path.$class.'.php';
    }
}

function loadExportConverter($class) {
    $path = api_get_path(SYS_CODE_PATH).'common_cartridge/export/src/converter/';
    $file = $path.$class.'.php';
    if (file_exists($file)) {
        require_once $path.$class.'.php';
    }
}

function loadExportInterfaces($class) {
    $path = api_get_path(SYS_CODE_PATH).'common_cartridge/export/src/interfaces/';
    $file = $path.$class.'.php';
    if (file_exists($file)) {
        require_once $path.$class.'.php';
    }
}

function loadExportUtils($class) {
    $path = api_get_path(SYS_CODE_PATH).'common_cartridge/export/src/utils/';
    $file = $path.$class.'.php';
    if (file_exists($file)) {
        require_once $path.$class.'.php';
    }
}

function loadExportSourceRoot($class) {
    $path = api_get_path(SYS_CODE_PATH).'common_cartridge/export/src/';
    $file = $path.$class.'.php';
    if (file_exists($file)) {
        require_once $path.$class.'.php';
    }
}

spl_autoload_register('loadExportRoot');
spl_autoload_register('loadExportSourceRoot');
spl_autoload_register('loadExportBase');
spl_autoload_register('loadExportConverter');
spl_autoload_register('loadExportInterfaces');
spl_autoload_register('loadExportUtils');

function loadImportRoot($class) {
    
    require_once api_get_path(SYS_CODE_PATH).'common_cartridge/import/src/lib/validateurlsyntax.php';
    require_once api_get_path(SYS_CODE_PATH).'common_cartridge/import/src/inc/constants.php';
    
    $path = api_get_path(SYS_CODE_PATH).'common_cartridge/import/';
    $file = $path.$class.'.php';
    if (file_exists($file)) {
        require_once $path.$class.'.php';
    }
}

function loadImportBase($class) {
    require_once api_get_path(SYS_CODE_PATH).'common_cartridge/import/src/base/CcValidator.php';
    $path = api_get_path(SYS_CODE_PATH).'common_cartridge/import/src/base/';
    $file = $path.$class.'.php';
    if (file_exists($file)) {
        require_once $path.$class.'.php';
    }
}

function loadImportConverter($class) {
    $path = api_get_path(SYS_CODE_PATH).'common_cartridge/import/src/converter/';
    $file = $path.$class.'.php';
    if (file_exists($file)) {
        require_once $path.$class.'.php';
    }
}

spl_autoload_register('loadImportRoot');
spl_autoload_register('loadImportBase');
spl_autoload_register('loadImportConverter');