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

use Sonata\UserBundle\Controller\Api\GroupController;


/**
 * Class GroupControllerTest
 *
 * @package Sonata\Test\UserBundle\Controller\Api
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class GroupControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetGroupsAction()
    {
        $group        = $this->getMock('FOS\UserBundle\Model\GroupInterface');
        $groupManager = $this->getMock('Sonata\UserBundle\Model\GroupManagerInterface');
        $groupManager->expects($this->once())->method('findGroupsBy')->will($this->returnValue(array($group)));

        $paramFetcher = $this->getMock('FOS\RestBundle\Request\ParamFetcherInterface');
        $paramFetcher->expects($this->exactly(3))->method('get');
        $paramFetcher->expects($this->once())->method('all')->will($this->returnValue(array()));

        $this->assertEquals(array($group), $this->createGroupController(null, $groupManager)->getGroupsAction($paramFetcher));
    }

    public function testGetGroupAction()
    {
        $group = $this->getMock('FOS\UserBundle\Model\GroupInterface');
        $this->assertEquals($group, $this->createGroupController($group)->getGroupAction(1));
    }

    /**
     * @expectedException        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Group (42) not found
     */
    public function testGetGroupActionNotFoundException()
    {
        $this->createGroupController()->getGroupAction(42);
    }


    /**
     * @param $group
     * @param $groupManager
     *
     * @return GroupController
     */
    public function createGroupController($group = null, $groupManager = null)
    {
        if (null === $groupManager) {
            $groupManager = $this->getMock('Sonata\UserBundle\Model\GroupManagerInterface');
        }
        if (null !== $group) {
            $groupManager->expects($this->once())->method('findGroupBy')->will($this->returnValue($group));
        }

        return new GroupController($groupManager);
    }
}
