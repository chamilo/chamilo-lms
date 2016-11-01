<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Test\UserBundle\Controller\Api;

use Sonata\UserBundle\Controller\Api\UserController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserControllerTest.
 *
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class UserControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetUsersAction()
    {
        $user = $this->getMock('Sonata\UserBundle\Model\UserInterface');
        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('getPager')->will($this->returnValue(array()));

        $paramFetcher = $this->getMock('FOS\RestBundle\Request\ParamFetcherInterface');
        $paramFetcher->expects($this->exactly(3))->method('get');
        $paramFetcher->expects($this->once())->method('all')->will($this->returnValue(array()));

        $this->assertEquals(array(), $this->createUserController(null, $userManager)->getUsersAction($paramFetcher));
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

    public function testPostUserAction()
    {
        $user = $this->getMock('FOS\UserBundle\Model\UserInterface');

        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('updateUser')->will($this->returnValue($user));

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('submit');
        $form->expects($this->once())->method('isValid')->will($this->returnValue(true));
        $form->expects($this->once())->method('getData')->will($this->returnValue($user));

        $formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory->expects($this->once())->method('createNamed')->will($this->returnValue($form));

        $view = $this->createUserController(null, $userManager, null, $formFactory)->postUserAction(new Request());

        $this->assertInstanceOf('FOS\RestBundle\View\View', $view);
    }

    public function testPostUserInvalidAction()
    {
        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('submit');
        $form->expects($this->once())->method('isValid')->will($this->returnValue(false));

        $formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory->expects($this->once())->method('createNamed')->will($this->returnValue($form));

        $view = $this->createUserController(null, $userManager, null, $formFactory)->postUserAction(new Request());

        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $view);
    }

    public function testPutUserAction()
    {
        $user = $this->getMock('FOS\UserBundle\Model\UserInterface');

        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('findUserBy')->will($this->returnValue($user));
        $userManager->expects($this->once())->method('updateUser')->will($this->returnValue($user));

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('submit');
        $form->expects($this->once())->method('isValid')->will($this->returnValue(true));
        $form->expects($this->once())->method('getData')->will($this->returnValue($user));

        $formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory->expects($this->once())->method('createNamed')->will($this->returnValue($form));

        $view = $this->createUserController($user, $userManager, null, $formFactory)->putUserAction(1, new Request());

        $this->assertInstanceOf('FOS\RestBundle\View\View', $view);
    }

    public function testPutUserInvalidAction()
    {
        $user = $this->getMock('FOS\UserBundle\Model\UserInterface');

        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('findUserBy')->will($this->returnValue($user));

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('submit');
        $form->expects($this->once())->method('isValid')->will($this->returnValue(false));

        $formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory->expects($this->once())->method('createNamed')->will($this->returnValue($form));

        $view = $this->createUserController($user, $userManager, null, $formFactory)->putUserAction(1, new Request());

        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $view);
    }

    public function testPostUserGroupAction()
    {
        $user = $this->getMock('Sonata\UserBundle\Entity\BaseUser');
        $user->expects($this->once())->method('hasGroup')->will($this->returnValue(false));

        $group = $this->getMock('FOS\UserBundle\Model\GroupInterface');

        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('findUserBy')->will($this->returnValue($user));
        $userManager->expects($this->once())->method('updateUser')->will($this->returnValue($user));

        $groupManager = $this->getMock('Sonata\UserBundle\Model\GroupManagerInterface');
        $groupManager->expects($this->once())->method('findGroupBy')->will($this->returnValue($group));

        $view = $this->createUserController($user, $userManager, $groupManager)->postUserGroupAction(1, 1);

        $this->assertEquals(array('added' => true), $view);
    }

    public function testPostUserGroupInvalidAction()
    {
        $user = $this->getMock('Sonata\UserBundle\Entity\BaseUser');
        $user->expects($this->once())->method('hasGroup')->will($this->returnValue(true));

        $group = $this->getMock('FOS\UserBundle\Model\GroupInterface');

        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('findUserBy')->will($this->returnValue($user));

        $groupManager = $this->getMock('Sonata\UserBundle\Model\GroupManagerInterface');
        $groupManager->expects($this->once())->method('findGroupBy')->will($this->returnValue($group));

        $view = $this->createUserController($user, $userManager, $groupManager)->postUserGroupAction(1, 1);

        $this->assertInstanceOf('FOS\RestBundle\View\View', $view);
        $this->assertEquals(400, $view->getStatusCode(), 'Should return 400');

        $data = $view->getData();

        $this->assertEquals(array('error' => 'User "1" already has group "1"'), $data);
    }

    public function testDeleteUserGroupAction()
    {
        $user = $this->getMock('Sonata\UserBundle\Entity\BaseUser');
        $user->expects($this->once())->method('hasGroup')->will($this->returnValue(true));

        $group = $this->getMock('FOS\UserBundle\Model\GroupInterface');

        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('findUserBy')->will($this->returnValue($user));
        $userManager->expects($this->once())->method('updateUser')->will($this->returnValue($user));

        $groupManager = $this->getMock('Sonata\UserBundle\Model\GroupManagerInterface');
        $groupManager->expects($this->once())->method('findGroupBy')->will($this->returnValue($group));

        $view = $this->createUserController($user, $userManager, $groupManager)->deleteUserGroupAction(1, 1);

        $this->assertEquals(array('removed' => true), $view);
    }

    public function testDeleteUserGroupInvalidAction()
    {
        $user = $this->getMock('Sonata\UserBundle\Entity\BaseUser');
        $user->expects($this->once())->method('hasGroup')->will($this->returnValue(false));

        $group = $this->getMock('FOS\UserBundle\Model\GroupInterface');

        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('findUserBy')->will($this->returnValue($user));

        $groupManager = $this->getMock('Sonata\UserBundle\Model\GroupManagerInterface');
        $groupManager->expects($this->once())->method('findGroupBy')->will($this->returnValue($group));

        $view = $this->createUserController($user, $userManager, $groupManager)->deleteUserGroupAction(1, 1);

        $this->assertInstanceOf('FOS\RestBundle\View\View', $view);
        $this->assertEquals(400, $view->getStatusCode(), 'Should return 400');

        $data = $view->getData();

        $this->assertEquals(array('error' => 'User "1" has not group "1"'), $data);
    }

    public function testDeleteUserAction()
    {
        $user = $this->getMock('FOS\UserBundle\Model\UserInterface');

        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('findUserBy')->will($this->returnValue($user));
        $userManager->expects($this->once())->method('deleteUser')->will($this->returnValue($user));

        $view = $this->createUserController($user, $userManager)->deleteUserAction(1);

        $this->assertEquals(array('deleted' => true), $view);
    }

    public function testDeleteUserInvalidAction()
    {
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');

        $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        $userManager->expects($this->once())->method('findUserBy')->will($this->returnValue(null));
        $userManager->expects($this->never())->method('deleteUser');

        $this->createUserController(null, $userManager)->deleteUserAction(1);
    }

    /**
     * @param $user
     * @param $userManager
     * @param $groupManager
     * @param $formFactory
     *
     * @return UserController
     */
    public function createUserController($user = null, $userManager = null, $groupManager = null, $formFactory = null)
    {
        if (null === $userManager) {
            $userManager = $this->getMock('Sonata\UserBundle\Model\UserManagerInterface');
        }
        if (null === $groupManager) {
            $groupManager = $this->getMock('Sonata\UserBundle\Model\GroupManagerInterface');
        }
        if (null !== $user) {
            $userManager->expects($this->once())->method('findUserBy')->will($this->returnValue($user));
        }
        if (null === $formFactory) {
            $formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        }

        return new UserController($userManager, $groupManager, $formFactory);
    }
}
