<?php
/**
 * This script is used to generate test classes automatically from the phpunit
 * documentation in PHPDoc blocks
 */
/**
 * Initialization
 */
$codedir = dirname(__FILE__).'/../../main/';
require $codedir.'inc/global.inc.php';
$libdir = $codedir.'inc/lib/';
$listfiles = scandir($libdir);
$declared = get_declared_classes();
// List of exclusions for classes not yet used
$excludes = array('entity_repository.class.php','course_entity_repository.class.php');
foreach ($listfiles as $file) {
  // we don't want folders for now
  if (is_dir($libdir.$file) || substr($file,0,1)=='.') { continue; }
  // we only want files ending in '.class.php'
  if (substr($file,-9) != 'class.php') { continue; }
  // excluding special cases
  if (in_array($file,$excludes)) { continue; }
  // all good, now proceeding with valid classes
  echo "Including ".$libdir.$file." unless it has already been included...\n";
  include_once $libdir.$file;
  $newdeclared = get_declared_classes();
  $newclasses = array_diff($newdeclared, $declared);
  foreach ($newclasses as $newclass) {
    $declared[] = $newclass;
    // Generate the call to phpunit-skelgen
    system('phpunit-skelgen --test -- '.$newclass.' '.$libdir.$file.' '.$newclass.'Test '.$codedir.'/../tests/phpunit/classes/'.$newclass.'Test.class.php');
  }
}
