<?php

namespace Sonata\EasyExtendsBundle\Tests\Bundle;

use Sonata\EasyExtendsBundle\Bundle\OrmMetadata;

class OrmMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testEntityNames()
    {
        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock(__DIR__.'/Fixtures/bundle1'));

        $entityNames = $ormMetadata->getEntityNames();

        $this->assertEquals(4, count($entityNames));
        $this->assertContains('Block', $entityNames);
        $this->assertContains('Page', $entityNames);
    }

    public function testDirectoryWithDotInPath()
    {
        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock(__DIR__.'/Fixtures/bundle2/dot.dot'));

        $entityNames = $ormMetadata->getEntityNames();

        $this->assertEquals(4, count($entityNames));
        $this->assertContains('Block', $entityNames);
        $this->assertContains('Page', $entityNames);
    }

    public function testGetMappingEntityDirectory()
    {
        $bundlePath = __DIR__.'/Fixtures/bundle1';
        $expectedDirectory = $bundlePath.'/Resources/config/doctrine/';

        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock($bundlePath));

        $directory = $ormMetadata->getMappingEntityDirectory();

        $this->assertEquals($expectedDirectory, $directory);
    }

    public function testGetExtendedMappingEntityDirectory()
    {
        $bundlePath = __DIR__.'/Fixtures/bundle1';
        $expectedDirectory = 'Application/Sonata/AcmeBundle/Resources/config/doctrine/';

        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock($bundlePath));

        $directory = $ormMetadata->getExtendedMappingEntityDirectory();

        $this->assertEquals($expectedDirectory, $directory);
    }

    public function testGetEntityDirectory()
    {
        $bundlePath = __DIR__.'/Fixtures/bundle1';
        $expectedDirectory = $bundlePath.'/Entity';

        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock($bundlePath));

        $directory = $ormMetadata->getEntityDirectory();

        $this->assertEquals($expectedDirectory, $directory);
    }

    public function testGetExtendedEntityDirectory()
    {
        $bundlePath = __DIR__.'/Fixtures/bundle1';
        $expectedDirectory = 'Application/Sonata/AcmeBundle/Entity';

        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock($bundlePath));

        $directory = $ormMetadata->getExtendedEntityDirectory();

        $this->assertEquals($expectedDirectory, $directory);
    }

    public function testGetExtendedSerializerDirectory()
    {
        $bundlePath = __DIR__.'/Fixtures/bundle1';
        $expectedDirectory = 'Application/Sonata/AcmeBundle/Resources/config/serializer';

        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock($bundlePath));

        $directory = $ormMetadata->getExtendedSerializerDirectory();

        $this->assertEquals($expectedDirectory, $directory);
    }

    public function testGetEntityMappingFiles()
    {
        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock(__DIR__.'/Fixtures/bundle1'));

        $filterIterator = $ormMetadata->getEntityMappingFiles();

        $files = array();
        foreach ($filterIterator as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertInstanceOf('Iterator', $filterIterator);
        $this->assertContainsOnly('Symfony\Component\Finder\SplFileInfo', $filterIterator);
        $this->assertContains('Block.orm.xml.skeleton', $files);
        $this->assertContains('Page.orm.xml.skeleton', $files);
        $this->assertNotContains('Block.mongodb.xml.skeleton', $files);
        $this->assertNotContains('Page.mongodb.xml.skeleton', $files);
    }

    public function testGetEntityMappingFilesWithFilesNotFound()
    {
        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock(__DIR__.'/Fixtures'));

        $result = $ormMetadata->getEntityMappingFiles();

        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }

    public function testGetRepositoryFiles()
    {
        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock(__DIR__.'/Fixtures/bundle1'));

        $filterIterator = $ormMetadata->getRepositoryFiles();

        $files = array();
        foreach ($filterIterator as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertInstanceOf('Iterator', $filterIterator);
        $this->assertContainsOnly('Symfony\Component\Finder\SplFileInfo', $filterIterator);
        $this->assertContains('BlockRepository.php', $files);
        $this->assertContains('PageRepository.php', $files);
        $this->assertNotContains('Block.php', $files);
        $this->assertNotContains('Page.php', $files);
    }

    public function testGetRepositoryFilesWithFilesNotFound()
    {
        $ormMetadata = new OrmMetadata($this->getBundleMetadataMock(__DIR__.'/Fixtures'));

        $result = $ormMetadata->getRepositoryFiles();

        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }

    /**
     * @param string $bundlePath
     *
     * @return Sonata\EasyExtendsBundle\Bundle\BundleMetadata
     */
    private function getBundleMetadataMock($bundlePath)
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\Bundle');
        $bundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($bundlePath));

        $bundleMetadata = $this->getMock(
            'Sonata\EasyExtendsBundle\Bundle\BundleMetadata',
            array(),
            array($bundle),
            '',
            true
        );
        $bundleMetadata->expects($this->any())
            ->method('getBundle')
            ->will($this->returnValue($bundle));
        $bundleMetadata->expects($this->any())
            ->method('getClass')
            ->will($this->returnValue('Sonata\\AcmeBundle\\SonataAcmeBundle'));
        $bundleMetadata->expects($this->any())
            ->method('getExtendedDirectory')
            ->will($this->returnValue('Application/Sonata/AcmeBundle'));

        return $bundleMetadata;
    }
}
