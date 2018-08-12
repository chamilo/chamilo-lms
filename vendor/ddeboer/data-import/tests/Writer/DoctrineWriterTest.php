<?php

namespace Ddeboer\DataImport\Tests\Writer;

use Ddeboer\DataImport\Writer\DoctrineWriter;
use Ddeboer\DataImport\Tests\Fixtures\Entity\TestEntity;

class DoctrineWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testWriteItem()
    {
        $em = $this->getEntityManager();

        $em->expects($this->once())
                ->method('persist');

        $writer = new DoctrineWriter($em, 'DdeboerDataImport:TestEntity');

        $association = new TestEntity();
        $item = array(
            'firstProperty'   => 'some value',
            'secondProperty'  => 'some other value',
            'firstAssociation'=> $association
        );
        $writer->writeItem($item);
    }

    protected function getEntityManager()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(array('getRepository', 'getClassMetadata', 'persist', 'flush', 'clear', 'getConnection', 'getReference'))
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setMethods(array('getName', 'getFieldNames', 'getAssociationNames', 'setFieldValue', 'getAssociationMappings'))
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Ddeboer\DataImport\Tests\Fixtures\Entity\TestEntity'));

        $metadata->expects($this->any())
            ->method('getFieldNames')
            ->will($this->returnValue(array('firstProperty', 'secondProperty')));

        $metadata->expects($this->any())
            ->method('getAssociationNames')
            ->will($this->returnValue(array('firstAssociation')));

        $metadata->expects($this->any())
            ->method('getAssociationMappings')
            ->will($this->returnValue(array(array('fieldName' => 'firstAssociation','targetEntity' => 'Ddeboer\DataImport\Tests\Fixtures\Entity\TestEntity'))));

        $configuration = $this->getMockBuilder('Doctrine\DBAL\Configuration')
            ->setMethods(array('getConnection'))
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->setMethods(array('getConfiguration', 'getDatabasePlatform', 'getTruncateTableSQL', 'executeQuery'))
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnSelf());

        $connection->expects($this->any())
            ->method('getTruncateTableSQL')
            ->will($this->returnValue('TRUNCATE SQL'));

        $connection->expects($this->any())
            ->method('executeQuery')
            ->with('TRUNCATE SQL');

        $em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repo));

        $em->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

        $em->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $self = $this;
        $em->expects($this->any())
            ->method('persist')
            ->will($this->returnCallback(function ($argument) use ($self) {
                $self->assertNotNull($argument->getFirstAssociation());
                return true;
            }));

        return $em;
    }

    public function testLoadAssociationWithoutObject()
    {
        $em = $this->getEntityManager();

        $em->expects($this->once())
            ->method('persist');

        $em->expects($this->once())
            ->method('getReference');

        $writer = new DoctrineWriter($em, 'DdeboerDataImport:TestEntity');

        $item   = array(
            'firstProperty'    => 'some value',
            'secondProperty'   => 'some other value',
            'firstAssociation' => 'firstAssociationId'
        );

        $writer->writeItem($item);
    }

    public function testLoadAssociationWithPresetObject()
    {
        $em = $this->getEntityManager();

        $em->expects($this->once())
            ->method('persist');

        $em->expects($this->never())
            ->method('getReference');

        $writer = new DoctrineWriter($em, 'DdeboerDataImport:TestEntity');

        $association = new TestEntity();
        $item        = array(
            'firstProperty'    => 'some value',
            'secondProperty'   => 'some other value',
            'firstAssociation' => $association,
        );

        $writer->writeItem($item);
    }

    /**
     * Test to make sure that we are clearing the write entity
     */
    public function testFlushAndClear()
    {
        $em = $this->getEntityManager();

        $em->expects($this->once())
            ->method('clear')
            ->with($this->equalTo('Ddeboer\DataImport\Tests\Fixtures\Entity\TestEntity'));

        $writer = new DoctrineWriter($em, 'DdeboerDataImport:TestEntity');
        $writer->finish();
    }
}
