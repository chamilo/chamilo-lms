<?php

namespace Alchemy\Zippy\Tests\Resource\Teleporter;

use Alchemy\Zippy\Resource\Resource;
use Alchemy\Zippy\Resource\Teleporter\StreamTeleporter;

class StreamTeleporterTest extends TeleporterTestCase
{
    /**
     * @covers Alchemy\Zippy\Resource\Teleporter\StreamTeleporter::teleport
     * @dataProvider provideContexts
     */
    public function testTeleport($context)
    {
        $teleporter = StreamTeleporter::create();

        $target = 'plop-badge.php';
        $resource = new Resource(fopen(__FILE__, 'rb'), $target);

        if (is_file($target)) {
            unlink($context . '/' . $target);
        }

        $teleporter->teleport($resource, $context);

        $this->assertfileExists($context . '/' . $target);
        unlink($context . '/' . $target);
    }

    /**
     * @covers Alchemy\Zippy\Resource\Teleporter\StreamTeleporter::teleport
     * @dataProvider provideContexts
     */
    public function testTeleportInNonStreamMode($context)
    {
        $teleporter = StreamTeleporter::create();

        $target = 'plop-badge.php';
        $resource = new Resource(__FILE__, $target);

        if (is_file($target)) {
            unlink($context . '/' . $target);
        }

        $teleporter->teleport($resource, $context);

        $this->assertfileExists($context . '/' . $target);
        unlink($context . '/' . $target);
    }

    /**
     * @covers Alchemy\Zippy\Resource\Teleporter\StreamTeleporter::create
     */
    public function testCreate()
    {
        $this->assertInstanceOf('Alchemy\Zippy\Resource\Teleporter\StreamTeleporter', StreamTeleporter::create());
    }
}
