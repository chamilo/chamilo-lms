#!/usr/bin/env php
<?php

/*
 * This file is part of the Symfony Standard Edition.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (PHP_SAPI !== 'cli') {
    echo 'Warning: '.__FILE__.' should be invoked via the CLI version of PHP, not the '.PHP_SAPI.' SAPI'.PHP_EOL;
}

$argv = $_SERVER['argv'];

// allow the base path to be passed as the first argument, or default
if (isset($argv[1])) {
    $appDir = $argv[1];
} else {
    if (!$appDir = realpath(__DIR__.'/../../../../../../../../app')) {
        exit('Looks like you don\'t have a standard layout.');
    }
}

require_once $appDir.'/autoload.php';

\Sensio\Bundle\DistributionBundle\Composer\ScriptHandler::doBuildBootstrap($appDir);
