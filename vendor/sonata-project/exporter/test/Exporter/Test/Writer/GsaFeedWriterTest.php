<?php

namespace Exporter\Test\Source;

use Exporter\Writer\GsaFeedWriter;

/**
 * Tests the GSA feed writer class.
 *
 * @author Rémi Marseille <marseille@ekino.com>
 */
class GsaFeedWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \SplFileInfo
     */
    private $folder;

    /**
     * @var string
     */
    private $dtd;

    /**
     * @var string
     */
    private $datasource;

    /**
     * @var string
     */
    private $feedtype;

    /**
     * Creates the folder useful to this test.
     */
    public function setUp()
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sonata_exporter_test';
        $this->folder = new \SplFileInfo($path);

        $this->tearDown();

        mkdir($path);

        $this->dtd = 'http://gsa.example.com/gsafeed.dtd';
        $this->datasource = 'default_collection';
        $this->feedtype = 'metadata-and-url';
    }

    /**
     * @expectedException RuntimeException
     */
    public function testNonExistentFolder()
    {
        $writer = new GsaFeedWriter(new \SplFileInfo('foo'), $this->dtd, $this->datasource, $this->feedtype);
        $writer->open();
    }

    /**
     * Tests a simple write case.
     */
    public function testSimpleWrite()
    {
        $writer = new GsaFeedWriter($this->folder, $this->dtd, $this->datasource, $this->feedtype);
        $writer->open();
        $writer->write(array(
            'url'       => 'https://sonata-project.org/about',
            'mime_type' => 'text/html',
            'action'    => 'add',
        ));
        $writer->write(array(
            'url'       => 'https://sonata-project.org/bundles/',
            'mime_type' => 'text/html',
            'action'    => 'delete',
        ));
        $writer->close();

        $generatedFiles = $this->getFiles();

        $this->assertCount(1, $generatedFiles);
        $this->assertEquals($this->folder.'/feed_00001.xml', $generatedFiles[0]);

        // this will throw an exception if the xml is invalid
        new \SimpleXMLElement(file_get_contents($generatedFiles[0]), LIBXML_PARSEHUGE);

        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE gsafeed PUBLIC "-//Google//DTD GSA Feeds//EN" "$this->dtd">
<gsafeed>
    <header>
        <datasource>$this->datasource</datasource>
        <feedtype>$this->feedtype</feedtype>
    </header>

    <group>
        <record url="https://sonata-project.org/about" mimetype="text/html" action="add"/>
        <record url="https://sonata-project.org/bundles/" mimetype="text/html" action="delete"/>
    </group>
</gsafeed>
XML;

        $this->assertEquals(trim($expected), file_get_contents($generatedFiles[0]));
    }

    /**
     * Tests the writer limit.
     */
    public function testLimitSize()
    {
        $writer = new GsaFeedWriter($this->folder, $this->dtd, $this->datasource, $this->feedtype);
        $writer->open();

        foreach (range(0, GsaFeedWriter::LIMIT_SIZE / 8196) as $i) {
            $writer->write(array(
                'url'       => str_repeat('x', 8196),
                'mime_type' => 'text/html',
                'action'    => 'add',
            ));
        }

        $writer->close();

        $generatedFiles = $this->getFiles();

        $this->assertCount(2, $generatedFiles);

        // this will throw an exception if the xml is invalid
        new \SimpleXMLElement(file_get_contents($generatedFiles[0]), LIBXML_PARSEHUGE);
        new \SimpleXMLElement(file_get_contents($generatedFiles[1]), LIBXML_PARSEHUGE);

        $info = stat($generatedFiles[0]);

        $this->assertLessThan(GsaFeedWriter::LIMIT_SIZE, $info['size']);
    }

    /**
     * Gets an array of files of the main folder.
     *
     * @return array
     */
    public function getFiles()
    {
        $files = glob($this->folder->getRealPath().'/*.xml');

        sort($files);

        return $files;
    }

    /**
     * Deletes the generated XML and the created folder.
     */
    public function tearDown()
    {
        if ($this->folder->getRealPath()) {
            foreach ($this->getFiles() as $file) {
                unlink($file);
            }

            rmdir($this->folder->getRealPath());
        }
    }
}
