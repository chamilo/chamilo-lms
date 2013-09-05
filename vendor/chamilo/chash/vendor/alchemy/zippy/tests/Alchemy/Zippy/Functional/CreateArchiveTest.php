<?php

namespace Alchemy\Zippy\Functional;

use Symfony\Component\Finder\Finder;

class CreateArchiveTest extends FunctionalTestCase
{
    private static $file;

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        if (file_exists(self::$file)) {
            unlink(self::$file);
            self::$file = null;
        }
    }

    public function testCreate()
    {
        $adapter = $this->getAdapter();
        $extension = $this->getArchiveExtensionForAdapter($adapter);

        self::$file = __DIR__ . '/samples/create-archive.' . $extension;

        $archive = $adapter->create(self::$file, array(
            'directory' => __DIR__ . '/samples/directory',
        ), true);

        return $archive;
    }

    /**
     * @depends testCreate
     */
    public function testExtract($archive)
    {
        $target = __DIR__ . '/samples/tmp';
        $archive->extract($target);

        $finder = new Finder();
        $finder
            ->files()
            ->in($target);

        $files2find = array(
            '/directory/README.md',
            '/directory/photo.jpg'
        );

        foreach ($finder as $file) {
            $this->assertEquals(0, strpos($file->getPathname(), $target));
            $member = substr($file->getPathname(), strlen($target));
            $this->assertTrue(in_array($member, $files2find), "looking for $member in files2find");
            unset($files2find[array_search($member, $files2find)]);
        }

        $this->assertEquals(array(), $files2find);
    }

    /**
     * @depends testCreate
     */
    public function testExtractOnExistingFilesCanOverwrite($archive)
    {
        $random = (string) uniqid(mt_rand(), true);
        $target = __DIR__ . '/samples/tmp';

        $files2find = array(
            '/directory/README.md',
            '/directory/photo.jpg'
        );
        foreach ($files2find as $file) {
            $file2create = $target . $file;
            if (!is_dir(dirname($file2create))) {
                mkdir(dirname($file2create), 0777, true);
            }
            file_put_contents($file2create, $random);
        }

        $archive->extract($target, true);

        $finder = new Finder();
        $finder
            ->files()
            ->in($target);

        foreach ($finder as $file) {
            $this->assertEquals(0, strpos($file->getPathname(), $target));
            $this->assertNotEquals($random, file_get_contents($file->getPathname()));
            $member = substr($file->getPathname(), strlen($target));
            $this->assertTrue(in_array($member, $files2find));
            unset($files2find[array_search($member, $files2find)]);
        }

        $this->assertEquals(array(), $files2find);
    }
}
