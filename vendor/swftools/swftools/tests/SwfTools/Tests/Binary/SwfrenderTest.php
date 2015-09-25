<?php

namespace SwfTools\Tests\Binary;

use SwfTools\Binary\Swfrender;

class SwfrenderTest extends BinaryTestCase
{

    /**
     * @var Swfrender
     */
    protected $object;

    protected function setUp()
    {
        $this->object = Swfrender::create();
    }

    public function getClassName()
    {
        return 'SwfTools\Binary\Swfrender';
    }

    /**
     * @covers SwfTools\Binary\Swfrender::render
     */
    public function testRender()
    {
        $flash = __DIR__ . '/../../../files/flashfile.swf';

        $dest_file = __DIR__ . '/../../../files/tmp.jpg';

        $this->object->render($flash, $dest_file, true);

        $sizes = getimagesize($dest_file);
        $this->assertTrue(file_exists($dest_file));

        unlink($dest_file);

        $this->assertEquals(1250, $sizes[0]);
        $this->assertEquals(580, $sizes[1]);

        $this->object->render($flash, $dest_file, false);

        $sizes = getimagesize($dest_file);
        $this->assertTrue(file_exists($dest_file));

        unlink($dest_file);

        $this->assertEquals(1250, $sizes[0]);
        $this->assertEquals(580, $sizes[1]);

        $fakeFlash = __DIR__ . '/../../../files/noflashfile.swf';

        try {
            $this->object->render($fakeFlash, $dest_file, true);
            $this->fail('Swfrender should raise an exception on an unexistent file');
        } catch (\SwfTools\Exception\RuntimeException $e) {

        }
        try {
            $this->object->render($flash, '', true);
            $this->fail('Swfrender should raise an exception on an unexistent destination');
        } catch (\SwfTools\Exception\InvalidArgumentException $e) {

        }
    }

    /**
     * @covers SwfTools\Binary\Swfrender::load
     */
    public function testLoad()
    {
        $swfextract = Swfrender::create();

        $this->assertInstanceOf('SwfTools\Binary\Swfrender', $swfextract);
    }
}
