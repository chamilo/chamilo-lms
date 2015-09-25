<?php

namespace Tests\Behat\Mink\Driver;

use Behat\Mink\Driver\BrowserKitDriver;
use Symfony\Component\HttpKernel\Client;

/**
 * @group browserkitdriver
 */
class BrowserKitDriverTest extends HeadlessDriverTest
{
    protected static function getDriver()
    {
        $client = new Client(require(__DIR__.'/../../../app.php'));
        $driver = new BrowserKitDriver($client);
        $driver->setRemoveScriptFromUrl(false);

        return $driver;
    }

    protected function pathTo($path)
    {
        $path = preg_replace('#quoted$#', 'quoted=', $path);

        return 'http://localhost'.$path;
    }
}
