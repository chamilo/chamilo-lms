<?php
/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Test;

use PHPExiftool\Driver;
use PHPExiftool\Writer;
use PHPExiftool\Reader;
use PHPExiftool\RDFParser;

abstract class AbstractWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Writer
     */
    protected $object;
    protected $in;
    protected $inWithICC;
    protected $inPlace;
    protected $out;

    protected function setUp()
    {
        $this->object = new Writer($this->getExiftool());
        $this->in = __DIR__ . '/../../../files/ExifTool.jpg';
        $this->inWithICC = __DIR__ . '/../../../files/pixelWithIcc.jpg';
        $this->out = __DIR__ . '/../../../files/ExifTool_erased.jpg';
        $this->inPlace = __DIR__ . '/../../../files/ExifToolCopied.jpg';
        copy($this->in, $this->inPlace);
    }

    protected function tearDown()
    {
        if (file_exists($this->out) && is_writable($this->out)) {
            unlink($this->out);
        }
        if (file_exists($this->inPlace) && is_writable($this->inPlace)) {
            unlink($this->inPlace);
        }
    }

    /**
     * @covers PHPExiftool\Writer::setMode
     * @covers PHPExiftool\Writer::isMode
     */
    public function testSetMode()
    {
        $this->object->setMode(Writer::MODE_EXIF2IPTC, true);
        $this->assertTrue($this->object->isMode(Writer::MODE_EXIF2IPTC));
        $this->object->setMode(Writer::MODE_XMP2EXIF, true);
        $this->assertTrue($this->object->isMode(Writer::MODE_XMP2EXIF));
        $this->object->setMode(Writer::MODE_EXIF2IPTC, false);
        $this->assertFalse($this->object->isMode(Writer::MODE_EXIF2IPTC));
        $this->object->setMode(Writer::MODE_XMP2EXIF, true);
        $this->assertTrue($this->object->isMode(Writer::MODE_XMP2EXIF));
    }

    /**
     * @covers PHPExiftool\Writer::copy
     */
    public function testCopy()
    {
        $metadatas = new Driver\Metadata\MetadataBag();
        $this->object->erase(true, true);
        $changedFiles = $this->object->write($this->inWithICC, $metadatas, $this->out);
        $this->assertEquals(1, $changedFiles);

        $reader = new Reader($this->getExiftool(), new RDFParser());
        $metadatasRead = $reader->files($this->out)->first()->getMetadatas();
        $this->assertFalse(is_object($metadatasRead->get('IPTC:ObjectName')));

        $this->object->copy($this->in, $this->out);

        $metadatasRead = $reader->files($this->out)->first()->getMetadatas();
        $this->assertTrue(is_object($metadatasRead->get('IPTC:ObjectName')));
        $this->assertEquals("Test IPTC picture", $metadatasRead->get('IPTC:ObjectName')->getValue()->asString());
    }

    /**
     * @covers PHPExiftool\Writer::setModule
     * @covers PHPExiftool\Writer::hasModule
     */
    public function testSetModule()
    {
        $this->assertFalse($this->object->hasModule(Writer::MODULE_MWG));
        $this->object->setModule(Writer::MODULE_MWG, true);
        $this->assertTrue($this->object->hasModule(Writer::MODULE_MWG));
        $this->object->setModule(Writer::MODULE_MWG, false);
        $this->assertFalse($this->object->hasModule(Writer::MODULE_MWG));
    }

    /**
     * @covers PHPExiftool\Writer::write
     * @covers PHPExiftool\Writer::erase
     */
    public function testEraseWithoutICC()
    {
        $uniqueId = 'UNI-QUE-ID';

        $metadatas = new Driver\Metadata\MetadataBag();
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\IPTC\UniqueDocumentID(), new Driver\Value\Mono($uniqueId)));
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\XMPExif\ImageUniqueID(), new Driver\Value\Mono($uniqueId)));

        $this->object->erase(true, false);
        $changedFiles = $this->object->write($this->inWithICC, $metadatas, $this->out);
        $this->assertEquals(1, $changedFiles);

        $reader = new Reader($this->getExiftool(), new RDFParser());
        $this->assertGreaterThan(200, count($reader->files($this->in)->first()->getMetadatas()));

        $reader = new Reader($this->getExiftool(), new RDFParser());
        $this->assertGreaterThan(4, count($reader->files($this->out)->first()->getMetadatas()));
        $this->assertLessThan(30, count($reader->files($this->out)->first()->getMetadatas()));

        $acceptedMetas = array(
            'Exiftool:\w+',
            'System:\w+',
            'File:\w+',
            'Composite:\w+',
            'IPTC:CodedCharacterSet',
            'IPTC:EnvelopeRecordVersion',
            'IPTC:UniqueDocumentID',
            'IPTC:ApplicationRecordVersion',
            'Photoshop:IPTCDigest',
            'XMP-x:XMPToolkit',
            'XMP-exif:ImageUniqueID',
            'Adobe:DCTEncodeVersion',
            'Adobe:APP14Flags0',
            'Adobe:APP14Flags1',
            'Adobe:ColorTransform',
        );

        foreach ($reader->files($this->out)->first()->getMetadatas() as $meta) {

            $found = false;

            foreach ($acceptedMetas as $accepted) {
                if (preg_match('/' . $accepted . '/i', $meta->getTag()->getTagname())) {
                    $found = true;
                    break;
                }
            }

            if ( ! $found) {
                $this->fail(sprintf('Unexpected meta %s found', $meta->getTag()->getTagname()));
            }
        }
    }

    public function testEraseWithICC()
    {
        $uniqueId = 'UNI-QUE-ID';

        $metadatas = new Driver\Metadata\MetadataBag();
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\IPTC\UniqueDocumentID(), new Driver\Value\Mono($uniqueId)));
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\XMPExif\ImageUniqueID(), new Driver\Value\Mono($uniqueId)));

        $this->object->erase(true, true);
        $changedFiles = $this->object->write($this->inWithICC, $metadatas, $this->out);
        $this->assertEquals(1, $changedFiles);

        $reader = new Reader($this->getExiftool(), new RDFParser());
        $this->assertGreaterThan(200, count($reader->files($this->in)->first()->getMetadatas()));

        $reader = new Reader($this->getExiftool(), new RDFParser());
        $this->assertGreaterThan(4, count($reader->files($this->out)->first()->getMetadatas()));

        $acceptedMetas = array(
            'Exiftool:\w+',
            'System:\w+',
            'File:\w+',
            'Composite:\w+',
            'IPTC:CodedCharacterSet',
            'ICC-header:\w+',
            'IPTC:EnvelopeRecordVersion',
            'IPTC:UniqueDocumentID',
            'IPTC:ApplicationRecordVersion',
            'Photoshop:IPTCDigest',
            'XMP-x:XMPToolkit',
            'XMP-exif:ImageUniqueID',
            'Adobe:DCTEncodeVersion',
            'Adobe:APP14Flags0',
            'Adobe:APP14Flags1',
            'Adobe:ColorTransform',
        );

        foreach ($reader->files($this->out)->first()->getMetadatas() as $meta) {

            $found = false;

            foreach ($acceptedMetas as $accepted) {
                if (preg_match('/' . $accepted . '/i', $meta->getTag()->getTagname())) {
                    $found = true;
                    break;
                }
            }

            if ( ! $found) {
                $this->fail(sprintf('Unexpected meta %s found', $meta->getTag()->getTagname()));
            }
        }
    }

    /**
     * @covers PHPExiftool\Writer::write
     */
    public function testWrite()
    {
        $metadatas = new Driver\Metadata\MetadataBag();
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\IPTC\ObjectName(), new Driver\Value\Mono('Beautiful Object')));
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\IPTC\ObjectName(), new Driver\Value\Mono('Beautiful Object')));
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\XMPIptcExt\PersonInImage(), new Driver\Value\Multi(array('Romain', 'Nicolas'))));

        $changedFiles = $this->object->write($this->in, $metadatas, $this->out);

        $this->assertEquals(1, $changedFiles);

        $reader = new Reader($this->getExiftool(), new RDFParser());
        $metadatasRead = $reader->files($this->out)->first()->getMetadatas();

        $this->assertGreaterThan(200, count($metadatasRead));

        $this->assertEquals('Beautiful Object', $metadatasRead->get('IPTC:ObjectName')->getValue()->asString());
        $this->assertEquals(array('Romain', 'Nicolas'), $metadatasRead->get('XMP-iptcExt:PersonInImage')->getValue()->asArray());
    }

    /**
     * @covers PHPExiftool\Writer::write
     */
    public function testWriteInPlace()
    {
        $metadatas = new Driver\Metadata\MetadataBag();
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\IPTC\ObjectName(), new Driver\Value\Mono('Beautiful Object')));
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\IPTC\ObjectName(), new Driver\Value\Mono('Beautiful Object')));
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\XMPIptcExt\PersonInImage(), new Driver\Value\Multi(array('Romain', 'Nicolas'))));

        $changedFiles = $this->object->write($this->inPlace, $metadatas);

        $this->assertEquals(1, $changedFiles);

        $reader = new Reader($this->getExiftool(), new RDFParser());
        $metadatasRead = $reader->files($this->inPlace)->first()->getMetadatas();

        $this->assertGreaterThan(200, count($metadatasRead));

        $this->assertEquals('Beautiful Object', $metadatasRead->get('IPTC:ObjectName')->getValue()->asString());
        $this->assertEquals(array('Romain', 'Nicolas'), $metadatasRead->get('XMP-iptcExt:PersonInImage')->getValue()->asArray());
    }

    /**
     * @covers PHPExiftool\Writer::write
     */
    public function testWriteInPlaceErased()
    {
        $metadatas = new Driver\Metadata\MetadataBag();
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\IPTC\ObjectName(), new Driver\Value\Mono('Beautiful Object')));
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\IPTC\ObjectName(), new Driver\Value\Mono('Beautiful Object')));
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\XMPIptcExt\PersonInImage(), new Driver\Value\Multi(array('Romain', 'Nicolas'))));

        $this->object->erase(true);
        $changedFiles = $this->object->write($this->inPlace, $metadatas);

        $this->assertEquals(1, $changedFiles);

        $reader = new Reader($this->getExiftool(), new RDFParser());
        $metadatasRead = $reader->files($this->inPlace)->first()->getMetadatas();

        $this->assertLessThan(50, count($metadatasRead));

        $this->assertEquals('Beautiful Object', $metadatasRead->get('IPTC:ObjectName')->getValue()->asString());
        $this->assertEquals(array('Romain', 'Nicolas'), $metadatasRead->get('XMP-iptcExt:PersonInImage')->getValue()->asArray());
    }

    /**
     * @covers PHPExiftool\Writer::write
     * @covers PHPExiftool\Exception\InvalidArgumentException
     * @expectedException PHPExiftool\Exception\InvalidArgumentException
     */
    public function testWriteFail()
    {
        $this->object->write('ici', new Driver\Metadata\MetadataBag());
    }

    /**
     * @covers PHPExiftool\Writer::addMetadatasArg
     */
    public function testAddMetadatasArg()
    {
        $metadatas = new Driver\Metadata\MetadataBag();
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\IPTC\ObjectName(), new Driver\Value\Mono('Beautiful Object')));
        $metadatas->add(new Driver\Metadata\Metadata(new Driver\Tag\XMPIptcExt\PersonInImage(), new Driver\Value\Multi(array('Romain', 'Nicolas'))));

        $writer = new WriterTester($this->getExiftool());
        $this->assertNotContains('@', trim($writer->getSyncCommandTester()));

        $writer->setMode(WriterTester::MODE_EXIF2IPTC, true);
        $this->assertContains('@ exif2iptc.args', $writer->getSyncCommandTester());

        $writer->setMode(WriterTester::MODE_EXIF2XMP, true);
        $this->assertContains('@ exif2xmp.args', $writer->getSyncCommandTester());

        $writer->setMode(WriterTester::MODE_IPTC2EXIF, true);
        $this->assertContains('@ iptc2exif', $writer->getSyncCommandTester());

        $writer->setMode(WriterTester::MODE_IPTC2XMP, true);
        $this->assertContains('@ iptc2xmp', $writer->getSyncCommandTester());

        $writer->setMode(WriterTester::MODE_GPS2XMP, true);
        $this->assertContains('@ gps2xmp', $writer->getSyncCommandTester());

        $writer->setMode(WriterTester::MODE_PDF2XMP, true);
        $this->assertContains('@ pdf2xmp', $writer->getSyncCommandTester());

        $writer->setMode(WriterTester::MODE_XMP2PDF, true);
        $this->assertContains('@ xmp2pdf', $writer->getSyncCommandTester());

        $writer->setMode(WriterTester::MODE_XMP2GPS, true);
        $this->assertContains('@ xmp2gps', $writer->getSyncCommandTester());

        $writer->setMode(WriterTester::MODE_XMP2EXIF, true);
        $this->assertContains('@ xmp2exif', $writer->getSyncCommandTester());

        $writer->setMode(WriterTester::MODE_XMP2IPTC, true);
        $this->assertContains('@ xmp2iptc', $writer->getSyncCommandTester());

        $writer->setMode(WriterTester::MODE_XMP2IPTC, false);
        $this->assertNotContains('@ xmp2iptc', $writer->getSyncCommandTester());

        $writer->setModule(WriterTester::MODULE_MWG, true);
        $this->assertContains('-use MWG', $writer->addMetadatasArgTester($metadatas));

        $writer->setModule(WriterTester::MODULE_MWG, false);
        $this->assertNotContains('-use MWG', $writer->addMetadatasArgTester($metadatas));

        $this->assertRegExp("/\ -XMP-iptcExt:PersonInImage=['\"]Nicolas['\"]/", $writer->addMetadatasArgTester($metadatas));
    }

    abstract protected function getExiftool();
}

class WriterTester extends Writer
{
    public function addMetadatasArgTester($metadatas)
    {
        return parent::addMetadatasArg($metadatas);
    }

    public function getSyncCommandTester()
    {
        return parent::getSyncCommand();
    }
}
