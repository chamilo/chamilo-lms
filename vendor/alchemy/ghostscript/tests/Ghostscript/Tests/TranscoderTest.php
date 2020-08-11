<?php

namespace Ghostscript\Tests;

use Ghostscript\Transcoder;

class TranscoderTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = Transcoder::create();
    }

    public function testTranscodeToPdf()
    {
        $dest = tempnam(sys_get_temp_dir(), 'gs_temp') . '.pdf';
        $this->object->toPDF(__DIR__ . '/../../files/test.pdf', $dest, 1, 1);

        $this->assertTrue(file_exists($dest));
        $this->assertGreaterThan(0, filesize($dest));

        unlink($dest);
    }

    public function testTranscodeAIToImage()
    {
        $dest = tempnam(sys_get_temp_dir(), 'gs_temp') . '.jpg';
        $this->object->toImage(__DIR__ . '/../../files/test.pdf', $dest);

        $this->assertTrue(file_exists($dest));
        $this->assertGreaterThan(0, filesize($dest));

        unlink($dest);
    }
}
