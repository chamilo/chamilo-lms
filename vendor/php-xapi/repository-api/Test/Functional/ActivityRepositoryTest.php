<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Api\Test\Functional;

use PHPUnit\Framework\TestCase;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\IRI;
use XApi\Repository\Api\ActivityRepositoryInterface;

/**
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
abstract class ActivityRepositoryTest extends TestCase
{
    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    protected function setUp()
    {
        $this->activityRepository = $this->createActivityRepository();
        $this->cleanDatabase();
    }

    protected function tearDown()
    {
        $this->cleanDatabase();
    }

    /**
     * @expectedException \Xabbuh\XApi\Common\Exception\NotFoundException
     */
    public function testFetchingNonExistingActivityThrowsException()
    {
        $this->activityRepository->findActivityById(IRI::fromString('not-existing'));
    }

    /**
     * @dataProvider getStatementsWithId
     */
    public function testActivitiesCanBeRetrievedById(Activity $activity)
    {
        $fetchedActivity = $this->activityRepository->findActivityById($activity->getId());

        $this->assertTrue($activity->equals($fetchedActivity));
    }

    public function getActivitiesWithId()
    {
        $fixtures = array();

        foreach (get_class_methods('Xabbuh\XApi\DataFixtures\ActivityFixtures') as $method) {
            $activity = call_user_func(array('Xabbuh\XApi\DataFixtures\ActivityFixtures', $method));

            if ($activity instanceof Activity) {
                $fixtures[$method] = array($activity);
            }
        }

        return $fixtures;
    }

    abstract protected function createActivityRepository();

    abstract protected function cleanDatabase();
}
