<?php
namespace Sonata\EasyExtendsBundle\Tests\Mapper;

use Sonata\EasyExtendsBundle\Mapper\DoctrineORMMapper;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineORMMapperTest extends \PHPUnit_Framework_TestCase
{
    private $doctrine;
    private $metadata;

    public function setUp()
    {
        $this->doctrine = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry', array(), array(), '', false);
        $this->metadata = $this->getMock('Doctrine\ORM\Mapping\ClassMetadataInfo', array(), array(), '', false);
    }

    public function testLoadDiscriminators()
    {
        $this->metadata
            ->expects($this->atLeastOnce())
            ->method('setDiscriminatorMap')
            ->with(array('key' => 'discriminator'));

        $this->metadata->name = "class";
        $mapper = new DoctrineORMMapper($this->doctrine);
        $mapper->addDiscriminator('class', 'key', 'discriminator');

        $r = new \ReflectionObject($mapper);
        $m = $r->getMethod('loadDiscriminators');
        $m->setAccessible(true);
        $m->invoke($mapper, $this->metadata);
    }

    public function testLoadDiscriminatorColumns()
    {
        $this->metadata
            ->expects($this->atLeastOnce())
            ->method('setDiscriminatorColumn')
            ->with(array('name' => 'disc'));

        $this->metadata->name = "class";
        $mapper = new DoctrineORMMapper($this->doctrine);
        $mapper->addDiscriminatorColumn('class', array('name' => 'disc'));

        $r = new \ReflectionObject($mapper);
        $m = $r->getMethod('loadDiscriminatorColumns');
        $m->setAccessible(true);
        $m->invoke($mapper, $this->metadata);
    }

    public function testInheritanceTypes()
    {
        $this->metadata
            ->expects($this->atLeastOnce())
            ->method('setInheritanceType')
            ->with(1);

        $this->metadata->name = "class";
        $mapper = new DoctrineORMMapper($this->doctrine);
        $mapper->addInheritanceType('class', 1);

        $r = new \ReflectionObject($mapper);
        $m = $r->getMethod('loadInheritanceTypes');
        $m->setAccessible(true);
        $m->invoke($mapper, $this->metadata);
    }
}