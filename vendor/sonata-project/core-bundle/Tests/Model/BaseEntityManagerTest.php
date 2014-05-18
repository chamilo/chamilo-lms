<?php

/*
 * This file is part of the Sonata package.
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
        $this->assertEquals('classname', $this->getManager()->getClass());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testException()
    {
        $this->getManager()->exception;
    }

    public function testGetEntityManager()
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->once())->method('getManagerForClass');

        $manager = new EntityManager('classname', $registry);


        $manager->em;
    }
}
