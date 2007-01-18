<?php

$version = isset($_SERVER["argv"][1]) ? $_SERVER["argv"][1] : file_get_contents(dirname(__FILE__)."/../version");
$archivename = 'phpfreechat-'.$version.'-setup.php';
$pfcpath = dirname(__FILE__).'/phpfreechat-'.$version;
if (!file_exists($pfcpath)) die("Dont find the directory $pfcpath");
$phpinstaller_path = realpath(dirname(__FILE__).'/../contrib/installer.beta-5.1');
include($phpinstaller_path.'/engine.inc.php');
$phpi = new phpInstaller();
$phpi->dataDir($phpinstaller_path.'/engine_data');
$phpi->appName = 'phpFreeChat';
$phpi->appVersion = $version;
$phpi->addMetaFile('ss',$phpinstaller_path.'/createinstaller/data/installer.css','text/css')
  or die('Can not find stylesheet');
$phpi->ignore[] = '.svn';
$phpi->addPage('Pre-Install Check',file_get_contents($phpinstaller_path.'/createinstaller/data/precheck.inc'));
$phpi->addInstallerPages();
$phpi->addPath($pfcpath);
$phpi->generate($archivename);

?>