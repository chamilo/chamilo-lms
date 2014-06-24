<?php

namespace Alchemy\Zippy\Functional;

use Alchemy\Zippy\Archive\ArchiveInterface;

class ExtractMembersArchiveTest extends FunctionalTestCase
{
    public function testOpen()
    {
        $adapter = $this->getAdapter();
        $archiveFile = $this->getArchiveFileForAdapter($adapter);

        $archive = $adapter->open($archiveFile);

        return $archive;
    }

    /**
     * @depends testOpen
     */
    public function testExtractMembersString(ArchiveInterface $archive)
    {
        $archive->extractMembers('directory/README.md', __DIR__ . '/samples/tmp');
        $archive->extractMembers('directory/photo.jpg', __DIR__ . '/samples/tmp');

        $this->assertFileExists(__DIR__ . '/samples/tmp/directory/README.md');
        $this->assertFileExists(__DIR__ . '/samples/tmp/directory/photo.jpg');
    }

    /**
     * @depends testOpen
     */
    public function testExtractFromMember(ArchiveInterface $archive)
    {
        foreach ($archive as $file) {
            if (!$file->isDir()) {
                $file->extract(__DIR__ . '/samples/tmp/');
            }
        }

        $this->assertFileExists(__DIR__ . '/samples/tmp/directory/README.md');
        $this->assertFileExists(__DIR__ . '/samples/tmp/directory/photo.jpg');
    }
}
