<?php

namespace Alchemy\Zippy\Functional;

class ListArchiveTest extends FunctionalTestCase
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
    public function testList($archive)
    {
        $target = __DIR__ . '/samples/tmp';

        $expectedMembers = $archive->getMembers($target);
        $foundMembers = array();

        $files2find = array(
            'directory/',
            'directory/README.md',
            'directory/photo.jpg'
        );

        foreach ($archive as $member) {
            $foundMembers[] = $member;
            $this->assertInstanceOf('Alchemy\Zippy\Archive\MemberInterface', $member);
            $this->assertTrue(in_array($member->getLocation(), $files2find));
            unset($files2find[array_search($member->getLocation(), $files2find)]);
        }

        $this->assertEquals($expectedMembers, $foundMembers);
        $this->assertEquals(array(), $files2find);
    }

    /**
     * @depends testOpen
     */
    public function testCount($archive)
    {
        $this->assertCount(3, $archive);
    }
}
