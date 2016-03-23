<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sonata\Test\UserBundle\Controller\Api;

use Sonata\UserBundle\Controller\Api\UserController;


/**
 * Class UserControllerTest
 *
 * @package Sonata\Test\UserBundle\Controller\Api
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class UserControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetUsersAction()
    {
        $user        = $this->getMock('Sonata\UserBundle\Model\UserInterface');
        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('findUsersBy')->will($this->returnValue(array($user)));

        $paramFetcher = $this->getMock('FOS\RestBundle\Request\ParamFetcherInterface');
        $paramFetcher->expects($this->exactly(3))->method('get');
        $paramFetcher->expects($this->once())->method('all')->will($this->returnValue(array()));

        $this->assertEquals(array($user), $this->createUserController(null, $userManager)->getUsersAction($paramFetcher));
    }

    public function testGetUserAction()
    {
        $user = $this->getMock('Sonata\UserBundle\Model\UserInterface');
        $this->assertEquals($user, $this->createUserController($user)->getUserAction(1));
    }

    /**
     * @expectedException        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage User (42) not found
     */
    public function testGetUserActionNotFoundException()
    {
        $this->createUserController()->getUserAction(42);
    }


    /**
     * @param $user
     * @param $userManager
     *
     * @return UserController
     */
    public function createUserController($user = null, $userManager = null)
    {
        if (null === $userManager) {
            $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        }
        if (null !== $user) {
            $userManager->expects($this->once())->method('findUserBy')->will($this->returnValue($user));
        }

        return new UserController($userManager);
    }
}
