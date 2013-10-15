<?php

namespace Alchemy\Zippy\Tests\Resource\Teleporter;

use Alchemy\Zippy\Tests\TestCase;

class TeleporterTestCase extends TestCase
{
    public function provideContexts()
    {
        if (!is_dir(__DIR__ . '/context-test')) {
            mkdir (__DIR__ . '/context-test');
        }

        return array(
            array(__DIR__),
            array(__DIR__ . '/context-test')
        );
    }
}
