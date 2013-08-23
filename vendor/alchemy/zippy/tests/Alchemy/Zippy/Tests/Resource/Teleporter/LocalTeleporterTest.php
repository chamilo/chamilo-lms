<?php

namespace Alchemy\Zippy\Tests\Resource\Teleporter;

use Alchemy\Zippy\Resource\Resource;
use Alchemy\Zippy\Resource\Teleporter\LocalTeleporter;

class LocalTeleporterTest extends TeleporterTestCase
{
    /**
     * @covers Alchemy\Zippy\Resource\Teleporter\LocalTeleporter::teleport
     * @dataProvider provideContexts
     */
    public function testTeleport($context)
    {
        $teleporter = LocalTeleporter::create();

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
     * @covers Alchemy\Zippy\Resource\Teleporter\LocalTeleporter::teleport
     * @dataProvider provideContexts
     */
    public function testTeleportAStream($context)
    {
        $teleporter = LocalTeleporter::create();

        $target = 'plop-badge.php';
        $resource = new Resource('file://' . __FILE__, $target);

        if (is_file($target)) {
            unlink($context . '/' . $target);
        }

        $teleporter->teleport($resource, $context);

        $this->assertfileExists($context . '/' . $target);
        unlink($context . '/' . $target);
    }

    /**
     * @covers Alchemy\Zippy\Resource\Teleporter\LocalTeleporter::teleport
     * @dataProvider provideInvalidSources
     * @expectedException Alchemy\Zippy\Exception\InvalidArgumentException
     */
    public function testTeleportOnNonExistentFile($source)
    {
        $teleporter = LocalTeleporter::create();

        $target = 'plop-badge.php';
        $resource = new Resource($source, $target);

        $teleporter->teleport($resource, __DIR__);
    }

    public function provideInvalidSources()
    {
        return array(
            array('file://path/to/nonexistent/file'),
            array('/path/to/nonexistent/file'),
        );
    }

    /**
     * @covers Alchemy\Zippy\Resource\Teleporter\LocalTeleporter::teleport
     * @dataProvider provideContexts
     */
    public function testTeleportADir($context)
    {
        $teleporter = LocalTeleporter::create();

        $target = 'plop-badge-dir';
        $resource = new Resource(__DIR__ . '/context-test', $target);

        if (!is_dir(__DIR__ . '/context-test')) {
            mkdir(__DIR__ . '/context-test');
        }
        if (!is_file(__DIR__ . '/context-test/test-file.png')) {
            touch(__DIR__ . '/context-test/test-file.png');
        }

        if (file_exists($context . '/' . $target . '/test-file.png')) {
            unlink($context . '/' . $target . '/test-file.png');
        }

        $teleporter->teleport($resource, $context);

        $this->assertFileExists($context . '/' . $target . '/test-file.png');
        unlink($context . '/' . $target . '/test-file.png');
        rmdir($context . '/' . $target);
    }

    /**
     * @covers Alchemy\Zippy\Resource\Teleporter\LocalTeleporter::create
     */
    public function testCreate()
    {
        $this->assertInstanceOf('Alchemy\Zippy\Resource\Teleporter\LocalTeleporter', LocalTeleporter::create());
    }
}
