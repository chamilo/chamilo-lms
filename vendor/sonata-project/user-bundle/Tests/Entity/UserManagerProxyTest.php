<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Tests\Entity;

use Sonata\UserBundle\Entity\UserManagerProxy;

class UserManagerProxyTest extends \PHPUnit_Framework_TestCase
{
    public function testProxy()
    {
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')->disableOriginalConstructor()->getMock();

        $userManager = $this->getMockBuilder('Sonata\UserBundle\Entity\UserManager')->disableOriginalConstructor()->getMock();

        $userManagerProxy = new UserManagerProxy('stClass', $doctrine, $userManager);

        $userManager->expects($this->once())->method('getClass');
        $userManagerProxy->getClass();

        $userManager->expects($this->once())->method('findAll');
        $userManagerProxy->findAll();

        $userManager->expects($this->once())->method('findBy');
        $userManagerProxy->findBy(array());

        $userManager->expects($this->once())->method('findOneBy');
        $userManagerProxy->findOneBy(array());

        $userManager->expects($this->once())->method('find');
        $userManagerProxy->find(10);

        $userManager->expects($this->once())->method('create');
        $userManagerProxy->create();

        $userManager->expects($this->once())->method('save');
        $userManagerProxy->save('grou');

        $userManager->expects($this->once())->method('delete');
        $userManagerProxy->delete('grou');

        $userManager->expects($this->once())->method('getTableName');
        $userManagerProxy->getTableName();

        $userManager->expects($this->once())->method('getConnection');
        $userManagerProxy->getConnection();
    }
}
