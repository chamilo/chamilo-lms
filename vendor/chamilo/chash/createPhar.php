<?php
/**
 * Make sure you have this setting in your php.ini (cli)
 * phar.readonly = Off
 */
error_reporting(-1);

$phar = new Phar('chash.phar');
$phar->setSignatureAlgorithm(\Phar::SHA1);
$phar->startBuffering();

$phar->buildFromDirectory(__DIR__, '/\.php$/');
$phar->buildFromDirectory(__DIR__, '/\.sql/');

$defaultStub = $phar->createDefaultStub('chash.php');

// Create a custom stub to add the shebang
$stub = "#!/usr/bin/env php \n".$defaultStub;

// Add the stub
$phar->setStub($stub);

$phar->stopBuffering();
