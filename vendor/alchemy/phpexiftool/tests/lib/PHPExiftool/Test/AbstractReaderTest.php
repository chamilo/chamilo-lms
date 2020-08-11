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

use PHPExiftool\Reader;

abstract class AbstractReaderTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Reader
     */
    protected $object;
    protected static $tmpDir;
    protected static $disableSymLinkTest = false;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $tmpDir = __DIR__ . '/tmp';

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $command = 'rmdir /q /s ' . escapeshellarg($tmpDir);
        } else {
            $command = 'rmdir -Rf ' . escapeshellarg($tmpDir);
        }

        $process = new \Symfony\Component\Process\Process($command);
        $process->run();

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir);
        }

        self::$tmpDir = $tmpDir . '/exiftool_reader';

        if (!is_dir(self::$tmpDir)) {
            mkdir(self::$tmpDir);
        }

        copy(__DIR__.'/../../../files/ExifTool.jpg', self::$tmpDir . '/test2.jpg');
        copy(__DIR__.'/../../../files/ExifTool.jpg', self::$tmpDir . '/test.jpg');

        if (!is_dir(self::$tmpDir . '/dir')) {
            mkdir(self::$tmpDir . '/dir');
        }
        if (!is_dir(self::$tmpDir . '/usr')) {
            mkdir(self::$tmpDir . '/usr');
        }

        $tmpDir2 = $tmpDir . '/exiftool_reader2';

        if (!is_dir($tmpDir2)) {
            mkdir($tmpDir2);
        }

        copy(__DIR__.'/../../../files/ExifTool.jpg', $tmpDir2 . '/test2.jpg');

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            self::$disableSymLinkTest = true;
        } elseif (!is_link(self::$tmpDir . '/symlink')) {

            if (!@symlink($tmpDir2, self::$tmpDir . '/symlink')) {
                self::$disableSymLinkTest = true;
            }
        }

        copy(__DIR__.'/../../../files/plop/CanonRaw.cr2', self::$tmpDir . '/dir/CanonRaw.cr2');

        $tmpDir3 = $tmpDir . '/exiftool_reader3';

        if (!is_dir($tmpDir3)) {
            mkdir($tmpDir3);
        }

        if (!is_dir($tmpDir3 . '/.svn')) {
            mkdir($tmpDir3 . '/.svn');
        }

        if (!is_dir($tmpDir3 . '/.roro')) {
            mkdir($tmpDir3 . '/.roro');
        }

        if (!is_dir($tmpDir3 . '/.git')) {
            mkdir($tmpDir3 . '/.git');
        }

        touch($tmpDir3 . '/.git/config');
        touch($tmpDir3 . '/.roro/.roro.tmp');
        copy(__DIR__.'/../../../files/ExifTool.jpg', $tmpDir3 . '/.exiftool.jpg');
    }

    /**
     * @covers PHPExiftool\Reader::__construct
     */
    protected function setUp()
    {
        parent::setUp();
        $this->object = $this->getReader();
    }

    /**
     * @covers PHPExiftool\Reader::__destruct
     */
    protected function tearDown()
    {
        $this->object = null;
        parent::tearDown();
    }

    /**
     * @covers PHPExiftool\Reader::getIterator
     */
    public function testGetIterator()
    {
        $file = self::$tmpDir . '/test.jpg';
        $this->assertInstanceOf('\\Iterator', $this->object->files($file)->getIterator());
    }

    /**
     * @covers PHPExiftool\Reader::append
     * @covers PHPExiftool\Reader::all
     */
    public function testAppend()
    {
        $file1 = self::$tmpDir . '/test.jpg';
        $file2 = self::$tmpDir . '/test2.jpg';
        $file3 = self::$tmpDir . '/dir/CanonRaw.cr2';
        $this->assertEquals(1, count($this->object->files($file1)->all()));

        $reader = $this->getReader();
        $reader->files(array($file2, $file3));
        $this->assertEquals(3, count($this->object->append($reader)->all()));
    }

    /**
     * @covers PHPExiftool\Reader::sort
     * @covers PHPExiftool\Reader::all
     */
    public function testSort()
    {
        $file1 = self::$tmpDir . '/test.jpg';
        $file2 = self::$tmpDir . '/test2.jpg';
        $file3 = self::$tmpDir . '/dir/CanonRaw.cr2';

        $reader = $this->getReader();
        $reader->files(array($file3, $file2, $file1));
        $reader->sort(array('directory', 'filename', 'cigarette'));

        $results = array();

        foreach ($reader->all() as $entity) {
            $results[] = basename($entity->getFile());
        }

        $this->assertSame(array('test.jpg', 'test2.jpg', 'CanonRaw.cr2'), $results);
    }

    /**
     * @covers PHPExiftool\Reader::files
     * @covers PHPExiftool\Reader::buildQuery
     */
    public function testFiles()
    {
        $file = self::$tmpDir . '/test.jpg';
        $this->object->files($file);

        $file = $this->object->files(self::$tmpDir . '/test.jpg')->first()->getFile();

        $this->assertEquals(realpath($file), realpath($file));
    }

    /**
     * @covers PHPExiftool\Reader::resetResults
     */
    public function testResetFilters()
    {
        $file = self::$tmpDir . '/test.jpg';
        $this->object->files($file)->all();
        $file = self::$tmpDir . '/test2.jpg';
        $this->object->files($file)->all();

        $this->assertEquals(2, count($this->object->all()));
    }

    /**
     * @covers PHPExiftool\Reader::ignoreDotFiles
     * @covers PHPExiftool\Reader::all
     */
    public function testIgnoreVCS()
    {
        $this->object->in(self::$tmpDir . '3');
        $this->assertEquals(1, count($this->object->all()));
    }

    /**
     * @covers PHPExiftool\Reader::ignoreDotFiles
     * @covers PHPExiftool\Reader::all
     */
    public function testIgnoreDotFiles()
    {
        $this->object->in(self::$tmpDir . '3');
        $this->assertEquals(1, count($this->object->all()));

        $this->object->ignoreDotFiles()->in(self::$tmpDir . '3');
        $this->assertEquals(0, count($this->object->all()));
    }

    /**
     * @covers PHPExiftool\Reader::in
     * @covers PHPExiftool\Reader::buildQuery
     * @covers PHPExiftool\Reader::all
     */
    public function testIn()
    {
        $reader = $this->getReader();
        $reader->in(self::$tmpDir);

        $this->assertEquals(3, count($reader->all()));

        $reader = $this->getReader();
        $reader->in(self::$tmpDir . '/dir');
        $this->assertEquals(1, count($reader->all()));

        $reader = $this->getReader();
        $reader->in(__DIR__ . '/../../../../vendor/phpexiftool/exiftool/');

        foreach ($reader as $file) {
            $this->assertEquals(basename($file->getFile()), $file->getMetadatas()->get('System:FileName')->getValue()->asString());
        }
    }

    /**
     * @covers PHPExiftool\Reader::exclude
     * @covers PHPExiftool\Reader::computeExcludeDirs
     * @covers PHPExiftool\Reader::buildQuery
     * @covers PHPExiftool\Reader::all
     */
    public function testExclude()
    {
        $reader = $this->getReader();
        $reader
                ->in(self::$tmpDir)
                ->exclude(self::$tmpDir . '/dir');

        $this->assertEquals(2, count($reader->all()));
    }

    /**
     * @dataProvider getExclude
     * @covers PHPExiftool\Reader::computeExcludeDirs
     * @covers PHPExiftool\Reader::all
     */
    public function testComputeExcludeDirs($dir)
    {
        $reader = $this->getReader();
        $reader
                ->in(self::$tmpDir)
                ->exclude($dir)
                ->all();
    }

    public function getExclude()
    {
        return array(
            array(self::$tmpDir . '/dir/'),
            array(self::$tmpDir . '/dir'),
            array('dir'),
            array('/dir'),
            array('/usr'),
            array('usr'),
            array('dir/'),
        );
    }

    /**
     * @dataProvider getWrongExclude
     * @covers PHPExiftool\Reader::computeExcludeDirs
     * @covers \PHPExiftool\Exception\RuntimeException
     * @expectedException \PHPExiftool\Exception\RuntimeException
     */
    public function testComputeExcludeDirsFail($dir)
    {
        $reader = $this->getReader();
        $reader
                ->in(self::$tmpDir)
                ->exclude($dir)
                ->all();
    }

    public function getWrongExclude()
    {
        return array(
            array(self::$tmpDir . '/dir/dir2'),
            array(self::$tmpDir . '/dirlo'),
            array('dir/dir2'),
            array('/usr/local'),
            array('usr/local'),
            array('/tmp'),
        );
    }

    /**
     * @covers PHPExiftool\Reader::extensions
     * @covers PHPExiftool\Reader::buildQuery
     * @covers PHPExiftool\Reader::buildQueryAndExecute
     */
    public function testExtensions()
    {
        $reader = $this->getReader();
        $reader->in(self::$tmpDir);
        $this->assertEquals(3, count($reader->all()));

        $reader = $this->getReader();
        $reader->in(self::$tmpDir)->notRecursive()->extensions(array('cr2'));
        $this->assertEquals(0, count($reader->all()));

        $reader = $this->getReader();
        $reader->in(self::$tmpDir)->extensions(array('cr2'));
        $this->assertEquals(1, count($reader->all()));

        $reader = $this->getReader();
        $reader->in(self::$tmpDir)->extensions(array('jpg'));
        $this->assertEquals(2, count($reader->all()));

        $reader = $this->getReader();
        $reader->in(self::$tmpDir)->extensions('jpg')->extensions('cr2');
        $this->assertEquals(3, count($reader->all()));

        $reader = $this->getReader();
        $reader->in(self::$tmpDir)->extensions(array('jpg'), false);
        $this->assertEquals(1, count($reader->all()));

        $reader = $this->getReader();
        $reader->in(self::$tmpDir)->extensions(array('cr2', 'jpg'), false)->notRecursive();
        $this->assertEquals(0, count($reader->all()));
    }

    /**
     * @covers PHPExiftool\Reader::extensions
     * @covers \PHPExiftool\Exception\LogicException
     * @expectedException \PHPExiftool\Exception\LogicException
     */
    public function testExtensionsMisUse()
    {
        $reader = $this->getReader();
        $reader->extensions('exiftool')->extensions('jpg', false);
    }

    /**
     * @covers PHPExiftool\Reader::followSymLinks
     */
    public function testFollowSymLinks()
    {
        if (self::$disableSymLinkTest) {
            $this->markTestSkipped('This system does not support symlinks');
        }

        $reader = $this->getReader();
        $reader->in(self::$tmpDir)
                ->followSymLinks();

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $reader->all());
        $this->assertEquals(4, count($reader->all()));
    }

    /**
     * @covers PHPExiftool\Reader::notRecursive
     * @covers PHPExiftool\Reader::buildQuery
     */
    public function testNotRecursive()
    {
        $reader = $this->getReader();
        $reader->in(self::$tmpDir)->notRecursive();
        $this->assertEquals(2, count($reader->all()));
    }

    /**
     * @covers PHPExiftool\Reader::getOneOrNull
     */
    public function testGetOneOrNull()
    {
        $reader = $this->getReader();
        $reader->in(self::$tmpDir)->notRecursive()->extensions(array('jpg', 'cr2'), false);

        $this->assertNull($reader->getOneOrNull());
    }

    /**
     * @covers PHPExiftool\Reader::first
     * @covers \PHPExiftool\Exception\EmptyCollectionException
     * @expectedException \PHPExiftool\Exception\EmptyCollectionException
     */
    public function testFirstEmpty()
    {
        $reader = $this->getReader();
        $reader->in(self::$tmpDir)->notRecursive()->extensions(array('jpg', 'cr2'), false);
        $reader->first();
    }

    /**
     * @covers PHPExiftool\Reader::first
     */
    public function testFirst()
    {
        $reader = $this->getReader();
        $reader
                ->in(self::$tmpDir);

        $this->assertInstanceOf('\\PHPExiftool\\FileEntity', $reader->first());
    }

    /**
     * @covers PHPExiftool\Reader::buildQuery
     * @expectedException \PHPExiftool\Exception\LogicException
     */
    public function testFail()
    {
        $reader = $this->getReader();
        $reader->all();
    }

    /**
     * @covers PHPExiftool\Reader::all
     * @covers PHPExiftool\Reader::buildQueryAndExecute
     */
    public function testAll()
    {
        $reader = $this->getReader();
        $reader->in(self::$tmpDir);

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $reader->all());
        $this->assertEquals(3, count($reader->all()));
    }

    abstract protected function getReader();

}
