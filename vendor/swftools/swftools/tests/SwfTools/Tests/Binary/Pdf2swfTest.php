<?php

namespace SwfTools\Tests\Binary;

use SwfTools\Binary\Pdf2swf;
use Alchemy\BinaryDriver\Configuration;

class Pdf2swfTest extends BinaryTestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = Pdf2swf::create();
    }

    public function getClassName()
    {
        return 'SwfTools\Binary\Pdf2swf';
    }

    public function testToSwf()
    {
        $pdf   = __DIR__ . '/../../../files/PDF.pdf';
        $swf   = __DIR__ . '/../../../files/PDF.swf';
        $embed = $this->object->toSwf($pdf, $swf);

        unlink($swf);
    }

    /**
     * @expectedException \SwfTools\Exception\InvalidArgumentException
     */
    public function testToSwfInvalidFile()
    {
        $pdf   = __DIR__ . '/../../../files/PDF.pdf';
        $embed = $this->object->toSwf($pdf, '');
    }

    /**
     * @covers SwfTools\Binary\Pdf2swf::toSwf
     * @dataProvider getWrongOptions
     * @expectedException SwfTools\Exception\InvalidArgumentException
     */
    public function testToSwfInvalidRes($pdf, $dest, $opts, $convert, $res, $pages, $framerate, $quality)
    {
        $this->object->toSwf($pdf, $dest, $opts, $convert, $res, $pages, $framerate, $quality);
    }

    /**
     * @covers SwfTools\Binary\Pdf2swf::toSwf
     * @dataProvider getGoodOptions
     */
    public function testToSwfValidRes($pdf, $dest, $opts, $convert, $res, $pages, $framerate, $quality)
    {
        $this->object->toSwf($pdf, $dest, $opts, $convert, $res, $pages, $framerate, $quality);
    }

    public function testToSwfWithTimetLimit()
    {
        $dest     = __DIR__ . '/../../../files/tmp.file';
        $pdf      = __DIR__ . '/../../../files/PDF.pdf';
        $convert  = Pdf2swf::CONVERT_POLY2BITMAP;

        $phpunit = $this;
        $caught = false;

        set_error_handler(function ($errno, $errstr) use ($phpunit, &$caught) {
            $caught = true;
            $phpunit->assertEquals(E_USER_DEPRECATED, $errno);
            $phpunit->assertEquals('Use Configuration timeout instead of Pdf2Swf timelimit', $errstr);
        });

        $this->object->toSwf($pdf, $dest, array(Pdf2swf::OPTION_DISABLE_SIMPLEVIEWER), $convert, 1, '1-', 15, 75, 10);

        restore_error_handler();

        $this->assertTrue($caught);
    }

    public function getWrongOptions()
    {
        $dest     = __DIR__ . '/../../../files/tmp.file';
        $pdf      = __DIR__ . '/../../../files/PDF.pdf';
        $wrongpdf = __DIR__ . '/../../../files/wrongPDF.pdf';
        $convert  = Pdf2swf::CONVERT_POLY2BITMAP;

        return array(
            array($pdf, $dest, array(), $convert, 0, '1-', 15, 75),
            array($pdf, $dest, array(), $convert, 1, '1', 15, 75),
            array($pdf, $dest, array(), $convert, 1, '1-', 0, 75),
            array($pdf, $dest, array(), $convert, 1, '1-', 15, 110),
            array($wrongpdf, $dest, array(), $convert, 1, '1-', 15, 75),
            array($pdf, '', array(), $convert, 1, '1-', 15, 75),
        );
    }

    public function getGoodOptions()
    {
        $dest    = __DIR__ . '/../../../files/tmp.file';
        $pdf     = __DIR__ . '/../../../files/PDF.pdf';
        $convert = Pdf2swf::CONVERT_POLY2BITMAP;

        return array(
            array($pdf, $dest, array(Pdf2swf::OPTION_DISABLE_SIMPLEVIEWER), $convert, 1, '1-', 15, 75),
            array($pdf, $dest, array(Pdf2swf::OPTION_ENABLE_SIMPLEVIEWER), $convert, 1, '1-', 15, 75),
            array($pdf, $dest, array(Pdf2swf::OPTION_LINKS_DISABLE), $convert, 1, '1-', 15, 75),
            array($pdf, $dest, array(Pdf2swf::OPTION_LINKS_OPENNEWWINDOW), $convert, 1, '1-', 15, 75),
            array($pdf, $dest, array(Pdf2swf::OPTION_ZLIB_DISABLE), $convert, 1, '1-', 15, 75),
            array($pdf, $dest, array(Pdf2swf::OPTION_ZLIB_ENABLE), $convert, 1, '1-', 15, 75),
        );
    }
}
