<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Entity;

use Sonata\CoreBundle\Model\BaseEntityManager;

class EntityManager extends BaseEntityManager
{
}

class BaseEntityManagerTest extends \PHPUnit_Framework_TestCase
{
    public function getManager()
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $manager = new EntityManager('classname', $registry);

        return $manager;
    }

    public function test()
    {
        $this->assertSame('classname', $this->getManager()->getClass());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The property exception does not exists
     */
    public function testException()
    {
        $this->getManager()->exception;
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to find the mapping information for the class classname. Please check the 'auto_mapping' option (http://symfony.com/doc/current/reference/configuration/doctrine.html#configuration-overview) or add the bundle to the 'mappings' section in the doctrine configuration
     */
    public function testExceptionOnNonMappedEntity()
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->once())->method('getManagerForClass')->will($this->returnValue(null));

        $manager = new EntityManager('classname', $registry);
        $manager->getObjectManager();
    }

    public function testGetEntityManager()
    {
        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->once())->method('getManagerForClass')->will($this->returnValue($objectManager));

        $manager = new EntityManager('classname', $registry);

        $manager->em;
    }
}
